<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlertHistoryRequest;
use App\Http\Resources\AlertHistoryResource;
use App\Models\AlertHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Read and append endpoints for the immutable alert history.
 *
 * Only index, store and show are exposed; update and destroy are intentionally
 * absent because the log is append-only.
 */
class AlertHistoryController extends Controller
{
    /**
     * List paginated alert history entries with their alert.
     */
    public function index(): AnonymousResourceCollection
    {
        $history = AlertHistory::with('alert')->latest('id')->paginate(15);

        return AlertHistoryResource::collection($history);
    }

    /**
     * Append a new alert history entry.
     */
    public function store(StoreAlertHistoryRequest $request): JsonResponse
    {
        $entry = AlertHistory::create($request->validated());

        return response()->json([
            'data' => new AlertHistoryResource($entry->load('alert')),
            'message' => 'Entrada del historial de alerta registrada correctamente.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a single alert history entry.
     */
    public function show(AlertHistory $alertHistory): JsonResponse
    {
        return response()->json([
            'data' => new AlertHistoryResource($alertHistory->load('alert')),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
