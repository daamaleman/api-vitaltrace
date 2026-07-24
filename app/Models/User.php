<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'person_id',
        'email',
        'password',
        'status',
        'email_verified_at',
        'last_access_at',
        'failed_attempts',
        'blocked_until',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_access_at' => 'datetime',
        'blocked_until' => 'datetime',
        'failed_attempts' => 'integer',
        'password' => 'hashed',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'updated_by');
    }

    public function eraser(): BelongsTo
    {
        return $this->belongsTo(self::class, 'deleted_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }
}
