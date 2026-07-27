<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable audit trail entry for user and system actions.
 *
 * Append-only: no updated_at and no soft deletes, so Eloquent timestamp
 * maintenance is disabled and only creation is supported. The acting user may
 * be null for system-generated actions.
 */
class AuditLog extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'audit_logs';

    /**
     * Disable Eloquent's automatic timestamp handling; created_at is set by the
     * database default and never modified.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Mass-assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'role_snapshot',
        'action',
        'table',
        'record_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'request_id',
    ];

    /**
     * Attribute casting.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'record_id' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Acting user (null for system actions).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
