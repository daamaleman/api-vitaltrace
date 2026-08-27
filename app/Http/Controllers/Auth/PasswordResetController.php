<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Throwable;

class PasswordResetController extends Controller
{
    private const GENERIC_FORGOT_MESSAGE =
        'If an account exists for that email, password reset instructions have been sent.';

    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            Password::broker()->sendResetLink($request->validated());
        } catch (Throwable) {
            // The public response intentionally remains identical to prevent account enumeration.
        }

        return response()->json([
            'data' => null,
            'message' => self::GENERIC_FORGOT_MESSAGE,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::broker()->reset(
            $request->validated(),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'data' => null,
                'message' => 'The password reset token is invalid or has expired.',
                'errors' => null,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => null,
            'message' => 'Password reset successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
