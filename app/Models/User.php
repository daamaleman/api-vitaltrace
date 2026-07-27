<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    /**
     * The database table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
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

    /**
     * Get the person associated with the user.
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * Get the user that created this record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    /**
     * Get the user that last updated this record.
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'updated_by');
    }

    /**
     * Get the user that soft deleted this record.
     */
    public function eraser(): BelongsTo
    {
        return $this->belongsTo(self::class, 'deleted_by');
    }

    /**
     * Get the patient profile associated with the user.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Determine whether a doctor or nurse has an active assignment for a patient (RN-06).
     *
     * System admins and admission are handled by role gates, not by this relation.
     */
    public function isAssignedToPatient(int $patientId): bool
    {
        return \App\Models\ProfessionalAssignment::query()
            ->where('patient_id', $patientId)
            ->where('status', 'ACTIVE')
            ->whereHas('healthStaff', function ($query): void {
                $query->where('person_id', $this->person_id);
            })
            ->exists();
    }

    /**
     * Roles currently assigned to the user through the user_role pivot.
     *
     * Only active pivot rows are considered, so revoked roles do not grant access.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role')
            ->withPivot(['active', 'assigned_at', 'revoked_at'])
            ->wherePivot('active', true);
    }

    /**
     * Determine whether the user has the given role by name.
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles->contains('name', $roleName);
    }

    /**
     * Determine whether the user has any of the given roles.
     *
     * @param  array<int, string>  $roleNames
     */
    public function hasAnyRole(array $roleNames): bool
    {
        return $this->roles->pluck('name')->intersect($roleNames)->isNotEmpty();
    }

    /**
     * Determine whether the user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    /**
     * Determine whether the user is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }
}
