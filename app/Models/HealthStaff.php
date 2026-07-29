<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Health professional profile (doctor or nurse).
 *
 * Wraps a person identity with professional data and an optional specialty.
 * Clinical assignments to patients are handled by the professional_assignments table.
 */
class HealthStaff extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Explicit table name to avoid Laravel's default pluralization.
     *
     * @var string
     */
    protected $table = 'health_staff';

    /**
     * Mass-assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'person_id',
        'professional_type',
        'professional_code',
        'specialty_id',
        'active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Attribute casting.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Person identity behind this professional profile.
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
    
    public function assignments(): HasMany
    {
        return $this->hasMany(ProfessionalAssignment::class);
    }

    /**
     * Optional main specialty of the professional.
     */
    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
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

    /**
     * User who soft-deleted the record.
     */
    public function eraser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Determine whether this professional is a doctor.
     */
    public function isDoctor(): bool
    {
        return $this->professional_type === 'DOCTOR';
    }

    /**
     * Determine whether this professional is a nurse.
     */
    public function isNurse(): bool
    {
        return $this->professional_type === 'NURSE';
    }
}
