<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\HealthStaff;
use App\Models\Patient;
use App\Models\ProfessionalAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Admission-side professional assignments (§8.4).
 *
 * Assigns doctors and nurses to patients. Enforces a single active
 * PRIMARY_DOCTOR per patient.
 */
class AdmissionAssignmentController extends Controller
{
    /**
     * List assignments for a patient, with the professional's data.
     */
    public function index(Patient $patient): JsonResponse
    {
        $assignments = ProfessionalAssignment::with('healthStaff.person')
            ->where('patient_id', $patient->id)
            ->latest('id')
            ->get();

        return response()->json([
            'data' => $assignments,
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Available active health staff to assign.
     */
    public function availableStaff(): JsonResponse
    {
        $staff = HealthStaff::with('person')->where('active', true)->get();

        return response()->json([
            'data' => $staff,
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Create an assignment, enforcing a single active primary doctor.
     */
    public function store(Request $request, Patient $patient): JsonResponse
    {
        $data = $request->validate([
            'health_staff_id' => ['required', 'integer', 'exists:health_staff,id'],
            'assignment_type' => ['required', 'string', 'in:PRIMARY_DOCTOR,SECONDARY_DOCTOR,NURSE'],
        ]);

        // Single active primary doctor per patient.
        if ($data['assignment_type'] === 'PRIMARY_DOCTOR') {
            $hasPrimary = ProfessionalAssignment::where('patient_id', $patient->id)
                ->where('assignment_type', 'PRIMARY_DOCTOR')
                ->where('status', 'ACTIVE')
                ->exists();

            if ($hasPrimary) {
                return response()->json([
                    'data' => null,
                    'message' => 'The patient already has an active primary doctor. Finish it before assigning a new one.',
                    'errors' => ['assignment_type' => ['An active primary doctor already exists.']],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        // Prevent duplicate active assignment of the same staff.
        $duplicate = ProfessionalAssignment::where('patient_id', $patient->id)
            ->where('health_staff_id', $data['health_staff_id'])
            ->where('status', 'ACTIVE')
            ->exists();

        if ($duplicate) {
            return response()->json([
                'data' => null,
                'message' => 'This professional is already actively assigned to the patient.',
                'errors' => ['health_staff_id' => ['Already assigned.']],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $assignment = ProfessionalAssignment::create([
            'patient_id' => $patient->id,
            'health_staff_id' => $data['health_staff_id'],
            'assignment_type' => $data['assignment_type'],
            'start_date' => now()->toDateString(),
            'status' => 'ACTIVE',
            'assigned_by' => $request->user()->id,
        ]);

        return response()->json([
            'data' => $assignment->load('healthStaff.person'),
            'message' => 'Assignment created successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Finish (deactivate) an assignment, keeping history.
     */
    public function finish(Request $request, ProfessionalAssignment $professionalAssignment): JsonResponse
    {
        $professionalAssignment->update([
            'status' => 'FINISHED',
            'end_date' => now()->toDateString(),
            'change_reason' => $request->input('reason'),
        ]);

        return response()->json([
            'data' => $professionalAssignment->fresh()->load('healthStaff.person'),
            'message' => 'Assignment finished.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}