<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreIntegrationLogRequest;
use App\Http\Resources\IntegrationLogResource;
use App\Models\IntegrationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Read and append endpoints for the immutable integration log.
 *
 * Only index, store and show are exposed; update and destroy are intentionally
 * absent because the log is append-only.
 */
class IntegrationLogController extends Controller
{
    /**
     * List paginated integration log entries.
     */
    public function index(): AnonymousResourceCollection
    {
        $logs = IntegrationLog::latest('id')->paginate(15);

        return IntegrationLogResource::collection($logs);
    }

    /**
     * Append a new integration log entry.
     */
    public function store(StoreIntegrationLogRequest $request): JsonResponse
    {
        $log = IntegrationLog::create($request->validated());

        return response()->json([
            'data' => new IntegrationLogResource($log),
            'message' => 'Entrada del registro de integración creada correctamente.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a single integration log entry.
     */
    public function show(IntegrationLog $integrationLog): JsonResponse
    {
        return response()->json([
            'data' => new IntegrationLogResource($integrationLog),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
