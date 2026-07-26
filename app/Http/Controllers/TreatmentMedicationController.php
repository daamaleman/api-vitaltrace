<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTreatmentMedicationRequest;
use App\Http\Requests\UpdateTreatmentMedicationRequest;
use App\Http\Resources\TreatmentMedicationResource;
use App\Models\TreatmentMedication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * CRUD endpoints for treatment medication details.
 */
class TreatmentMedicationController extends Controller
{
    /**
     * List paginated prescription details with treatment and medication.
     */
    public function index(): AnonymousResourceCollection
    {
        $details = TreatmentMedication::with(['treatment', 'medication'])->latest('id')->paginate(15);

        return TreatmentMedicationResource::collection($details);
    }

    /**
     * Add a medication to a treatment.
     */
    public function store(StoreTreatmentMedicationRequest $request): JsonResponse
    {
        $detail = TreatmentMedication::create($request->validated());

        return response()->json([
            'data' => new TreatmentMedicationResource($detail->load(['treatment', 'medication'])),
            'message' => 'Treatment medication added successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a single treatment medication detail.
     */
    public function show(TreatmentMedication $treatmentMedication): JsonResponse
    {
        return response()->json([
            'data' => new TreatmentMedicationResource($treatmentMedication->load(['treatment', 'medication'])),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Update an existing treatment medication detail.
     */
    public function update(UpdateTreatmentMedicationRequest $request, TreatmentMedication $treatmentMedication): JsonResponse
    {
        $treatmentMedication->update($request->validated());

        return response()->json([
            'data' => new TreatmentMedicationResource($treatmentMedication->load(['treatment', 'medication'])),
            'message' => 'Treatment medication updated successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Soft-delete a treatment medication detail.
     */
    public function destroy(TreatmentMedication $treatmentMedication): JsonResponse
    {
        $treatmentMedication->delete();

        return response()->json([
            'data' => null,
            'message' => 'Treatment medication removed successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
