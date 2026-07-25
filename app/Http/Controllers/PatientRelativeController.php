<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRelativeRequest;
use App\Http\Requests\UpdatePatientRelativeRequest;
use App\Http\Resources\PatientRelativeResource;
use App\Models\PatientRelative;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Handle CRUD operations for patient-relative relationships.
 */
class PatientRelativeController extends Controller
{
    /**
     * List patient-relative relationships with patient and relative data.
     */
    public function index(): AnonymousResourceCollection
    {
        $relations = PatientRelative::with(['patient', 'relative'])->latest('id')->paginate(15);

        return PatientRelativeResource::collection($relations);
    }

    /**
     * Create a new patient-relative relationship.
     */
    public function store(StorePatientRelativeRequest $request): JsonResponse
    {
        $relation = DB::transaction(function () use ($request) {
            return PatientRelative::create($request->validated());
        });

        return response()->json([
            'data' => new PatientRelativeResource($relation->load(['patient', 'relative'])),
            'message' => 'Relative linked to patient successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a specific patient-relative relationship.
     */
    public function show(PatientRelative $patientRelative): JsonResponse
    {
        return response()->json([
            'data' => new PatientRelativeResource($patientRelative->load(['patient', 'relative'])),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Update an existing patient-relative relationship.
     */
    public function update(UpdatePatientRelativeRequest $request, PatientRelative $patientRelative): JsonResponse
    {
        $relation = DB::transaction(function () use ($request, $patientRelative) {
            $patientRelative->update($request->validated());

            return $patientRelative;
        });

        return response()->json([
            'data' => new PatientRelativeResource($relation->load(['patient', 'relative'])),
            'message' => 'Patient relative updated successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Remove a patient-relative relationship.
     */
    public function destroy(PatientRelative $patientRelative): JsonResponse
    {
        $patientRelative->delete();

        return response()->json([
            'data' => null,
            'message' => 'Patient relative removed successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
