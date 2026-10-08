<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwoFactorCode extends Model
{
    public const VALIDITY_MINUTES = 10;
    public const MAX_ATTEMPTS = 5;

    protected $fillable = [
        'user_id',
        'challenge_id',
        'code_hash',
        'sent_to_email',
        'expires_at',
        'used_at',
        'attempts',
        'status',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}