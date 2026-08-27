<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlertRequest;
use App\Http\Requests\UpdateAlertRequest;
use App\Http\Resources\AlertResource;
use App\Models\Alert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * CRUD endpoints for alerts.
 */
class AlertController extends Controller
{
    /**
     * List paginated alerts with patient and originating measurement.
     */
    public function index(): AnonymousResourceCollection
    {
        $alerts = Alert::with(['patient.person', 'measurement'])->latest('id')->paginate(15);

        return AlertResource::collection($alerts);
    }

    /**
     * Create a new alert.
     */
    public function store(StoreAlertRequest $request): JsonResponse
    {
        $alert = Alert::create($request->validated());

        return response()->json([
            'data' => new AlertResource($alert->load(['patient.person', 'measurement'])),
            'message' => 'Alerta creada correctamente.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a single alert.
     */
    public function show(Alert $alert): JsonResponse
    {
        return response()->json([
            'data' => new AlertResource($alert->load(['patient.person', 'measurement'])),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Update an existing alert.
     */
    public function update(UpdateAlertRequest $request, Alert $alert): JsonResponse
    {
        $alert->update($request->validated());

        return response()->json([
            'data' => new AlertResource($alert->load(['patient.person', 'measurement'])),
            'message' => 'Alerta actualizada correctamente.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Soft-delete an alert.
     */
    public function destroy(Alert $alert): JsonResponse
    {
        $alert->delete();

        return response()->json([
            'data' => null,
            'message' => 'Alerta eliminada correctamente.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
