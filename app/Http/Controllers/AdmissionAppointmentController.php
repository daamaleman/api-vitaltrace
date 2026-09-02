<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Admission-side appointment management. Unlike the doctor flow, admission
 * schedules for ANY patient with ANY professional (no assignment scoping),
 * so patient_id and health_staff_id are validated inputs.
 */
class AdmissionAppointmentController extends Controller
{
    /**
     * List appointments, optionally filtered by patient or status.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Appointment::with(['patient.person', 'healthStaff.person'])
            ->orderBy('scheduled_at', 'desc');

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->integer('patient_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json([
            'data' => $query->limit(100)->get(),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Create an appointment for any patient with any professional.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'health_staff_id' => ['required', 'integer', 'exists:health_staff,id'],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $appointment = Appointment::create([
            'patient_id' => $data['patient_id'],
            'health_staff_id' => $data['health_staff_id'],
            'scheduled_at' => $data['scheduled_at'],
            'duration_minutes' => $data['duration_minutes'] ?? 30,
            'reason' => $data['reason'],
            'status' => 'SCHEDULED',
            'external_sync' => 'NOT_APPLICABLE',
        ]);

        return response()->json([
            'data' => $appointment,
            'message' => 'Cita agendada correctamente.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Reschedule an appointment (date, duration, reason, professional).
     */
    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'reason' => ['required', 'string', 'max:255'],
            'health_staff_id' => ['nullable', 'integer', 'exists:health_staff,id'],
        ]);

        $appointment->update([
            'scheduled_at' => $data['scheduled_at'],
            'duration_minutes' => $data['duration_minutes'] ?? $appointment->duration_minutes,
            'reason' => $data['reason'],
            'health_staff_id' => $data['health_staff_id'] ?? $appointment->health_staff_id,
        ]);

        return response()->json([
            'data' => $appointment,
            'message' => 'Cita reprogramada correctamente.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Change appointment status (confirm, attend, cancel, no-show).
     */
    public function status(Request $request, Appointment $appointment): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:SCHEDULED,CONFIRMED,ATTENDED,CANCELLED,NO_SHOW'],
        ]);

        $appointment->update(['status' => $data['status']]);

        return response()->json([
            'data' => $appointment,
            'message' => 'Estado de la cita actualizado.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}