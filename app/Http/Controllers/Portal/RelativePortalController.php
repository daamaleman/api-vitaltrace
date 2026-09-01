<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListPatientAppointmentsRequest;
use App\Http\Requests\ListPatientMeasurementsRequest;
use App\Http\Requests\ListPatientTreatmentsRequest;
use App\Http\Resources\AppointmentSummaryResource;
use App\Http\Resources\MeasurementResource;
use App\Http\Resources\PatientClinicalHistoryResource;
use App\Http\Resources\PatientSummaryResource;
use App\Http\Resources\PatientTreatmentResource;
use App\Http\Resources\RelativeLinkedPatientResource;
use App\Http\Resources\RelativePatientProfileResource;
use App\Http\Resources\TreatmentResource;
use App\Models\Alert;
use App\Models\Appointment;
use App\Models\ClinicalEvolution;
use App\Models\Diagnosis;
use App\Models\Measurement;
use App\Models\Treatment;
use App\Services\Portal\RelativePatientAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class RelativePortalController extends Controller
{
    public function __construct(private readonly RelativePatientAccessService $access) {}

    public function patients(Request $request): AnonymousResourceCollection
    {
        return RelativeLinkedPatientResource::collection(
            $this->access->authorizedPatients($request->user())
                ->orderBy('patient_id')
                ->get()
        );
    }

    public function summary(Request $request, int $patientId): JsonResponse
    {
        $patient = $this->access->resolvePatient($request->user(), $patientId);
        $patient->load('person');

        $nextAppointment = Appointment::query()
            ->with(['healthStaff.person', 'healthStaff.specialty'])
            ->where('patient_id', $patient->id)
            ->where('scheduled_at', '>=', now())
            ->whereIn('status', ['SCHEDULED', 'CONFIRMED'])
            ->orderBy('scheduled_at')
            ->first();
        $latestMeasurements = Measurement::query()
            ->with(['measurementType', 'reviewer.person'])
            ->where('patient_id', $patient->id)
            ->orderByDesc('measured_at')->limit(3)->get();
        $activeTreatments = Treatment::query()
            ->where('patient_id', $patient->id)->where('status', 'ACTIVE')
            ->orderByDesc('start_date')->limit(3)->get();
        $openAlerts = Alert::query()->where('patient_id', $patient->id)->where('status', '!=', 'CLOSED');

        return response()->json([
            'data' => [
                'patient' => new PatientSummaryResource($patient),
                'next_appointment' => $nextAppointment === null ? null : new AppointmentSummaryResource($nextAppointment),
                'latest_measurements' => MeasurementResource::collection($latestMeasurements),
                'active_treatments' => TreatmentResource::collection($activeTreatments),
                'alerts_summary' => [
                    'open' => (clone $openAlerts)->count(),
                    'critical' => (clone $openAlerts)->where('severity', 'CRITICAL')->count(),
                ],
            ],
            'message' => 'Relative patient summary retrieved successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    public function profile(Request $request, int $patientId): JsonResponse
    {
        $patient = $this->access->resolvePatient($request->user(), $patientId);
        $patient->load('person.user');

        return response()->json([
            'data' => new RelativePatientProfileResource($patient),
            'message' => 'Relative patient profile retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function appointments(ListPatientAppointmentsRequest $request, int $patientId): AnonymousResourceCollection
    {
        $patient = $this->access->resolvePatient($request->user(), $patientId);
        $filters = $request->validated();
        $items = Appointment::query()
            ->with(['healthStaff.person', 'healthStaff.specialty'])
            ->where('patient_id', $patient->id)
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['date_from']), fn ($q) => $q->whereDate('scheduled_at', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn ($q) => $q->whereDate('scheduled_at', '<=', $filters['date_to']))
            ->when((bool) ($filters['upcoming'] ?? false), fn ($q) => $q->where('scheduled_at', '>=', now())->whereIn('status', ['SCHEDULED', 'CONFIRMED']))
            ->orderByDesc('scheduled_at')->paginate(15)->withQueryString();

        return AppointmentSummaryResource::collection($items);
    }

    public function measurements(ListPatientMeasurementsRequest $request, int $patientId): AnonymousResourceCollection
    {
        $patient = $this->access->resolvePatient($request->user(), $patientId);
        $filters = $request->validated();
        $items = Measurement::query()->with(['measurementType', 'reviewer.person'])
            ->where('patient_id', $patient->id)
            ->when(isset($filters['measurement_type_id']), fn ($q) => $q->where('measurement_type_id', $filters['measurement_type_id']))
            ->when(isset($filters['date_from']), fn ($q) => $q->whereDate('measured_at', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn ($q) => $q->whereDate('measured_at', '<=', $filters['date_to']))
            ->orderByDesc('measured_at')->paginate(15)->withQueryString();

        return MeasurementResource::collection($items);
    }

    public function treatments(ListPatientTreatmentsRequest $request, int $patientId): AnonymousResourceCollection
    {
        $patient = $this->access->resolvePatient($request->user(), $patientId);
        $filters = $request->validated();
        $items = Treatment::query()
            ->with(['diagnosis', 'prescribedBy.person', 'prescribedBy.healthStaff.specialty'])
            ->where('patient_id', $patient->id)
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['date_from']), fn ($q) => $q->whereDate('start_date', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn ($q) => $q->whereDate('start_date', '<=', $filters['date_to']))
            ->when((bool) ($filters['active'] ?? false), fn ($q) => $q->where('status', 'ACTIVE'))
            ->orderByDesc('start_date')->orderByDesc('id')->paginate(15)->withQueryString();

        return PatientTreatmentResource::collection($items);
    }

    public function clinicalHistory(Request $request, int $patientId): JsonResponse
    {
        $patient = $this->access->resolvePatient($request->user(), $patientId);
        $professionalRelations = ['registeredBy.person', 'registeredBy.healthStaff.specialty'];
        $diagnoses = Diagnosis::query()->with($professionalRelations)->where('patient_id', $patient->id)->orderByDesc('diagnosis_date')->get();
        $evolutions = ClinicalEvolution::query()->with($professionalRelations)->where('patient_id', $patient->id)->orderByDesc('recorded_at')->get();
        $treatments = Treatment::query()->with(['prescribedBy.person', 'prescribedBy.healthStaff.specialty'])->where('patient_id', $patient->id)->where('status', 'ACTIVE')->orderByDesc('start_date')->get();
        $measurements = Measurement::query()->with('measurementType')->where('patient_id', $patient->id)->orderByDesc('measured_at')->limit(30)->get();

        return response()->json([
            'data' => new PatientClinicalHistoryResource(compact('patient', 'diagnoses', 'evolutions', 'treatments', 'measurements')),
            'message' => 'Relative patient clinical history retrieved successfully.',
            'errors' => null,
        ]);
    }
}