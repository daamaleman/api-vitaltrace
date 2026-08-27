<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreRolePermissionRequest;
use App\Http\Requests\UpdateRolePermissionRequest;
use App\Http\Resources\RolePermissionResource;
use App\Models\RolePermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class RolePermissionController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $rolePermissions = RolePermission::latest('id')->paginate(15);

        return RolePermissionResource::collection($rolePermissions);
    }

    public function store(StoreRolePermissionRequest $request): JsonResponse
    {
        $rolePermission = RolePermission::create($request->validated());

        return response()->json([
            'data' => new RolePermissionResource($rolePermission->load(['role', 'permission'])),
            'message' => 'Permiso asignado al rol correctamente.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    public function show(RolePermission $rolePermission): JsonResponse
    {
        return response()->json([
            'data' => new RolePermissionResource($rolePermission->load(['role', 'permission'])),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    public function update(UpdateRolePermissionRequest $request, RolePermission $rolePermission): JsonResponse
    {
        $rolePermission->update($request->validated());

        return response()->json([
            'data' => new RolePermissionResource($rolePermission->load(['role', 'permission'])),
            'message' => 'Permiso de rol actualizado correctamente.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    public function destroy(RolePermission $rolePermission): JsonResponse
    {
        $rolePermission->delete();

        return response()->json([
            'data' => null,
            'message' => 'Permiso de rol eliminado correctamente.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
