<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\PatientResource;
use App\Models\HealthStaff;
use App\Models\Patient;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Read-only patient access for clinical staff (Doctor / Nurse).
 *
 * Enforces RN-06: a clinician only sees patients with an active
 * professional assignment to them. Administrative editing stays with
 * the Admission role.
 */
class ClinicalPatientController extends Controller
{
    /**
     * List patients actively assigned to the authenticated clinician.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $staff = $this->resolveStaff($request);

        if ($staff === null) {
            return response()->json([
                'data' => [],
                'message' => 'No clinical profile linked to this account.',
                'errors' => null,
            ], Response::HTTP_OK);
        }

        $patients = Patient::whereHas('professionalAssignments', function ($query) use ($staff) {
            $query->where('health_staff_id', $staff->id)
                ->where('status', 'ACTIVE');
        })
            ->with('person')
            ->orderBy('id')
            ->paginate(15);

        return PatientResource::collection($patients);
    }

    /**
     * Show a single assigned patient, or 403 if not assigned to the clinician.
     */
    public function show(Request $request, Patient $patient): JsonResponse
    {
        $staff = $this->resolveStaff($request);

        $isAssigned = $staff !== null && $patient->professionalAssignments()
            ->where('health_staff_id', $staff->id)
            ->where('status', 'ACTIVE')
            ->exists();

        if (! $isAssigned) {
            return response()->json([
                'data' => null,
                'message' => 'You do not have access to this patient.',
                'errors' => null,
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'data' => new PatientResource($patient->load('person')),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Full clinical summary for an assigned patient: diagnoses, evolutions,
     * treatments and recent measurements. Read-only, enforcing RN-06.
     */
    public function summary(Request $request, Patient $patient): JsonResponse
    {
        $staff = $this->resolveStaff($request);

        $isAssigned = $staff !== null && $patient->professionalAssignments()
            ->where('health_staff_id', $staff->id)
            ->where('status', 'ACTIVE')
            ->exists();

        if (! $isAssigned) {
            return response()->json([
                'data' => null,
                'message' => 'You do not have access to this patient.',
                'errors' => null,
            ], Response::HTTP_FORBIDDEN);
        }

        $patient->load('person');

        $diagnoses = \App\Models\Diagnosis::where('patient_id', $patient->id)
            ->latest('diagnosis_date')->get();

        $evolutions = \App\Models\ClinicalEvolution::where('patient_id', $patient->id)
            ->latest('recorded_at')->get();

        $treatments = \App\Models\Treatment::where('patient_id', $patient->id)
            ->latest('start_date')->get();

        $measurements = \App\Models\Measurement::where('patient_id', $patient->id)
            ->with('measurementType')
            ->latest('measured_at')->limit(30)->get();

        return response()->json([
            'data' => [
                'patient' => new PatientResource($patient),
                'diagnoses' => $diagnoses,
                'evolutions' => $evolutions,
                'treatments' => $treatments,
                'measurements' => $measurements,
            ],
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Appointments for the authenticated clinician, ordered by schedule.
     */
    public function appointments(Request $request): JsonResponse
    {
        $staff = $this->resolveStaff($request);

        if ($staff === null) {
            return response()->json([
                'data' => [],
                'message' => 'No clinical profile linked to this account.',
                'errors' => null,
            ], Response::HTTP_OK);
        }

        $appointments = \App\Models\Appointment::where('health_staff_id', $staff->id)
            ->with('patient.person')
            ->orderBy('scheduled_at')
            ->get();

        return response()->json([
            'data' => $appointments,
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Create an appointment for an assigned patient (doctor as professional).
     */
    public function storeAppointment(Request $request, Patient $patient): JsonResponse
    {
        $staff = $this->resolveStaff($request);

        if ($staff === null) {
            return response()->json([
                'data' => null, 
                'message' => 'Sin perfil clínico vinculado.', 
                'errors' => null
            ], Response::HTTP_FORBIDDEN);
        }

        // RN-06: el paciente debe estar asignado a este profesional.
        $assigned = \App\Models\ProfessionalAssignment::where('patient_id', $patient->id)
            ->where('health_staff_id', $staff->id)
            ->where('status', 'ACTIVE')
            ->exists();

        if (! $assigned) {
            return response()->json([
                'data' => null, 
                'message' => 'No tienes permiso para agendar en este paciente.', 
                'errors' => null
            ], Response::HTTP_FORBIDDEN);
        }

        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'health_staff_id' => $staff->id,
            'scheduled_at' => $data['scheduled_at'],
            'duration_minutes' => $data['duration_minutes'] ?? 30,
            'reason' => $data['reason'],
            'status' => 'SCHEDULED',
            'external_sync' => 'NOT_APPLICABLE',
        ]);

        return response()->json([
            'data' => $appointment, 
            'message' => 'Cita agendada correctamente.', 
            'errors' => null
        ], Response::HTTP_CREATED);
    }

    /**
     * Reschedule an appointment that belongs to this professional.
     */
    public function updateAppointment(Request $request, Appointment $appointment): JsonResponse
    {
        $staff = $this->resolveStaff($request);

        if ($staff === null || $appointment->health_staff_id !== $staff->id) {
            return response()->json([
                'data' => null, 
                'message' => 'No tienes permiso sobre esta cita.', 
                'errors' => null
            ], Response::HTTP_FORBIDDEN);
        }

        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $appointment->update([
            'scheduled_at' => $data['scheduled_at'],
            'duration_minutes' => $data['duration_minutes'] ?? $appointment->duration_minutes,
            'reason' => $data['reason'],
        ]);

        return response()->json([
            'data' => $appointment, 
            'message' => 'Cita reprogramada correctamente.', 
            'errors' => null
        ], Response::HTTP_OK);
    }

    /**
     * Change the status of an appointment that belongs to this professional.
     */
    public function appointmentStatus(Request $request, Appointment $appointment): JsonResponse
    {
        $staff = $this->resolveStaff($request);

        if ($staff === null || $appointment->health_staff_id !== $staff->id) {
            return response()->json([
                'data' => null, 
                'message' => 'No tienes permiso sobre esta cita.', 
                'errors' => null
            ], Response::HTTP_FORBIDDEN);
        }

        $data = $request->validate([
            'status' => ['required', 'string', 'in:SCHEDULED,CONFIRMED,ATTENDED,CANCELLED,NO_SHOW'],
        ]);

        $appointment->update(['status' => $data['status']]);

        return response()->json([
            'data' => $appointment, 
            'message' => 'Estado de la cita actualizado.', 
            'errors' => null
        ], Response::HTTP_OK);
    }

    /**
     * Resolve the health_staff profile for the authenticated user.
     */
    private function resolveStaff(Request $request): ?HealthStaff
    {
        return HealthStaff::where('person_id', $request->user()->person_id)->first();
    }
}