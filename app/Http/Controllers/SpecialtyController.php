<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreSpecialtyRequest;
use App\Http\Requests\UpdateSpecialtyRequest;
use App\Http\Resources\SpecialtyResource;
use App\Models\Specialty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SpecialtyController extends Controller
{
    /**
     * Display a paginated list of specialties ordered by name.
     */
    public function index(): AnonymousResourceCollection
    {
        $specialties = Specialty::orderBy('name')->paginate(15);

        return SpecialtyResource::collection($specialties);
    }

    /**
     * Store a newly created specialty in storage.
     */
    public function store(StoreSpecialtyRequest $request): JsonResponse
    {
        $specialty = Specialty::create($request->validated());

        return response()->json([
            'data' => new SpecialtyResource($specialty),
            'message' => 'Specialty created successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified specialty.
     */
    public function show(Specialty $specialty): JsonResponse
    {
        return response()->json([
            'data' => new SpecialtyResource($specialty),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified specialty in storage.
     */
    public function update(UpdateSpecialtyRequest $request, Specialty $specialty): JsonResponse
    {
        $specialty->update($request->validated());

        return response()->json([
            'data' => new SpecialtyResource($specialty),
            'message' => 'Specialty updated successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified specialty from storage.
     */
    public function destroy(Specialty $specialty): JsonResponse
    {
        $specialty->delete();

        return response()->json([
            'data' => null,
            'message' => 'Specialty deleted successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
