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
use Illuminate\Validation\ValidationException;

/**
 * State-transition actions for alerts: classify, escalate and close.
 *
 * Each action changes the alert status and records an immutable entry in
 * alert_history within a single transaction. An alert is a follow-up signal,
 * never a diagnosis.
 */
class AlertActionController extends Controller
{
    /**
     * Classify a NEW alert (mark it as triaged).
     */
    public function classify(Request $request, Alert $alert): JsonResponse
    {
        $data = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->guardTransition($alert, ['NEW'], 'classify');

        return $this->applyTransition($alert, 'CLASSIFIED', 'CLASSIFY', $data['comment'] ?? null);
    }

    /**
     * Escalate an alert to require higher attention.
     */
    public function escalate(Request $request, Alert $alert): JsonResponse
    {
        $data = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->guardTransition($alert, ['NEW', 'CLASSIFIED', 'IN_PROGRESS'], 'escalate');

        return $this->applyTransition($alert, 'ESCALATED', 'ESCALATE', $data['comment'] ?? null);
    }

    /**
     * Close an alert after review.
     */
    public function close(Request $request, Alert $alert): JsonResponse
    {
        $data = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->guardTransition($alert, ['NEW', 'CLASSIFIED', 'ESCALATED', 'IN_PROGRESS'], 'close');

        return $this->applyTransition(
            $alert,
            'CLOSED',
            'CLOSE',
            $data['comment'] ?? null,
            closing: true,
        );
    }

    /**
     * Reject the action if the alert is not in an allowed source status.
     *
     * @param  array<int, string>  $allowedFrom
     */
    private function guardTransition(Alert $alert, array $allowedFrom, string $action): void
    {
        if (! in_array($alert->status, $allowedFrom, true)) {
            throw ValidationException::withMessages([
                'status' => ["Cannot {$action} an alert in status {$alert->status}."],
            ]);
        }
    }

    /**
     * Apply the status change and record the history entry atomically.
     */
    private function applyTransition(
        Alert $alert,
        string $newStatus,
        string $action,
        ?string $comment,
        bool $closing = false,
    ): JsonResponse {
        $previousStatus = $alert->status;

        DB::transaction(function () use ($alert, $newStatus, $action, $comment, $previousStatus, $closing) {
            $alert->status = $newStatus;

            if ($closing) {
                $alert->closed_at = now();
            }

            $alert->save();

            AlertHistory::create([
                'alert_id' => $alert->id,
                'action' => $action,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'comment' => $comment,
                'user_id' => request()->user()->id,
            ]);
        });

        return response()->json([
            'data' => new AlertResource($alert->fresh()->load(['patient.person', 'measurement'])),
            'message' => 'Alert updated successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}