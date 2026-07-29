<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListPatientAppointmentsRequest;
use App\Http\Requests\ListPatientMeasurementsRequest;
use App\Http\Requests\StoreCorrectionRequestRequest;
use App\Http\Requests\StoreMeasurementRequest;
use App\Http\Resources\CorrectionRequestResource;
use App\Http\Resources\AppointmentSummaryResource;
use App\Http\Resources\MeasurementResource;
use App\Http\Resources\PatientSummaryResource;
use App\Http\Resources\TreatmentResource;
use App\Models\Alert;
use App\Models\Appointment;
use App\Models\CorrectionRequest;
use App\Models\Measurement;
use App\Models\Treatment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PatientPortalController extends Controller
{
    /**
     * List the authenticated patient's appointments, newest first.
     */
    public function appointments(ListPatientAppointmentsRequest $request): AnonymousResourceCollection|JsonResponse
    {
        $patient = $request->user()->patient;

        if ($patient === null) {
            return response()->json([
                'data' => null,
                'message' => 'No patient profile is associated with this account.',
                'errors' => null,
            ], Response::HTTP_NOT_FOUND);
        }

        $filters = $request->validated();

        $appointments = Appointment::query()
            ->with(['healthStaff.person', 'healthStaff.specialty'])
            ->where('patient_id', $patient->id)
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['date_from']), fn ($query) => $query->whereDate('scheduled_at', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn ($query) => $query->whereDate('scheduled_at', '<=', $filters['date_to']))
            ->when(
                (bool) ($filters['upcoming'] ?? false),
                fn ($query) => $query
                    ->where('scheduled_at', '>=', now())
                    ->whereIn('status', ['SCHEDULED', 'CONFIRMED'])
            )
            ->orderByDesc('scheduled_at')
            ->paginate(15)
            ->withQueryString();

        return AppointmentSummaryResource::collection($appointments);
    }

    /**
     * Return a bounded home-screen summary for the authenticated patient.
     */
    public function summary(Request $request): JsonResponse
    {
        $patient = $request->user()->patient;

        if ($patient === null) {
            return response()->json([
                'data' => null,
                'message' => 'No patient profile is associated with this account.',
                'errors' => null,
            ], Response::HTTP_NOT_FOUND);
        }

        $patient->load('person');

        $nextAppointment = Appointment::query()
            ->with(['healthStaff.person', 'healthStaff.specialty'])
            ->where('patient_id', $patient->id)
            ->where('scheduled_at', '>=', now())
            ->whereIn('status', ['SCHEDULED', 'CONFIRMED'])
            ->orderBy('scheduled_at')
            ->first();

        $latestMeasurements = Measurement::query()
            ->with('measurementType')
            ->where('patient_id', $patient->id)
            ->orderByDesc('measured_at')
            ->limit(3)
            ->get();

        $activeTreatments = Treatment::query()
            ->where('patient_id', $patient->id)
            ->where('status', 'ACTIVE')
            ->orderByDesc('start_date')
            ->limit(3)
            ->get();

        $openAlerts = Alert::query()
            ->where('patient_id', $patient->id)
            ->where('status', '!=', 'CLOSED');

        return response()->json([
            'data' => [
                'patient' => new PatientSummaryResource($patient),
                'next_appointment' => $nextAppointment === null
                    ? null
                    : new AppointmentSummaryResource($nextAppointment),
                'latest_measurements' => MeasurementResource::collection($latestMeasurements),
                'active_treatments' => TreatmentResource::collection($activeTreatments),
                'alerts_summary' => [
                    'open' => (clone $openAlerts)->count(),
                    'critical' => (clone $openAlerts)->where('severity', 'CRITICAL')->count(),
                ],
            ],
            'message' => 'Patient summary retrieved successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * List the authenticated patient's measurements, newest first.
     */
    public function measurements(ListPatientMeasurementsRequest $request): AnonymousResourceCollection|JsonResponse
    {
        $patient = $request->user()->patient;

        if ($patient === null) {
            return response()->json([
                'data' => null,
                'message' => 'No patient profile is associated with this account.',
                'errors' => null,
            ], Response::HTTP_NOT_FOUND);
        }

        $filters = $request->validated();

        $measurements = Measurement::query()
            ->with('measurementType')
            ->where('patient_id', $patient->id)
            ->when(
                isset($filters['measurement_type_id']),
                fn ($query) => $query->where('measurement_type_id', $filters['measurement_type_id'])
            )
            ->when(
                isset($filters['date_from']),
                fn ($query) => $query->whereDate('measured_at', '>=', $filters['date_from'])
            )
            ->when(
                isset($filters['date_to']),
                fn ($query) => $query->whereDate('measured_at', '<=', $filters['date_to'])
            )
            ->orderByDesc('measured_at')
            ->paginate(15)
            ->withQueryString();

        return MeasurementResource::collection($measurements);
    }

    /**
     * Register a measurement for the authenticated patient's own plan (section 5.4).
     *
     * The patient and author are forced from the session, so the patient cannot
     * register a measurement for anyone else.
     */
    public function storeMeasurement(StoreMeasurementRequest $request): JsonResponse
    {
        $patient = $request->user()->patient;

        if ($patient === null) {
            return response()->json([
                'data' => null,
                'message' => 'No patient profile is associated with this account.',
                'errors' => null,
            ], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validated();
        $data['patient_id'] = $patient->id;
        $data['origin'] = 'PATIENT';
        $data['author_user_id'] = $request->user()->id;

        $measurement = Measurement::create($data);

        return response()->json([
            'data' => new MeasurementResource($measurement->load('measurementType')),
            'message' => 'Measurement registered successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Submit an administrative correction request for the authenticated patient.
     *
     * The patient and requester are forced from the session (RF-BE-09).
     */
    public function storeCorrectionRequest(StoreCorrectionRequestRequest $request): JsonResponse
    {
        $patient = $request->user()->patient;

        if ($patient === null) {
            return response()->json([
                'data' => null,
                'message' => 'No patient profile is associated with this account.',
                'errors' => null,
            ], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validated();
        $data['patient_id'] = $patient->id;
        $data['requested_by'] = $request->user()->id;
        $data['status'] = 'PENDING';

        $correctionRequest = CorrectionRequest::create($data);

        return response()->json([
            'data' => new CorrectionRequestResource($correctionRequest),
            'message' => 'Correction request submitted successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }
}
