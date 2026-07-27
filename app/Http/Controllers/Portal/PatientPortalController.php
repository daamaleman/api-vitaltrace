<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCorrectionRequestRequest;
use App\Http\Requests\StoreMeasurementRequest;
use App\Http\Resources\CorrectionRequestResource;
use App\Http\Resources\MeasurementResource;
use App\Models\CorrectionRequest;
use App\Models\Measurement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class PatientPortalController extends Controller
{
    /**
     * Register a measurement for the authenticated patient's own plan (section 5.4).
     *
     * The patient and author are forced from the session, so the patient cannot
     * register a measurement for anyone else.
     */
    public function storeMeasurement(StoreMeasurementRequest $request): JsonResponse
    {
        $patient = $request->user()->patient;

        if ($patient === null) {
            return response()->json([
                'data' => null,
                'message' => 'No patient profile is associated with this account.',
                'errors' => null,
            ], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validated();
        $data['patient_id'] = $patient->id;
        $data['origin'] = 'PATIENT';
        $data['author_user_id'] = $request->user()->id;

        $measurement = Measurement::create($data);

        return response()->json([
            'data' => new MeasurementResource($measurement->load('measurementType')),
            'message' => 'Measurement registered successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Submit an administrative correction request for the authenticated patient.
     *
     * The patient and requester are forced from the session (RF-BE-09).
     */
    public function storeCorrectionRequest(StoreCorrectionRequestRequest $request): JsonResponse
    {
        $patient = $request->user()->patient;

        if ($patient === null) {
            return response()->json([
                'data' => null,
                'message' => 'No patient profile is associated with this account.',
                'errors' => null,
            ], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validated();
        $data['patient_id'] = $patient->id;
        $data['requested_by'] = $request->user()->id;
        $data['status'] = 'PENDING';

        $correctionRequest = CorrectionRequest::create($data);

        return response()->json([
            'data' => new CorrectionRequestResource($correctionRequest),
            'message' => 'Correction request submitted successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }
}
