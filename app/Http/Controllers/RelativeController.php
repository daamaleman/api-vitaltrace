<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreRelativeRequest;
use App\Http\Requests\UpdateRelativeRequest;
use App\Http\Resources\RelativeResource;
use App\Models\Relative;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * RelativeController
 * 
 * Handles HTTP requests for managing relatives.
 * Provides endpoints for CRUD operations on relative records.
 */
class RelativeController extends Controller
{
    /**
     * Retrieve a paginated list of all relatives.
     *
     * @return AnonymousResourceCollection A paginated collection of relative resources.
     */
    public function index(): AnonymousResourceCollection
    {
        $relatives = Relative::with('person')->latest('id')->paginate(15);

        return RelativeResource::collection($relatives);
    }

    /**
     * Create and store a new relative record.
     *
     * @param StoreRelativeRequest $request The validated request data for storing a relative.
     * @return JsonResponse JSON response containing the created relative resource.
     */
    public function store(StoreRelativeRequest $request): JsonResponse
    {
        $relative = Relative::create($request->validated());

        return response()->json([
            'data' => new RelativeResource($relative->load('person')),
            'message' => 'Relative registered successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Display a specific relative by ID.
     *
     * @param Relative $relative The relative model instance (resolved via route model binding).
     * @return JsonResponse JSON response containing the relative resource.
     */
    public function show(Relative $relative): JsonResponse
    {
        return response()->json([
            'data' => new RelativeResource($relative->load('person')),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Update an existing relative record.
     *
     * @param UpdateRelativeRequest $request The validated request data for updating a relative.
     * @param Relative $relative The relative model instance to update.
     * @return JsonResponse JSON response containing the updated relative resource.
     */
    public function update(UpdateRelativeRequest $request, Relative $relative): JsonResponse
    {
        $relative->update($request->validated());

        return response()->json([
            'data' => new RelativeResource($relative->load('person')),
            'message' => 'Relative updated successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Delete a relative record.
     *
     * @param Relative $relative The relative model instance to delete.
     * @return JsonResponse JSON response confirming the deletion.
     */
    public function destroy(Relative $relative): JsonResponse
    {
        $relative->delete();

        return response()->json([
            'data' => null,
            'message' => 'Relative removed successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
