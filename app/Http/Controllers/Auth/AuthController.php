<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\TwoFactorService;
use App\Support\Auditing\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Hybrid authentication with mandatory two-factor (2FA) by emailed code:
 *
 *  - Step 1 (login): validates credentials and emails a 6-digit code.
 *    No session or token is issued yet; a challenge id is returned.
 *  - Step 2 (verify-2fa): validates the code and establishes the session
 *    (web cookie) or token (mobile).
 */
class AuthController extends Controller
{
    /**
     * Step 1: authenticate credentials and issue a 2FA challenge.
     *
     * Only ACTIVE accounts may proceed. On success, a six-digit code is
     * emailed and a challenge id is returned; the session is NOT started yet.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if ($user !== null
            && $user->password_set_at === null
            && $user->patient()->exists()) {
            return response()->json([
                'data' => null,
                'message' => 'Initial account activation is required.',
                'errors' => ['code' => 'ACCOUNT_ACTIVATION_REQUIRED'],
            ], Response::HTTP_FORBIDDEN);
        }

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

        // Credentials are valid: do NOT start a session yet.
        // Issue a 2FA code to the user's email and return a challenge.
        $challengeId = app(TwoFactorService::class)->issueFor($user);

        return response()->json([
            'data' => [
                'requires_2fa' => true,
                'challenge_id' => $challengeId,
                'email_hint' => $this->maskEmail($user->email),
            ],
            'message' => 'Se envio un codigo de verificacion a tu correo.',
            'errors' => null,
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * Step 2: verify the emailed 2FA code and establish the session/token.
     */
    public function verifyTwoFactor(Request $request): JsonResponse
    {
        $data = $request->validate([
            'challenge_id' => ['required', 'string'],
            'code' => ['required', 'string', 'digits:6'],
        ]);

        $user = app(TwoFactorService::class)->verify($data['challenge_id'], $data['code']);

        if ($user === null) {
            throw ValidationException::withMessages([
                'code' => ['El codigo es incorrecto, expiro o ya fue usado.'],
            ]);
        }

        if ($user->status !== 'ACTIVE') {
            throw ValidationException::withMessages([
                'code' => ['This account is not active.'],
            ]);
        }

        $user->forceFill(['last_access_at' => now()])->save();

        // Web SPA request: start a cookie session, no token.
        if ($this->isStatefulRequest($request)) {
            Auth::guard('web')->login($user, true);
            $request->session()->regenerate();

            AuditLogger::log('LOGIN', module: AuditLogger::portalFor($user));

            return response()->json([
                'data' => [
                    'user' => new UserResource($user->load('person', 'roles')),
                ],
                'message' => 'Sesion iniciada correctamente.',
                'errors' => null,
            ], Response::HTTP_OK);
        }

        // Mobile request: issue a Bearer token.
        $user->tokens()->delete();
        $token = $user->createToken('mobile-app')->plainTextToken;
        Auth::setUser($user);

        AuditLogger::log('LOGIN', module: AuditLogger::portalFor($user));

        return response()->json([
            'data' => [
                'user' => new UserResource($user->load('person', 'roles')),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
            'message' => 'Sesion iniciada correctamente.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Resend the 2FA code for an existing challenge.
     */
    public function resendTwoFactor(Request $request): JsonResponse
    {
        $data = $request->validate([
            'challenge_id' => ['required', 'string'],
        ]);

        $newChallenge = app(TwoFactorService::class)->resend($data['challenge_id']);

        if ($newChallenge === null) {
            throw ValidationException::withMessages([
                'challenge_id' => ['La solicitud de verificacion no es valida o expiro.'],
            ]);
        }

        return response()->json([
            'data' => ['challenge_id' => $newChallenge],
            'message' => 'Se reenvio el codigo de verificacion.',
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
        // Register the LOGOUT before invalidating the session/token.
        AuditLogger::log('LOGOUT', module: AuditLogger::portalFor($request->user()));

        if ($this->isStatefulRequest($request)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        } else {
            $request->user()->currentAccessToken()?->delete();
        }

        return response()->json([
            'data' => null,
            'message' => 'Sesion cerrada correctamente.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Mask an email for display, e.g. ad****@vitaltrace.lat
     */
    private function maskEmail(string $email): string
    {
        [$name, $domain] = explode('@', $email, 2);
        $visible = mb_substr($name, 0, 2);

        return $visible . str_repeat('*', max(2, mb_strlen($name) - 2)) . '@' . $domain;
    }

    /**
     * Determine whether the request comes from the stateful SPA frontend.
     */
    private function isStatefulRequest(Request $request): bool
    {
        return $request->hasSession() && $request->session()->isStarted();
    }
}