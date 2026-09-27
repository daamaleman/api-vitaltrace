<?php

declare(strict_types=1);

namespace App\Observers;

use App\Support\Auditing\AuditLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Generic observer that records CREATE, UPDATE and DELETE of auditable models.
 * With soft deletes, `deleted` fires on the logical delete, so logical
 * deletions are captured as DELETE. Registered per model in AppServiceProvider.
 */
class AuditableObserver
{
    /** Campos de tracking de sesión que no constituyen operación auditable. */
    private const IGNORED_ON_UPDATE = [
        'last_access_at', 'failed_attempts', 'blocked_until',
        'remember_token', 'updated_at', 'password_set_at', 'email_verified_at',
    ];

    public function created(Model $model): void
    {
        AuditLogger::logModel('CREATE', $model);
    }

    public function updated(Model $model): void
    {
        $changes = array_diff_key($model->getChanges(), array_flip(self::IGNORED_ON_UPDATE));
        
        // Si lo único que cambió son campos de tracking, no auditar.
        if (count($changes) === 0) {
            return;
        }
        
        AuditLogger::logModel('UPDATE', $model);
    }

    public function deleted(Model $model): void
    {
        AuditLogger::logModel('DELETE', $model);
    }
}