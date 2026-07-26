<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Prescription detail linking a treatment with a catalog medication.
 */
class TreatmentMedication extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'treatment_medication';

    /**
     * Mass-assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'treatment_id',
        'medication_id',
        'dose',
        'route',
        'frequency',
        'schedules',
        'start_date',
        'end_date',
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
        'schedules' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Parent treatment.
     */
    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }

    /**
     * Prescribed catalog medication.
     */
    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
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
