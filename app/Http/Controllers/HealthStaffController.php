<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreHealthStaffRequest;
use App\Http\Requests\UpdateHealthStaffRequest;
use App\Http\Resources\HealthStaffResource;
use App\Models\HealthStaff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * CRUD endpoints for health professionals (doctors and nurses).
 */
class HealthStaffController extends Controller
{
    /**
     * List paginated health staff with their person and specialty.
     */
    public function index(): AnonymousResourceCollection
    {
        $healthStaff = HealthStaff::latest('id')->paginate(15);

        return HealthStaffResource::collection($healthStaff);
    }

    /**
     * Register a new health professional.
     */
    public function store(StoreHealthStaffRequest $request): JsonResponse
    {
        $healthStaff = HealthStaff::create($request->validated());

        return response()->json([
            'data' => new HealthStaffResource($healthStaff->load(['person', 'specialty'])),
            'message' => 'Personal de salud registrado correctamente.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a single health professional.
     */
    public function show(HealthStaff $healthStaff): JsonResponse
    {
        return response()->json([
            'data' => new HealthStaffResource($healthStaff->load(['person', 'specialty'])),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Update an existing health professional.
     */
    public function update(UpdateHealthStaffRequest $request, HealthStaff $healthStaff): JsonResponse
    {
        $healthStaff->update($request->validated());

        return response()->json([
            'data' => new HealthStaffResource($healthStaff->load(['person', 'specialty'])),
            'message' => 'Personal de salud actualizado correctamente.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Soft-delete a health professional.
     */
    public function destroy(HealthStaff $healthStaff): JsonResponse
    {
        $healthStaff->delete();

        return response()->json([
            'data' => null,
            'message' => 'Personal de salud eliminado correctamente.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
