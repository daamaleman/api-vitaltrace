<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Immutable technical log of external integration calls.
 *
 * Append-only: no updated_at and no soft deletes, so Eloquent timestamp
 * maintenance is disabled and only creation is supported.
 */
class IntegrationLog extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'integration_logs';

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
        'service',
        'operation',
        'local_reference',
        'status',
        'attempts',
        'error_summary',
        'request_id',
    ];

    /**
     * Attribute casting.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attempts' => 'integer',
        'created_at' => 'datetime',
    ];
}
