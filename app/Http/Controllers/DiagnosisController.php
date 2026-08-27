<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDiagnosisRequest;
use App\Http\Requests\UpdateDiagnosisRequest;
use App\Http\Resources\DiagnosisResource;
use App\Models\Diagnosis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * CRUD endpoints for clinical diagnoses.
 */
class DiagnosisController extends Controller
{
    /**
     * List paginated diagnoses with their patient.
     */
    public function index(): AnonymousResourceCollection
    {
        $diagnoses = Diagnosis::with('patient')->latest('id')->paginate(15);

        return DiagnosisResource::collection($diagnoses);
    }

    /**
     * Register a new diagnosis.
     */
    public function store(StoreDiagnosisRequest $request): JsonResponse
    {
        $diagnosis = Diagnosis::create($request->validated());

        return response()->json([
            'data' => new DiagnosisResource($diagnosis->load('patient')),
            'message' => 'Diagnóstico registrado correctamente.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a single diagnosis.
     */
    public function show(Diagnosis $diagnosis): JsonResponse
    {
        return response()->json([
            'data' => new DiagnosisResource($diagnosis->load('patient')),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Update an existing diagnosis.
     */
    public function update(UpdateDiagnosisRequest $request, Diagnosis $diagnosis): JsonResponse
    {
        $diagnosis->update($request->validated());

        return response()->json([
            'data' => new DiagnosisResource($diagnosis->load('patient')),
            'message' => 'Diagnóstico actualizado correctamente.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Soft-delete a diagnosis.
     */
    public function destroy(Diagnosis $diagnosis): JsonResponse
    {
        $diagnosis->delete();

        return response()->json([
            'data' => null,
            'message' => 'Diagnóstico eliminado correctamente.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
