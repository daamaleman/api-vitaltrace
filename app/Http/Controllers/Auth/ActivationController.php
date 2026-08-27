<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivateAccountRequest;
use App\Http\Requests\ResendCodeRequest;
use App\Http\Requests\SetInitialPasswordRequest;
use App\Http\Requests\VerifyActivationCodeRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ActivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Public endpoints for the email-based account activation flow.
 */
class ActivationController extends Controller
{
    public function __construct(private readonly ActivationService $activationService) {}

    /**
     * Activate an account with the emailed code and set the initial password.
     *
     * Responses stay generic to avoid leaking whether the email exists or the
     * exact failure cause (wrong, expired, used or too many attempts).
     */
    public function activate(ActivateAccountRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = $this->activationService->activateLegacy($data['email'], $data['code'], $data['password']);

        if ($user === null) {
            return response()->json([
                'data' => null,
                'message' => 'The activation code is invalid or has expired.',
                'errors' => null,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => new UserResource($user->load('person')),
            'message' => 'Account activated successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Resend an activation code to a pending account.
     *
     * Always responds the same way regardless of whether the account exists or
     * is already active, to avoid account enumeration.
     */
    public function resend(ResendCodeRequest $request): JsonResponse
    {
        $email = $request->validated()['email'];

        $user = User::where('email', $email)->first();

        if ($user !== null
            && $user->status === 'PENDING'
            && $user->password_set_at === null
            && $user->patient()->exists()) {
            $this->activationService->issueFor($user);
        }

        return response()->json([
            'data' => null,
            'message' => 'If the account exists and is pending, a new code has been sent.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    public function verifyCode(VerifyActivationCodeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->activationService->verifyPatientCode($data['email'], $data['code']);

        if ($result === null) {
            return response()->json([
                'data' => null,
                'message' => 'The activation code is invalid or has expired.',
                'errors' => ['code' => 'INVALID_ACTIVATION_CODE'],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => $result,
            'message' => 'Activation code verified.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    public function setPassword(SetInitialPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $completed = $this->activationService->setPatientInitialPassword(
            $data['activation_token'],
            $data['password'],
        );

        if (! $completed) {
            return response()->json([
                'data' => null,
                'message' => 'The activation token is invalid or has expired.',
                'errors' => ['code' => 'INVALID_ACTIVATION_TOKEN'],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => ['activation_completed' => true],
            'message' => 'Password created successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
