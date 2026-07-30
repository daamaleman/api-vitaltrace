<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Resources\PatientPortalRelativeResource;
use App\Models\PatientRelative;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PatientRelativeAccessController extends Controller
{
    /**
     * List relatives linked to the authenticated patient's own record.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $patient = $request->user()->patient;

        if ($patient === null) {
            return response()->json([
                'data' => null,
                'message' => 'No patient profile is associated with this account.',
                'errors' => null,
            ], Response::HTTP_NOT_FOUND);
        }

        $relations = PatientRelative::with('relative.person')
            ->where('patient_id', $patient->id)
            ->latest('id')
            ->paginate(15);

        return PatientPortalRelativeResource::collection($relations);
    }

    /**
     * Authorize a relative for the authenticated patient.
     */
    public function grant(Request $request, PatientRelative $patientRelative): JsonResponse
    {
        $owned = $this->ensureOwnedByPatient($request, $patientRelative);

        if ($owned !== null) {
            return $owned;
        }

        $patientRelative->update([
            'status' => 'ACTIVE',
            'authorized_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'data' => new PatientPortalRelativeResource($patientRelative->load('relative.person')),
            'message' => 'Relative access authorized successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Revoke a relative authorization for the authenticated patient.
     */
    public function revoke(Request $request, PatientRelative $patientRelative): JsonResponse
    {
        $owned = $this->ensureOwnedByPatient($request, $patientRelative);

        if ($owned !== null) {
            return $owned;
        }

        $patientRelative->update([
            'status' => 'REVOKED',
            'end_date' => now(),
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'data' => new PatientPortalRelativeResource($patientRelative->load('relative.person')),
            'message' => 'Relative access revoked successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Ensure the patient-relative record belongs to the authenticated patient.
     */
    private function ensureOwnedByPatient(Request $request, PatientRelative $patientRelative): ?JsonResponse
    {
        $patient = $request->user()->patient;

        if ($patient === null || $patientRelative->patient_id !== $patient->id) {
            return response()->json([
                'data' => null,
                'message' => 'No patient profile is associated with this account.',
                'errors' => null,
            ], Response::HTTP_NOT_FOUND);
        }

        return null;
    }
}
