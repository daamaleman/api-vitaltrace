<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Resources\NurseAlertResource;
use App\Http\Resources\NurseAppointmentResource;
use App\Http\Resources\NurseClinicalEvolutionResource;
use App\Http\Resources\NurseDiagnosisResource;
use App\Http\Resources\NurseMeasurementResource;
use App\Http\Resources\NurseMeasurementTypeResource;
use App\Http\Resources\NursePatientProfileResource;
use App\Http\Resources\NursePatientResource;
use App\Http\Resources\NurseTreatmentResource;
use App\Models\Alert;
use App\Models\AlertHistory;
use App\Models\Appointment;
use App\Models\ClinicalEvolution;
use App\Models\Diagnosis;
use App\Models\Measurement;
use App\Models\MeasurementType;
use App\Models\Patient;
use App\Models\Treatment;
use App\Services\Portal\NursePatientAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class NursePortalController extends Controller
{
    private const OPEN_ALERT_STATUSES = ['NEW', 'CLASSIFIED', 'ESCALATED', 'IN_PROGRESS'];

    public function __construct(private readonly NursePatientAccessService $access) {}

    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $staff = $this->access->resolveNurse($user);
        $patientIds = $this->access->assignedPatientIds($user);
        $person = $user->person;

        $alerts = Alert::query()->whereIn('patient_id', clone $patientIds);
        $appointments = Appointment::query()
            ->whereIn('patient_id', clone $patientIds)
            ->whereIn('status', ['SCHEDULED', 'CONFIRMED'])
            ->where('scheduled_at', '>', now());

        return response()->json([
            'data' => [
                'nurse' => [
                    'id' => $staff->id,
                    'full_name' => collect([$person?->first_name, $person?->middle_name, $person?->first_last_name, $person?->second_last_name])->filter()->implode(' '),
                    'professional_code' => $staff->professional_code,
                    'specialty' => $staff->specialty?->name,
                ],
                'assigned_patients_count' => $this->access->assignedPatientsQuery($user)->count(),
                'alerts' => [
                    'total_pending' => (clone $alerts)->whereIn('status', self::OPEN_ALERT_STATUSES)->count(),
                    'new' => (clone $alerts)->where('status', 'NEW')->count(),
                    'critical' => (clone $alerts)->whereIn('status', self::OPEN_ALERT_STATUSES)->where('severity', 'CRITICAL')->count(),
                ],
                'appointments' => [
                    'upcoming_count' => (clone $appointments)->count(),
                    'next' => NurseAppointmentResource::collection((clone $appointments)->with(['healthStaff.person', 'healthStaff.specialty'])->orderBy('scheduled_at')->limit(3)->get())->resolve(),
                ],
            ],
            'message' => null,
            'errors' => null,
        ]);
    }

    public function patients(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $this->access->resolveNurse($user);
        $search = trim((string) $request->query('search', ''));

        $query = $this->access->assignedPatientsQuery($user)
            ->with('person')
            ->withCount([
                'alerts as active_alerts_count' => fn (Builder $q) => $q->whereIn('status', self::OPEN_ALERT_STATUSES),
                'alerts as critical_alerts_count' => fn (Builder $q) => $q->whereIn('status', self::OPEN_ALERT_STATUSES)->where('severity', 'CRITICAL'),
            ])
            ->with([
                'latestMeasurement' => fn ($q) => $q->with('measurementType'),
                'nextAppointment' => fn ($q) => $q->with(['healthStaff.person', 'healthStaff.specialty']),
            ]);

        if ($search !== '') {
            foreach (preg_split('/\s+/', $search) ?: [] as $term) {
                $query->where(function (Builder $q) use ($term): void {
                    $q->where('record_number', 'like', "%{$term}%")
                        ->orWhereHas('person', fn (Builder $person) => $person
                            ->where('first_name', 'like', "%{$term}%")
                            ->orWhere('middle_name', 'like', "%{$term}%")
                            ->orWhere('first_last_name', 'like', "%{$term}%")
                            ->orWhere('second_last_name', 'like', "%{$term}%"));
                });
            }
        }

        return NursePatientResource::collection($query->orderBy('id')->paginate(15)->withQueryString());
    }

    public function profile(Request $request, Patient $patient): JsonResponse
    {
        $patient = $this->access->assertPatientAccess($request->user(), $patient)->load('person');

        return $this->response(new NursePatientProfileResource($patient));
    }

    public function patientSummary(Request $request, Patient $patient): JsonResponse
    {
        $patient = $this->access->assertPatientAccess($request->user(), $patient)->load('person');

        $measurements = Measurement::query()->with('measurementType')->where('patient_id', $patient->id)->latest('measured_at')->limit(7)->get();
        $diagnoses = Diagnosis::query()->where('patient_id', $patient->id)->latest('diagnosis_date')->limit(5)->get();
        $treatments = $this->treatmentsQuery($patient)->where('status', 'ACTIVE')->limit(5)->get();
        $appointment = Appointment::query()->with(['healthStaff.person', 'healthStaff.specialty'])->where('patient_id', $patient->id)->whereIn('status', ['SCHEDULED', 'CONFIRMED'])->where('scheduled_at', '>', now())->orderBy('scheduled_at')->first();
        $alerts = Alert::query()->where('patient_id', $patient->id)->whereIn('status', self::OPEN_ALERT_STATUSES)->latest('generated_at')->limit(5)->get();

        return $this->response([
            'patient' => (new NursePatientResource($patient))->resolve(),
            'recent_measurements' => NurseMeasurementResource::collection($measurements)->resolve(),
            'diagnoses' => NurseDiagnosisResource::collection($diagnoses)->resolve(),
            'active_treatments' => NurseTreatmentResource::collection($treatments)->resolve(),
            'upcoming_appointment' => $appointment === null ? null : (new NurseAppointmentResource($appointment))->resolve(),
            'active_alerts' => NurseAlertResource::collection($alerts)->resolve(),
        ]);
    }

    public function appointments(Request $request): AnonymousResourceCollection
    {
        $this->access->resolveNurse($request->user());

        $items = Appointment::query()->with(['healthStaff.person', 'healthStaff.specialty'])
            ->whereIn('patient_id', $this->access->assignedPatientIds($request->user()))
            ->orderByDesc('scheduled_at')->paginate(15)->withQueryString();

        return NurseAppointmentResource::collection($items);
    }

    public function patientAppointments(Request $request, Patient $patient): AnonymousResourceCollection
    {
        $patient = $this->access->assertPatientAccess($request->user(), $patient);

        return NurseAppointmentResource::collection(Appointment::query()
            ->with(['healthStaff.person', 'healthStaff.specialty'])
            ->where('patient_id', $patient->id)
            ->orderByDesc('scheduled_at')->paginate(15)->withQueryString());
    }

    public function appointment(Request $request, Appointment $appointment): JsonResponse
    {
        $this->access->assertPatientAccess($request->user(), $appointment->patient_id);

        return $this->response(new NurseAppointmentResource($appointment->load(['healthStaff.person', 'healthStaff.specialty'])));
    }

    public function measurements(Request $request, Patient $patient): AnonymousResourceCollection
    {
        $patient = $this->access->assertPatientAccess($request->user(), $patient);
        $filters = $request->validate(['measurement_type_id' => ['nullable', 'integer', 'exists:measurement_types,id']]);

        return NurseMeasurementResource::collection(Measurement::query()->with('measurementType')
            ->where('patient_id', $patient->id)
            ->when(isset($filters['measurement_type_id']), fn (Builder $q) => $q->where('measurement_type_id', $filters['measurement_type_id']))
            ->orderByDesc('measured_at')->paginate(15)->withQueryString());
    }

    public function storeMeasurement(Request $request, Patient $patient): JsonResponse
    {
        $patient = $this->access->assertPatientAccess($request->user(), $patient);
        $data = $request->validate([
            'measurement_type_id' => ['required', 'integer', 'exists:measurement_types,id'],
            'value' => ['required', 'numeric'],
            'unit' => ['required', 'string', 'max:30'],
            'measured_at' => ['required', 'date'],
            'observation' => ['nullable', 'string', 'max:2000'],
        ]);
        $type = MeasurementType::query()->where('active', true)->find($data['measurement_type_id']);

        if ($type === null || $data['unit'] !== $type->base_unit) {
            throw ValidationException::withMessages(['unit' => ['La unidad no corresponde al tipo de medición activo.']]);
        }

        $measurement = Measurement::create([
            ...$data,
            'patient_id' => $patient->id,
            'origin' => 'NURSE',
            'author_user_id' => $request->user()->id,
        ]);

        return $this->response(new NurseMeasurementResource($measurement->load('measurementType')), Response::HTTP_CREATED, 'Medición registrada correctamente.');
    }

    public function measurementTypes(Request $request): AnonymousResourceCollection
    {
        $this->access->resolveNurse($request->user());

        return NurseMeasurementTypeResource::collection(MeasurementType::query()->where('active', true)->orderBy('name')->get());
    }

    public function diagnoses(Request $request, Patient $patient): AnonymousResourceCollection
    {
        $patient = $this->access->assertPatientAccess($request->user(), $patient);

        return NurseDiagnosisResource::collection(Diagnosis::query()->where('patient_id', $patient->id)->latest('diagnosis_date')->paginate(15)->withQueryString());
    }

    public function treatments(Request $request, Patient $patient): AnonymousResourceCollection
    {
        $patient = $this->access->assertPatientAccess($request->user(), $patient);

        return NurseTreatmentResource::collection($this->treatmentsQuery($patient)->orderByDesc('start_date')->paginate(15)->withQueryString());
    }

    public function clinicalHistory(Request $request, Patient $patient): JsonResponse
    {
        $patient = $this->access->assertPatientAccess($request->user(), $patient)->load('person');
        $diagnoses = Diagnosis::query()->where('patient_id', $patient->id)->latest('diagnosis_date')->get();
        $evolutions = ClinicalEvolution::query()->where('patient_id', $patient->id)->latest('recorded_at')->get();
        $treatments = $this->treatmentsQuery($patient)->latest('start_date')->get();
        $measurements = Measurement::query()->with('measurementType')->where('patient_id', $patient->id)->latest('measured_at')->limit(30)->get();

        return $this->response([
            'patient' => (new NursePatientResource($patient))->resolve(),
            'diagnoses' => NurseDiagnosisResource::collection($diagnoses)->resolve(),
            'evolutions' => NurseClinicalEvolutionResource::collection($evolutions)->resolve(),
            'treatments' => NurseTreatmentResource::collection($treatments)->resolve(),
            'measurements' => NurseMeasurementResource::collection($measurements)->resolve(),
        ]);
    }

    public function alerts(Request $request): AnonymousResourceCollection
    {
        $filters = $this->alertFilters($request);
        $query = Alert::query()->whereIn('patient_id', $this->access->assignedPatientIds($request->user()));
        $this->applyAlertFilters($query, $filters);

        return NurseAlertResource::collection($query->latest('generated_at')->paginate(15)->withQueryString());
    }

    public function patientAlerts(Request $request, Patient $patient): AnonymousResourceCollection
    {
        $patient = $this->access->assertPatientAccess($request->user(), $patient);
        $filters = $this->alertFilters($request, false);
        $query = Alert::query()->where('patient_id', $patient->id);
        $this->applyAlertFilters($query, $filters);

        return NurseAlertResource::collection($query->latest('generated_at')->paginate(15)->withQueryString());
    }

    public function alert(Request $request, Alert $alert): JsonResponse
    {
        $this->access->assertPatientAccess($request->user(), $alert->patient_id);

        return $this->response(new NurseAlertResource($alert->load('history')));
    }

    public function classifyAlert(Request $request, Alert $alert): JsonResponse
    {
        return $this->transitionAlert($request, $alert, ['NEW'], 'CLASSIFIED', 'CLASSIFY');
    }

    public function escalateAlert(Request $request, Alert $alert): JsonResponse
    {
        return $this->transitionAlert($request, $alert, ['NEW', 'CLASSIFIED', 'IN_PROGRESS'], 'ESCALATED', 'ESCALATE');
    }

    private function transitionAlert(Request $request, Alert $alert, array $allowed, string $newStatus, string $action): JsonResponse
    {
        $this->access->assertPatientAccess($request->user(), $alert->patient_id);
        $data = $request->validate(['comment' => ['nullable', 'string', 'max:1000']]);

        if (! in_array($alert->status, $allowed, true)) {
            throw ValidationException::withMessages(['status' => ["No se puede ejecutar {$action} desde el estado actual."]]);
        }

        DB::transaction(function () use ($alert, $newStatus, $action, $data, $request): void {
            $previousStatus = $alert->status;
            $alert->update(['status' => $newStatus]);
            AlertHistory::create([
                'alert_id' => $alert->id,
                'action' => $action,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'comment' => $data['comment'] ?? null,
                'user_id' => $request->user()->id,
            ]);
        });

        return $this->response(new NurseAlertResource($alert->fresh()->load('history')), Response::HTTP_OK, 'Alerta actualizada correctamente.');
    }

    private function treatmentsQuery(Patient $patient): Builder
    {
        return Treatment::query()->with(['diagnosis', 'treatmentMedications.medication'])->where('patient_id', $patient->id);
    }

    private function alertFilters(Request $request, bool $allowPatient = true): array
    {
        return $request->validate([
            'status' => ['nullable', Rule::in(['NEW', 'CLASSIFIED', 'ESCALATED', 'IN_PROGRESS', 'CLOSED'])],
            'severity' => ['nullable', Rule::in(['INFORMATIONAL', 'MODERATE', 'HIGH', 'CRITICAL'])],
            'patient_id' => $allowPatient ? ['nullable', 'integer'] : ['prohibited'],
        ]);
    }

    private function applyAlertFilters(Builder $query, array $filters): void
    {
        $query->when(isset($filters['status']), fn (Builder $q) => $q->where('status', $filters['status']))
            ->when(isset($filters['severity']), fn (Builder $q) => $q->where('severity', $filters['severity']))
            ->when(isset($filters['patient_id']), fn (Builder $q) => $q->where('patient_id', $filters['patient_id']));
    }

    private function response(mixed $data, int $status = Response::HTTP_OK, ?string $message = null): JsonResponse
    {
        return response()->json(['data' => $data, 'message' => $message, 'errors' => null], $status);
    }
}
