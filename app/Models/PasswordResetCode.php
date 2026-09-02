<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetCode extends Model
{
    public const VALIDITY_HOURS = 24;
    public const MAX_ATTEMPTS = 5;

    protected $fillable = [
        'user_id', 'code_hash', 'sent_to_email', 'expires_at',
        'used_at', 'attempts', 'status',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
