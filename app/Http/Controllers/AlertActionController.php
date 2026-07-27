<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\AlertResource;
use App\Models\Alert;
use App\Models\AlertHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Alert workflow actions (RF-BE-14): classify, escalate and close.
 *
 * Each transition records an immutable alert_history entry in the same
 * transaction to preserve traceability.
 */
class AlertActionController extends Controller
{
    /**
     * Classify a new alert.
     */
    public function classify(Request $request, Alert $alert): JsonResponse
    {
        return $this->transition($request, $alert, 'CLASSIFIED', 'CLASSIFY');
    }

    /**
     * Escalate an alert to a doctor.
     */
    public function escalate(Request $request, Alert $alert): JsonResponse
    {
        return $this->transition($request, $alert, 'ESCALATED', 'ESCALATE');
    }

    /**
     * Close an alert.
     */
    public function close(Request $request, Alert $alert): JsonResponse
    {
        return $this->transition($request, $alert, 'CLOSED', 'CLOSE');
    }

    /**
     * Apply a status transition and log it in alert_history atomically.
     */
    private function transition(Request $request, Alert $alert, string $newStatus, string $action): JsonResponse
    {
        $comment = $request->input('comment');

        $alert = DB::transaction(function () use ($alert, $newStatus, $action, $comment, $request) {
            $previousStatus = $alert->status;

            $alert->update([
                'status' => $newStatus,
                'closed_at' => $newStatus === 'CLOSED' ? now() : $alert->closed_at,
            ]);

            AlertHistory::create([
                'alert_id' => $alert->id,
                'action' => $action,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'comment' => $comment,
                'user_id' => $request->user()->id,
            ]);

            return $alert;
        });

        return response()->json([
            'data' => new AlertResource($alert->load(['patient', 'measurement'])),
            'message' => 'Alert updated successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
