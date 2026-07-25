<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRoleRequest;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Http\Resources\UserRoleResource;
use App\Models\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class UserRoleController extends Controller
{
    /**
     * Display a paginated list of user roles with their related user and role.
     */
    public function index(): AnonymousResourceCollection
    {
        $userRoles = UserRole::with(['user', 'role'])->latest('id')->paginate(15);

        return UserRoleResource::collection($userRoles);
    }

    /**
     * Store a newly assigned role for a user.
     */
    public function store(StoreUserRoleRequest $request): JsonResponse
    {
        $userRole = UserRole::create($request->validated());

        return response()->json([
            'data' => new UserRoleResource($userRole->load(['user', 'role'])),
            'message' => 'Role assigned to user successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified user role with its related user and role.
     */
    public function show(UserRole $userRole): JsonResponse
    {
        return response()->json([
            'data' => new UserRoleResource($userRole->load(['user', 'role'])),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified user role.
     */
    public function update(UpdateUserRoleRequest $request, UserRole $userRole): JsonResponse
    {
        $userRole->update($request->validated());

        return response()->json([
            'data' => new UserRoleResource($userRole->load(['user', 'role'])),
            'message' => 'User role updated successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified user role.
     */
    public function destroy(UserRole $userRole): JsonResponse
    {
        $userRole->delete();

        return response()->json([
            'data' => null,
            'message' => 'User role removed successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
