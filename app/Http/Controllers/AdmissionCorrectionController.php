<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CorrectionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Admission-side correction request review (§8.5, RF-BE-09).
 *
 * Lists correction requests submitted by patients and lets Admission approve
 * (applying the change to the patient's person record) or reject them.
 */
class AdmissionCorrectionController extends Controller
{
    /**
     * Fields on the person record that a correction may target.
     *
     * @var array<int, string>
     */
    private const PERSON_FIELDS = [
        'phone', 'address', 'identity_document',
        'first_name', 'middle_name', 'first_last_name', 'second_last_name',
    ];

    /**
     * List correction requests, newest first, with patient context.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CorrectionRequest::with('patient.person')->latest('id');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return response()->json([
            'data' => $query->get(),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Approve a request, applying the change to the person record when the
     * field is a known person attribute.
     */
    public function approve(Request $request, CorrectionRequest $correctionRequest): JsonResponse
    {
        if ($correctionRequest->status !== 'PENDING') {
            return $this->notPending();
        }

        $data = $request->validate([
            'response' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($correctionRequest, $data, $request) {
            // Apply the change to the person record if it's a known field.
            if (in_array($correctionRequest->field, self::PERSON_FIELDS, true)) {
                $person = $correctionRequest->patient?->person;
                if ($person !== null) {
                    $person->update([
                        $correctionRequest->field => $correctionRequest->requested_value,
                    ]);
                }
            }

            $correctionRequest->update([
                'status' => 'APPROVED',
                'reviewed_by' => $request->user()->id,
                'response' => $data['response'] ?? 'Approved and applied.',
                'reviewed_at' => now(),
            ]);
        });

        return response()->json([
            'data' => $correctionRequest->fresh()->load('patient.person'),
            'message' => 'Correction approved and applied.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Reject a request with a response note.
     */
    public function reject(Request $request, CorrectionRequest $correctionRequest): JsonResponse
    {
        if ($correctionRequest->status !== 'PENDING') {
            return $this->notPending();
        }

        $data = $request->validate([
            'response' => ['required', 'string', 'max:1000'],
        ]);

        $correctionRequest->update([
            'status' => 'REJECTED',
            'reviewed_by' => $request->user()->id,
            'response' => $data['response'],
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'data' => $correctionRequest->fresh()->load('patient.person'),
            'message' => 'Correction rejected.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Standard response when a request is no longer pending.
     */
    private function notPending(): JsonResponse
    {
        return response()->json([
            'data' => null,
            'message' => 'This request has already been resolved.',
            'errors' => ['status' => ['Not pending.']],
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}