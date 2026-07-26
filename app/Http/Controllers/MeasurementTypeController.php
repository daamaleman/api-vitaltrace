<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreMeasurementTypeRequest;
use App\Http\Requests\UpdateMeasurementTypeRequest;
use App\Http\Resources\MeasurementTypeResource;
use App\Models\MeasurementType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * CRUD endpoints for the measurement types catalog.
 */
class MeasurementTypeController extends Controller
{
    /**
     * List paginated measurement types ordered by name.
     */
    public function index(): AnonymousResourceCollection
    {
        $types = MeasurementType::orderBy('name')->paginate(15);

        return MeasurementTypeResource::collection($types);
    }

    /**
     * Create a new measurement type.
     */
    public function store(StoreMeasurementTypeRequest $request): JsonResponse
    {
        $type = MeasurementType::create($request->validated());

        return response()->json([
            'data' => new MeasurementTypeResource($type),
            'message' => 'Measurement type created successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a single measurement type.
     */
    public function show(MeasurementType $measurementType): JsonResponse
    {
        return response()->json([
            'data' => new MeasurementTypeResource($measurementType),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Update an existing measurement type.
     */
    public function update(UpdateMeasurementTypeRequest $request, MeasurementType $measurementType): JsonResponse
    {
        $measurementType->update($request->validated());

        return response()->json([
            'data' => new MeasurementTypeResource($measurementType),
            'message' => 'Measurement type updated successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Delete a measurement type.
     */
    public function destroy(MeasurementType $measurementType): JsonResponse
    {
        $measurementType->delete();

        return response()->json([
            'data' => null,
            'message' => 'Measurement type deleted successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
