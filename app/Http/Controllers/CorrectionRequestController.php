<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCorrectionRequestRequest;
use App\Http\Requests\UpdateCorrectionRequestRequest;
use App\Http\Resources\CorrectionRequestResource;
use App\Models\CorrectionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * CRUD endpoints for administrative correction requests.
 *
 * Applying an approved change to the patient record is an Admission action
 * handled elsewhere; this controller manages the request lifecycle.
 */
class CorrectionRequestController extends Controller
{
    /**
     * List paginated correction requests with their patient.
     */
    public function index(): AnonymousResourceCollection
    {
        $requests = CorrectionRequest::with('patient')->latest('id')->paginate(15);

        return CorrectionRequestResource::collection($requests);
    }

    /**
     * Submit a new correction request.
     */
    public function store(StoreCorrectionRequestRequest $request): JsonResponse
    {
        $correctionRequest = CorrectionRequest::create($request->validated());

        return response()->json([
            'data' => new CorrectionRequestResource($correctionRequest->load('patient')),
            'message' => 'Correction request submitted successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a single correction request.
     */
    public function show(CorrectionRequest $correctionRequest): JsonResponse
    {
        return response()->json([
            'data' => new CorrectionRequestResource($correctionRequest->load('patient')),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Resolve a correction request (Admission review).
     */
    public function update(UpdateCorrectionRequestRequest $request, CorrectionRequest $correctionRequest): JsonResponse
    {
        $correctionRequest->update($request->validated());

        return response()->json([
            'data' => new CorrectionRequestResource($correctionRequest->load('patient')),
            'message' => 'Correction request updated successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Delete a correction request.
     */
    public function destroy(CorrectionRequest $correctionRequest): JsonResponse
    {
        $correctionRequest->delete();

        return response()->json([
            'data' => null,
            'message' => 'Correction request removed successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
