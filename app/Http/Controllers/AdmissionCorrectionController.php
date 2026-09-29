<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CorrectionRequest;
use App\Models\Patient;
use App\Http\Resources\PatientResource;
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

    /**
     * Direct administrative correction (presential flow, RN-01).
     * Only Admission edits authorized administrative fields, with a mandatory
     * reason. Records each changed field in correction_requests as an approved
     * correction and updates person/patient transactionally.
     */
    public function correct(Request $request, Patient $patient): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            // Campos administrativos autorizados (todos opcionales; se corrige lo que venga)
            'first_name' => ['sometimes', 'string', 'max:80'],
            'middle_name' => ['sometimes', 'nullable', 'string', 'max:80'],
            'first_last_name' => ['sometimes', 'string', 'max:80'],
            'second_last_name' => ['sometimes', 'nullable', 'string', 'max:80'],
            'identity_document' => ['sometimes', 'nullable', 'string', 'max:40'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:25'],
            'address' => ['sometimes', 'nullable', 'string', 'max:200'],
            'emergency_contact_name' => ['sometimes', 'nullable', 'string', 'max:160'],
            'emergency_contact_phone' => ['sometimes', 'nullable', 'string', 'max:25'],
            'administrative_notes' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $reason = $data['reason'];
        unset($data['reason']);

        // Mapa de a qué modelo pertenece cada campo autorizado.
        $personFields = ['first_name','middle_name','first_last_name','second_last_name','identity_document','phone','address'];
        $patientFields = ['emergency_contact_name','emergency_contact_phone','administrative_notes'];

        $patient->load('person');
        $person = $patient->person;
        $changes = [];

        foreach ($data as $field => $newValue) {
            $current = in_array($field, $personFields, true) ? $person->{$field} : $patient->{$field};
            // Normaliza para comparar (null vs '')
            if ((string) $current !== (string) $newValue) {
                $changes[$field] = ['old' => $current, 'new' => $newValue];
            }
        }

        if (count($changes) === 0) {
            return response()->json([
                'data' => null,
                'message' => 'No se detectaron cambios para corregir.',
                'errors' => ['fields' => ['Sin cambios.']],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::transaction(function () use ($changes, $person, $patient, $personFields, $reason, $request) {
            foreach ($changes as $field => $vals) {
                if (in_array($field, $personFields, true)) {
                    $person->{$field} = $vals['new'];
                } else {
                    $patient->{$field} = $vals['new'];
                }

                // Registro de la corrección (aprobada, presencial).
                \App\Models\CorrectionRequest::create([
                    'patient_id' => $patient->id,
                    'requested_by' => $request->user()->id,
                    'field' => $field,
                    'current_value' => (string) ($vals['old'] ?? ''),
                    'requested_value' => (string) ($vals['new'] ?? ''),
                    'reason' => $reason,
                    'status' => 'APPROVED',
                    'reviewed_by' => $request->user()->id,
                    'response' => 'Corrección administrativa presencial.',
                    'reviewed_at' => now(),
                ]);
            }
            $person->save();
            $patient->save();
        });

        return response()->json([
            'data' => new PatientResource($patient->fresh()->load('person')),
            'message' => 'Corrección administrativa aplicada correctamente.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}