<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountActivation extends Model
{
    use HasFactory;

    /**
     * Activation code validity window in hours.
     */
    public const VALIDITY_HOURS = 24;

    /**
     * Maximum failed validation attempts before code invalidation.
     */
    public const MAX_ATTEMPTS = 5;

    public const TOKEN_VALIDITY_MINUTES = 15;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'account_activations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'code_hash',
        'activation_token_hash',
        'activation_token_expires_at',
        'activation_token_used_at',
        'sent_to_email',
        'expires_at',
        'used_at',
        'attempts',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'activation_token_expires_at' => 'datetime',
        'activation_token_used_at' => 'datetime',
        'used_at' => 'datetime',
        'attempts' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user associated with this activation code.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
