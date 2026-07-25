<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PatientRelative Model
 * 
 * Represents the relationship between a patient and their relatives.
 * Handles soft deletes and tracks creation, modification, and deletion by users.
 */
class PatientRelative extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'patient_relative';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'patient_id',
        'relative_id',
        'relationship',
        'scope',
        'status',
        'start_date',
        'end_date',
        'registered_by',
        'authorized_by',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scope' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the patient associated with this relative relationship.
     *
     * @return BelongsTo
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the relative associated with this patient relationship.
     *
     * @return BelongsTo
     */
    public function relative(): BelongsTo
    {
        return $this->belongsTo(Relative::class);
    }

    /**
     * Get the user who registered this patient-relative relationship.
     *
     * @return BelongsTo
     */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /**
     * Get the user who authorized this patient-relative relationship.
     *
     * @return BelongsTo
     */
    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    /**
     * Get the user who created this patient-relative relationship.
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last edited this patient-relative relationship.
     *
     * @return BelongsTo
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this patient-relative relationship.
     *
     * @return BelongsTo
     */
    public function eraser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * RN-03: Statuses that count toward the two-relative limit per patient.
     * Only relationships with these statuses are considered active and count
     * against the maximum allowed relatives for a single patient.
     *
     * @var array<int, string>
     */
    public const ACTIVE_STATUSES = ['PENDING', 'ACTIVE'];
}
