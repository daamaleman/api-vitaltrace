<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Admin management of user roles (assign / revoke) with safeguards.
 *
 * Roles are stored in the user_role pivot with an `active` flag; revoking is
 * a soft operation (active = false) that preserves history. Clinical roles
 * are flagged so the UI can warn, but assignment is not blocked.
 */
class AdminUserRoleController extends Controller
{
    /** Roles that imply a health_staff record; UI warns when assigning. */
    private const CLINICAL_ROLES = ['DOCTOR', 'NURSE'];

    /**
     * List a user's active roles plus all assignable roles.
     */
    public function index(User $user): JsonResponse
    {
        $activeRoleIds = $user->roles()->pluck('roles.id')->all();

        $allRoles = Role::orderBy('id')->get(['id', 'name', 'description'])
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'active' => in_array($role->id, $activeRoleIds, true),
                'clinical' => in_array($role->name, self::CLINICAL_ROLES, true),
            ]);

        return response()->json([
            'data' => $allRoles,
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Assign a role to a user (idempotent: reactivates if previously revoked).
     */
    public function assign(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $role = Role::findOrFail($data['role_id']);
        $adminId = $request->user()->id;

        $existing = $user->roles()->wherePivot('active', true)
            ->where('roles.id', $role->id)->exists();

        if ($existing) {
            return response()->json([
                'data' => null,
                'message' => 'El usuario ya tiene este rol.',
                'errors' => ['role_id' => ['Already assigned.']],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::transaction(function () use ($user, $role, $adminId) {
            // Reactivate a previously revoked row, or attach a new one.
            $pivotExists = DB::table('user_role')
                ->where('user_id', $user->id)
                ->where('role_id', $role->id)
                ->exists();

            if ($pivotExists) {
                DB::table('user_role')
                    ->where('user_id', $user->id)
                    ->where('role_id', $role->id)
                    ->update([
                        'active' => true,
                        'assigned_at' => now(),
                        'revoked_at' => null,
                        'assigned_by' => $adminId,
                    ]);
            } else {
                DB::table('user_role')->insert([
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'active' => true,
                    'assigned_at' => now(),
                    'assigned_by' => $adminId,
                ]);
            }
        });

        return response()->json([
            'data' => ['user_id' => $user->id, 'role' => $role->name],
            'message' => 'Rol asignado correctamente.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Revoke a role from a user (soft: active = false).
     */
    public function revoke(Request $request, User $user, Role $role): JsonResponse
    {
        $adminId = $request->user()->id;

        // Safeguard: an admin cannot remove their own SYSTEM_ADMIN role.
        if ($user->id === $adminId && $role->name === 'SYSTEM_ADMIN') {
            return response()->json([
                'data' => null,
                'message' => 'No puedes quitarte a ti mismo el rol de administrador del sistema.',
                'errors' => ['role' => ['Self-lockout prevented.']],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::table('user_role')
            ->where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->update(['active' => false, 'revoked_at' => now()]);

        return response()->json([
            'data' => ['user_id' => $user->id, 'role' => $role->name],
            'message' => 'Rol revocado.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}