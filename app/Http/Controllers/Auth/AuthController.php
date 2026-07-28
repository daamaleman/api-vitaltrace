<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * SPA authentication endpoints using Sanctum cookie-based sessions.
 */
class AuthController extends Controller
{
    /**
     * Authenticate a user and start a session.
     *
     * Only ACTIVE accounts may log in; pending, blocked, suspended or
     * deactivated accounts are rejected with a generic message.
     */
    public function login(LoginRequest $request): JsonResponse
{
    $credentials = $request->validated();

    $user = User::where('email', $credentials['email'])->first();

    if (
        $user === null ||
        $user->password === null ||
        ! Hash::check($credentials['password'], $user->password)
    ) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    if ($user->status !== 'ACTIVE') {
        throw ValidationException::withMessages([
            'email' => ['This account is not active.'],
        ]);
    }

    $user->tokens()->delete();

    $token = $user->createToken('android-app')->plainTextToken;

    $user->forceFill([
        'last_access_at' => now(),
    ])->save();

    return response()->json([
        'data' => [
            'user' => new UserResource($user->load('person')),
            'token' => $token,
            'token_type' => 'Bearer',
        ],
        'message' => 'Logged in successfully.',
        'errors' => null,
    ], Response::HTTP_OK);
}

    /**
     * Return the currently authenticated user.
     */
 public function me(Request $request): JsonResponse
{
    return response()->json([
        'data' => new UserResource(
            $request->user()->load('person')
        ),
        'message' => null,
        'errors' => null,
    ], Response::HTTP_OK);
}

    /**
     * Log out the current user and invalidate the session.
     */
    public function logout(Request $request): JsonResponse
{
    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'data' => null,
        'message' => 'Logged out successfully.',
        'errors' => null,
    ], Response::HTTP_OK);
}
}
