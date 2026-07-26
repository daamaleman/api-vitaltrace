<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTreatmentRequest;
use App\Http\Requests\UpdateTreatmentRequest;
use App\Http\Resources\TreatmentResource;
use App\Models\Treatment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * CRUD endpoints for treatments.
 */
class TreatmentController extends Controller
{
    /**
     * List paginated treatments with patient and diagnosis.
     */
    public function index(): AnonymousResourceCollection
    {
        $treatments = Treatment::with(['patient', 'diagnosis'])->latest('id')->paginate(15);

        return TreatmentResource::collection($treatments);
    }

    /**
     * Prescribe a new treatment.
     */
    public function store(StoreTreatmentRequest $request): JsonResponse
    {
        $treatment = Treatment::create($request->validated());

        return response()->json([
            'data' => new TreatmentResource($treatment->load(['patient', 'diagnosis'])),
            'message' => 'Treatment prescribed successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a single treatment.
     */
    public function show(Treatment $treatment): JsonResponse
    {
        return response()->json([
            'data' => new TreatmentResource($treatment->load(['patient', 'diagnosis'])),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Update an existing treatment.
     */
    public function update(UpdateTreatmentRequest $request, Treatment $treatment): JsonResponse
    {
        $treatment->update($request->validated());

        return response()->json([
            'data' => new TreatmentResource($treatment->load(['patient', 'diagnosis'])),
            'message' => 'Treatment updated successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Soft-delete a treatment.
     */
    public function destroy(Treatment $treatment): JsonResponse
    {
        $treatment->delete();

        return response()->json([
            'data' => null,
            'message' => 'Treatment removed successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
