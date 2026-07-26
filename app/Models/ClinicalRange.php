<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Clinical range for a measurement type.
 *
 * Patient-specific when patient_id is set, otherwise a general range. Defined by
 * a doctor and used by the alert engine to flag out-of-range measurements.
 */
class ClinicalRange extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'clinical_ranges';

    /**
     * Mass-assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'patient_id',
        'measurement_type_id',
        'min_value',
        'max_value',
        'severity',
        'start_date',
        'end_date',
        'defined_by',
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
        'min_value' => 'decimal:3',
        'max_value' => 'decimal:3',
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Target patient (null for a general range).
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Measurement type the range applies to.
     */
    public function measurementType(): BelongsTo
    {
        return $this->belongsTo(MeasurementType::class);
    }

    /**
     * Doctor who defined the range.
     */
    public function definedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'defined_by');
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
}
