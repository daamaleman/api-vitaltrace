<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class PasswordResetController extends Controller
{
    private const GENERIC_FORGOT_MESSAGE =
        'Si existe una cuenta con ese correo, se ha enviado un código para restablecer la contraseña.';

    public function __construct(private PasswordResetService $service)
    {
    }

    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->validated()['email'];
        $user = User::where('email', $email)->first();

        // Only issue for existing accounts; response stays generic to prevent enumeration.
        if ($user !== null) {
            $this->service->issueFor($user);
        }

        return response()->json([
            'data' => null,
            'message' => self::GENERIC_FORGOT_MESSAGE,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $ok = $this->service->resetWithCode($data['email'], $data['code'], $data['password']);

        if (! $ok) {
            return response()->json([
                'data' => null,
                'message' => 'El código no es válido o ha expirado.',
                'errors' => ['code' => 'INVALID_RESET_CODE'],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => null,
            'message' => 'Contraseña restablecida correctamente.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
