<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AccountActivation;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Admission-side account management (§8.3, RN-10).
 *
 * Lists accounts, generates access accounts for registered people (patients
 * or relatives) issuing an activation code, resends codes, and blocks or
 * unblocks accounts.
 */
class AdmissionAccountController extends Controller
{
    public function __construct(private readonly ActivationService $activationService)
    {
    }

    /**
     * List accounts with their person and latest activation status.
     */
    public function index(): JsonResponse
    {
        $users = User::with(['person'])
            ->latest('id')
            ->get()
            ->map(function (User $user) {
                $latest = AccountActivation::where('user_id', $user->id)
                    ->latest('id')
                    ->first();

                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'status' => $user->status,
                    'person' => $user->person,
                    'last_access_at' => $user->last_access_at?->format('Y-m-d H:i:s'),
                    'activation' => $latest ? [
                        'status' => $latest->status,
                        'expires_at' => $latest->expires_at?->format('Y-m-d H:i:s'),
                        'attempts' => $latest->attempts,
                    ] : null,
                ];
            });

        return response()->json([
            'data' => $users,
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Create an access account for an existing person and issue a code.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'person_id' => ['required', 'integer', 'exists:people,id'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
        ]);

        $user = DB::transaction(function () use ($data, $request) {
            $newUser = User::create([
                'person_id' => $data['person_id'],
                'email' => mb_strtolower(trim($data['email'])),
                'password' => null,
                'password_set_at' => null,
                'status' => 'PENDING',
            ]);

            if ($newUser->patient()->exists()) {
                $patientRole = Role::query()->where('name', 'PATIENT')->firstOrFail();
                $newUser->roles()->attach($patientRole->id, [
                    'active' => true,
                    'assigned_at' => now(),
                    'assigned_by' => $request->user()?->id,
                ]);
            }

            $this->activationService->issueFor($newUser);

            return $newUser;
        });

        return response()->json([
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'status' => $user->status,
            ],
            'message' => 'Account created and activation code sent.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Resend a fresh activation code to a pending account.
     */
    public function resend(User $user): JsonResponse
    {
        if ($user->status !== 'PENDING') {
            return response()->json([
                'data' => null,
                'message' => 'Only pending accounts can receive an activation code.',
                'errors' => ['status' => ['Account is not pending.']],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->activationService->issueFor($user);

        return response()->json([
            'data' => null,
            'message' => 'A new activation code has been sent.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Block an account.
     */
    public function block(User $user): JsonResponse
    {
        $user->update(['status' => 'BLOCKED']);

        return response()->json([
            'data' => ['id' => $user->id, 'status' => $user->status],
            'message' => 'Account blocked.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Unblock an account back to active.
     */
    public function unblock(User $user): JsonResponse
    {
        $user->update(['status' => 'ACTIVE', 'failed_attempts' => 0, 'blocked_until' => null]);

        return response()->json([
            'data' => ['id' => $user->id, 'status' => $user->status],
            'message' => 'Account unblocked.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * People (patients/relatives) that don't yet have a user account,
     * to populate the "create account" selector.
     */
    public function peopleWithoutAccount(): JsonResponse
    {
        $people = Person::whereDoesntHave('user')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'middle_name', 'first_last_name', 'second_last_name']);

        return response()->json([
            'data' => $people,
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
