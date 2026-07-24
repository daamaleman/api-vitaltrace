<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class UserController extends Controller
{
    /**
     * Display a paginated list of users with their related person data.
     */
    public function index(): AnonymousResourceCollection
    {
        $users = User::with('person')->latest('id')->paginate(15);

        return UserResource::collection($users);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return response()->json([
            'data' => new UserResource($user->load('person')),
            'message' => 'User created successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($user->load('person')),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user->update($request->validated());

        return response()->json([
            'data' => new UserResource($user->load('person')),
            'message' => 'User updated successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json([
            'data' => null,
            'message' => 'User deleted successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
