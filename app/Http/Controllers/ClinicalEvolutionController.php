<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreClinicalEvolutionRequest;
use App\Http\Requests\UpdateClinicalEvolutionRequest;
use App\Http\Resources\ClinicalEvolutionResource;
use App\Models\ClinicalEvolution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * CRUD endpoints for clinical evolution entries.
 */
class ClinicalEvolutionController extends Controller
{
    /**
     * List paginated clinical evolutions with their patient.
     */
    public function index(): AnonymousResourceCollection
    {
        $evolutions = ClinicalEvolution::with('patient')->latest('id')->paginate(15);

        return ClinicalEvolutionResource::collection($evolutions);
    }

    /**
     * Register a new clinical evolution entry.
     */
    public function store(StoreClinicalEvolutionRequest $request): JsonResponse
    {
        $evolution = ClinicalEvolution::create($request->validated());

        return response()->json([
            'data' => new ClinicalEvolutionResource($evolution->load('patient')),
            'message' => 'Clinical evolution registered successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a single clinical evolution entry.
     */
    public function show(ClinicalEvolution $clinicalEvolution): JsonResponse
    {
        return response()->json([
            'data' => new ClinicalEvolutionResource($clinicalEvolution->load('patient')),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Update an existing clinical evolution entry.
     */
    public function update(UpdateClinicalEvolutionRequest $request, ClinicalEvolution $clinicalEvolution): JsonResponse
    {
        $clinicalEvolution->update($request->validated());

        return response()->json([
            'data' => new ClinicalEvolutionResource($clinicalEvolution->load('patient')),
            'message' => 'Clinical evolution updated successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Soft-delete a clinical evolution entry.
     */
    public function destroy(ClinicalEvolution $clinicalEvolution): JsonResponse
    {
        $clinicalEvolution->delete();

        return response()->json([
            'data' => null,
            'message' => 'Clinical evolution removed successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
