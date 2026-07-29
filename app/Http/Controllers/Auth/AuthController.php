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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Hybrid authentication:
 *
 *  - Web SPA (app.vitaltrace.lat) authenticates via Sanctum cookie session.
 *  - Mobile app authenticates via a Bearer token.
 *
 * The request is treated as "from the SPA frontend" when it carries the
 * stateful frontend cookies (detected by EnsureFrontendRequestsAreStateful),
 * in which case a session is started instead of issuing a token.
 */
class AuthController extends Controller
{
    /**
     * Authenticate a user.
     *
     * Only ACTIVE accounts may log in. Web clients receive a session cookie;
     * mobile clients receive a Bearer token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if ($user === null
            || $user->password === null
            || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'ACTIVE') {
            throw ValidationException::withMessages([
                'email' => ['This account is not active.'],
            ]);
        }

        $user->forceFill(['last_access_at' => now()])->save();

        // Web SPA request: start a cookie session, no token.
        if ($this->isStatefulRequest($request)) {
            Auth::guard('web')->login($user, true);
            $request->session()->regenerate();

            return response()->json([
                'data' => [
                    'user' => new UserResource($user->load('person', 'roles')),
                ],
                'message' => 'Logged in successfully.',
                'errors' => null,
            ], Response::HTTP_OK);
        }

        // Mobile request: issue a Bearer token.
        $user->tokens()->delete();
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => new UserResource($user->load('person', 'roles')),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
            'message' => 'Logged in successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Return the currently authenticated user (works for both guards).
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($request->user()->load('person', 'roles')),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Log out the current user, ending the session or revoking the token.
     */
    public function logout(Request $request): JsonResponse
    {
        if ($this->isStatefulRequest($request)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        } else {
            $request->user()->currentAccessToken()?->delete();
        }

        return response()->json([
            'data' => null,
            'message' => 'Logged out successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Determine whether the request comes from the stateful SPA frontend.
     *
     * Sanctum marks these requests when the Origin/Referer matches a
     * configured stateful domain and the session cookie is present.
     */
    private function isStatefulRequest(Request $request): bool
    {
        return $request->hasSession() && $request->session()->isStarted();
    }
}