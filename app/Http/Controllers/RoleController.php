<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        // Retrieve all roles and return them as a collection of RoleResource
        $roles = Role::latest()->paginate(10);
        return RoleResource::collection($roles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create($request->validated());
        return (new RoleResource($role))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role): RoleResource
    {
        return new RoleResource($role);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        $role->update($request->validated());
        return new RoleResource($role);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role): JsonResponse
    {
        $role->delete();
        return response()->json(['success' => true, 'message' => 'Rol eliminado correctamente.'], 200);
    }
}
