<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreClinicalRangeRequest;
use App\Http\Requests\UpdateClinicalRangeRequest;
use App\Http\Resources\ClinicalRangeResource;
use App\Models\ClinicalRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * CRUD endpoints for clinical ranges.
 */
class ClinicalRangeController extends Controller
{
    /**
     * List paginated clinical ranges with patient and measurement type.
     */
    public function index(): AnonymousResourceCollection
    {
        $ranges = ClinicalRange::with(['patient', 'measurementType'])->latest('id')->paginate(15);

        return ClinicalRangeResource::collection($ranges);
    }

    /**
     * Define a new clinical range.
     */
    public function store(StoreClinicalRangeRequest $request): JsonResponse
    {
        $range = ClinicalRange::create($request->validated());

        return response()->json([
            'data' => new ClinicalRangeResource($range->load(['patient', 'measurementType'])),
            'message' => 'Rango clínico creado correctamente.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a single clinical range.
     */
    public function show(ClinicalRange $clinicalRange): JsonResponse
    {
        return response()->json([
            'data' => new ClinicalRangeResource($clinicalRange->load(['patient', 'measurementType'])),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Update an existing clinical range.
     */
    public function update(UpdateClinicalRangeRequest $request, ClinicalRange $clinicalRange): JsonResponse
    {
        $clinicalRange->update($request->validated());

        return response()->json([
            'data' => new ClinicalRangeResource($clinicalRange->load(['patient', 'measurementType'])),
            'message' => 'Rango clínico actualizado correctamente.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Soft-delete a clinical range.
     */
    public function destroy(ClinicalRange $clinicalRange): JsonResponse
    {
        $clinicalRange->delete();

        return response()->json([
            'data' => null,
            'message' => 'Rango clínico eliminado correctamente.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
