<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdministrativeStaffRequest;
use App\Http\Requests\UpdateAdministrativeStaffRequest;
use App\Http\Resources\AdministrativeStaffResource;
use App\Models\AdministrativeStaff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Handles CRUD operations for administrative staff records.
 */
class AdministrativeStaffController extends Controller
{
    /**
     * Display a paginated listing of administrative staff with related person data.
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $staff = AdministrativeStaff::with('person')->latest('id')->paginate(15);

        return AdministrativeStaffResource::collection($staff);
    }

    /**
     * Store a newly created administrative staff record.
     *
     * @param StoreAdministrativeStaffRequest $request
     * @return JsonResponse
     */
    public function store(StoreAdministrativeStaffRequest $request): JsonResponse
    {
        $staff = AdministrativeStaff::create($request->validated());

        return response()->json([
            'data' => new AdministrativeStaffResource($staff->load('person')),
            'message' => 'Administrative staff registered successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified administrative staff record with related person data.
     *
     * @param AdministrativeStaff $administrativeStaff
     * @return JsonResponse
     */
    public function show(AdministrativeStaff $administrativeStaff): JsonResponse
    {
        return response()->json([
            'data' => new AdministrativeStaffResource($administrativeStaff->load('person')),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified administrative staff record.
     *
     * @param UpdateAdministrativeStaffRequest $request
     * @param AdministrativeStaff $administrativeStaff
     * @return JsonResponse
     */
    public function update(UpdateAdministrativeStaffRequest $request, AdministrativeStaff $administrativeStaff): JsonResponse
    {
        $administrativeStaff->update($request->validated());

        return response()->json([
            'data' => new AdministrativeStaffResource($administrativeStaff->load('person')),
            'message' => 'Administrative staff updated successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified administrative staff record.
     *
     * @param AdministrativeStaff $administrativeStaff
     * @return JsonResponse
     */
    public function destroy(AdministrativeStaff $administrativeStaff): JsonResponse
    {
        $administrativeStaff->delete();

        return response()->json([
            'data' => null,
            'message' => 'Administrative staff removed successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
