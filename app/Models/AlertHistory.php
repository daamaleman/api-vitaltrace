<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable alert state-change entry.
 *
 * Append-only: the table has no updated_at and no soft deletes, so Eloquent
 * timestamp maintenance is disabled and only creation is supported.
 */
class AlertHistory extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'alert_history';

    /**
     * Disable Eloquent's automatic updated_at handling; created_at is set by
     * the database default and never modified.
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
        'alert_id',
        'action',
        'previous_status',
        'new_status',
        'comment',
        'user_id',
    ];

    /**
     * Attribute casting.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Alert this history entry belongs to.
     */
    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class);
    }

    /**
     * User responsible for the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
