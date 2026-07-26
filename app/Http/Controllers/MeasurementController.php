<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreMeasurementRequest;
use App\Http\Requests\UpdateMeasurementRequest;
use App\Http\Resources\MeasurementResource;
use App\Models\Measurement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * CRUD endpoints for patient measurements.
 */
class MeasurementController extends Controller
{
    /**
     * List paginated measurements with patient and type.
     */
    public function index(): AnonymousResourceCollection
    {
        $measurements = Measurement::with(['patient', 'measurementType'])->latest('id')->paginate(15);

        return MeasurementResource::collection($measurements);
    }

    /**
     * Register a new measurement.
     */
    public function store(StoreMeasurementRequest $request): JsonResponse
    {
        $measurement = Measurement::create($request->validated());

        return response()->json([
            'data' => new MeasurementResource($measurement->load(['patient', 'measurementType'])),
            'message' => 'Measurement registered successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a single measurement.
     */
    public function show(Measurement $measurement): JsonResponse
    {
        return response()->json([
            'data' => new MeasurementResource($measurement->load(['patient', 'measurementType'])),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Update an existing measurement.
     */
    public function update(UpdateMeasurementRequest $request, Measurement $measurement): JsonResponse
    {
        $measurement->update($request->validated());

        return response()->json([
            'data' => new MeasurementResource($measurement->load(['patient', 'measurementType'])),
            'message' => 'Measurement updated successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Soft-delete a measurement.
     */
    public function destroy(Measurement $measurement): JsonResponse
    {
        $measurement->delete();

        return response()->json([
            'data' => null,
            'message' => 'Measurement removed successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
