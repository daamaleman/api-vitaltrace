<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Internal or email notification sent to a user.
 *
 * Named AppNotification to avoid collision with Laravel's built-in
 * Illuminate\Notifications\Notification. The underlying table is "notifications".
 */
class AppNotification extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'notifications';

    /**
     * Mass-assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'channel',
        'subject',
        'general_message',
        'status',
        'attempts',
        'scheduled_at',
        'sent_at',
        'error_summary',
        'created_by',
        'updated_by',
    ];

    /**
     * Attribute casting.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attempts' => 'integer',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Recipient account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * User who created the record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who last updated the record.
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
