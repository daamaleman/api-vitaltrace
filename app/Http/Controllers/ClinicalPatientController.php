<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\PatientResource;
use App\Models\HealthStaff;
use App\Models\Patient;
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
     * Resolve the health_staff profile for the authenticated user.
     */
    private function resolveStaff(Request $request): ?HealthStaff
    {
        return HealthStaff::where('person_id', $request->user()->person_id)->first();
    }
}