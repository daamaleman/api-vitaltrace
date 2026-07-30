<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Admin panel user management. Lists users with their person and roles,
 * and allows blocking/unblocking. Does not touch clinical data.
 */
class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::with(['person', 'roles'])->latest('id');

        if ($search = $request->query('search')) {
            $query->where('email', 'like', "%{$search}%")
                ->orWhereHas('person', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('first_last_name', 'like', "%{$search}%");
                });
        }

        $users = $query->get()->map(fn (User $user) => [
            'id' => $user->id,
            'email' => $user->email,
            'status' => $user->status,
            'last_access_at' => $user->last_access_at?->format('Y-m-d H:i:s'),
            'person' => $user->person,
            'roles' => $user->roles->map(fn ($r) => ['id' => $r->id, 'name' => $r->name]),
        ]);

        return response()->json([
            'data' => $users,
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    public function block(User $user): JsonResponse
    {
        $user->update(['status' => 'BLOCKED']);

        return response()->json([
            'data' => ['id' => $user->id, 'status' => $user->status],
            'message' => 'User blocked.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    public function unblock(User $user): JsonResponse
    {
        $user->update(['status' => 'ACTIVE', 'failed_attempts' => 0, 'blocked_until' => null]);

        return response()->json([
            'data' => ['id' => $user->id, 'status' => $user->status],
            'message' => 'User unblocked.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}