<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * CRUD endpoints for appointments.
 */
class AppointmentController extends Controller
{
    /**
     * List paginated appointments with patient and professional.
     */
    public function index(): AnonymousResourceCollection
    {
        $appointments = Appointment::with(['patient', 'healthStaff'])->latest('id')->paginate(15);

        return AppointmentResource::collection($appointments);
    }

    /**
     * Schedule a new appointment.
     */
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $appointment = Appointment::create($request->validated());

        return response()->json([
            'data' => new AppointmentResource($appointment->load(['patient', 'healthStaff'])),
            'message' => 'Appointment scheduled successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a single appointment.
     */
    public function show(Appointment $appointment): JsonResponse
    {
        return response()->json([
            'data' => new AppointmentResource($appointment->load(['patient', 'healthStaff'])),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Update an existing appointment.
     */
    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        $appointment->update($request->validated());

        return response()->json([
            'data' => new AppointmentResource($appointment->load(['patient', 'healthStaff'])),
            'message' => 'Appointment updated successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Soft-delete an appointment.
     */
    public function destroy(Appointment $appointment): JsonResponse
    {
        $appointment->delete();

        return response()->json([
            'data' => null,
            'message' => 'Appointment removed successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
