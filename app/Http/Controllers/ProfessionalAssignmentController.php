<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreProfessionalAssignmentRequest;
use App\Http\Requests\UpdateProfessionalAssignmentRequest;
use App\Http\Resources\ProfessionalAssignmentResource;
use App\Models\ProfessionalAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * CRUD endpoints for professional assignments.
 *
 * Writes are wrapped in transactions so the single-active-primary-doctor
 * validation and the persistence stay atomic.
 */
class ProfessionalAssignmentController extends Controller
{
    /**
     * List paginated assignments with patient and professional.
     */
    public function index(): AnonymousResourceCollection
    {
        $assignments = ProfessionalAssignment::with(['patient', 'healthStaff'])
            ->latest('id')
            ->paginate(15);

        return ProfessionalAssignmentResource::collection($assignments);
    }

    /**
     * Create a new professional assignment.
     */
    public function store(StoreProfessionalAssignmentRequest $request): JsonResponse
    {
        $assignment = DB::transaction(function () use ($request) {
            return ProfessionalAssignment::create($request->validated());
        });

        return response()->json([
            'data' => new ProfessionalAssignmentResource($assignment->load(['patient', 'healthStaff'])),
            'message' => 'Professional assignment created successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a single professional assignment.
     */
    public function show(ProfessionalAssignment $professionalAssignment): JsonResponse
    {
        return response()->json([
            'data' => new ProfessionalAssignmentResource($professionalAssignment->load(['patient', 'healthStaff'])),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Update an existing professional assignment.
     */
    public function update(UpdateProfessionalAssignmentRequest $request, ProfessionalAssignment $professionalAssignment): JsonResponse
    {
        $assignment = DB::transaction(function () use ($request, $professionalAssignment) {
            $professionalAssignment->update($request->validated());

            return $professionalAssignment;
        });

        return response()->json([
            'data' => new ProfessionalAssignmentResource($assignment->load(['patient', 'healthStaff'])),
            'message' => 'Professional assignment updated successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Soft-delete a professional assignment.
     */
    public function destroy(ProfessionalAssignment $professionalAssignment): JsonResponse
    {
        $professionalAssignment->delete();

        return response()->json([
            'data' => null,
            'message' => 'Professional assignment removed successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
