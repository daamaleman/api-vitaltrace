<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAuditLogRequest;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Read and append endpoints for the immutable audit trail.
 *
 * Only index, store and show are exposed; update and destroy are intentionally
 * absent because the trail is append-only and read-only for the system admin.
 */
class AuditLogController extends Controller
{
    /**
     * List paginated audit log entries with optional filters
     * (user, action, table, record and date range).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $logs = AuditLog::with('user')
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')))
            ->when($request->filled('table'), fn ($q) => $q->where('table', $request->string('table')))
            ->when($request->filled('record_id'), fn ($q) => $q->where('record_id', $request->integer('record_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return AuditLogResource::collection($logs);
    }

    /**
     * Append a new audit log entry.
     */
    public function store(StoreAuditLogRequest $request): JsonResponse
    {
        $log = AuditLog::create($request->validated());

        return response()->json([
            'data' => new AuditLogResource($log->load('user')),
            'message' => 'Audit log entry recorded successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a single audit log entry.
     */
    public function show(AuditLog $auditLog): JsonResponse
    {
        return response()->json([
            'data' => new AuditLogResource($auditLog->load('user')),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }
}