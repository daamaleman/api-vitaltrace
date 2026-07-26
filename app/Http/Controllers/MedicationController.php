<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicationRequest;
use App\Http\Requests\UpdateMedicationRequest;
use App\Http\Resources\MedicationResource;
use App\Models\Medication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * CRUD endpoints for the medication catalog.
 */
class MedicationController extends Controller
{
    /**
     * List paginated medications ordered by generic name.
     */
    public function index(): AnonymousResourceCollection
    {
        $medications = Medication::orderBy('generic_name')->paginate(15);

        return MedicationResource::collection($medications);
    }

    /**
     * Create a new medication catalog entry.
     */
    public function store(StoreMedicationRequest $request): JsonResponse
    {
        $medication = Medication::create($request->validated());

        return response()->json([
            'data' => new MedicationResource($medication),
            'message' => 'Medication created successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a single medication.
     */
    public function show(Medication $medication): JsonResponse
    {
        return response()->json([
            'data' => new MedicationResource($medication),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Update an existing medication.
     */
    public function update(UpdateMedicationRequest $request, Medication $medication): JsonResponse
    {
        $medication->update($request->validated());

        return response()->json([
            'data' => new MedicationResource($medication),
            'message' => 'Medication updated successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Delete a medication.
     */
    public function destroy(Medication $medication): JsonResponse
    {
        $medication->delete();

        return response()->json([
            'data' => null,
            'message' => 'Medication deleted successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
