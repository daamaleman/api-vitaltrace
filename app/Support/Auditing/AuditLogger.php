<?php

declare(strict_types=1);

namespace App\Support\Auditing;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

/**
 * Central writer for the immutable audit trail.
 *
 * Captures the acting user, role snapshot and request context, redacts
 * sensitive fields, and appends an entry. A per-request correlation id is
 * reused across all entries of the same request. Auditing can be suspended
 * (e.g. during seeding).
 */
class AuditLogger
{
    /** Fields that must never be stored in the audit trail. */
    private const REDACTED = [
        'password', 'password_confirmation', 'remember_token',
        'code_hash', 'activation_token_hash', 'token', 'secret',
    ];

    private static ?string $requestId = null;
    private static bool $enabled = true;

    public static function disable(): void { self::$enabled = false; }
    public static function enable(): void { self::$enabled = true; }

    /**
     * Correlation id shared by all audit entries of the current request.
     */
    public static function requestId(): string
    {
        if (self::$requestId === null) {
            self::$requestId = (string) Str::uuid();
        }

        return self::$requestId;
    }

    /**
     * Append an audit entry. Returns silently on any failure so auditing
     * never breaks the underlying business operation.
     */
    public static function log(
        string $action,
        ?string $table = null,
        ?int $recordId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $module = null,
    ): void {
        if (! self::$enabled) {
            return;
        }

        try {
            $user = Auth::user();

            AuditLog::create([
                'user_id' => $user?->id,
                'role_snapshot' => self::roleSnapshot($user),
                'action' => $action,
                'module' => $module,
                'table' => $table,
                'record_id' => $recordId,
                'old_values' => self::redact($oldValues),
                'new_values' => self::redact($newValues),
                'ip_address' => Request::ip(),
                'user_agent' => (string) Request::userAgent(),
                'request_id' => self::requestId(),
            ]);
        } catch (\Throwable $e) {
            // Auditing must not interfere with the business transaction.
            report($e);
        }
    }

    /**
     * Log a model change, deriving table, id and dirty/original values.
     */
    public static function logModel(string $action, Model $model): void
    {
        $table = $model->getTable();
        $id = (int) $model->getKey();

        [$old, $new] = match ($action) {
            'CREATE' => [null, $model->getAttributes()],
            'UPDATE' => [
                array_intersect_key($model->getOriginal(), $model->getChanges()),
                $model->getChanges(),
            ],
            'DELETE' => [$model->getOriginal(), null],
            default => [null, null],
        };

        self::log($action, $table, $id, $old, $new);
    }

    private static function roleSnapshot(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        // First active role name, or a comma-separated list if several.
        $roles = $user->relationLoaded('roles') ? $user->roles : $user->roles()->get();

        return $roles->pluck('name')->implode(',') ?: null;
    }

    /**
     * Human-readable portal name for the user's primary role.
     */
    public static function portalFor(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        $roles = $user->relationLoaded('roles')
            ? $user->roles->pluck('name')->all()
            : $user->roles()->pluck('name')->all();

        return match (true) {
            in_array('SYSTEM_ADMIN', $roles, true) => 'Portal administrativo',
            in_array('DOCTOR', $roles, true)       => 'Portal médico',
            in_array('NURSE', $roles, true)        => 'Portal de enfermería',
            in_array('ADMISSION', $roles, true)    => 'Portal de admisión',
            in_array('PATIENT', $roles, true)      => 'Portal del paciente',
            in_array('RELATIVE', $roles, true)     => 'Portal del familiar',
            default => null,
        };
    }

    /**
     * Remove sensitive keys from a value array before persisting.
     *
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>|null
     */
    private static function redact(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        foreach (array_keys($values) as $key) {
            if (in_array($key, self::REDACTED, true)) {
                $values[$key] = '[REDACTED]';
            }
        }

        return $values;
    }
}