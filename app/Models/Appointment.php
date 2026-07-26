<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Patient appointment with a health professional.
 *
 * Managed internally; external calendar sync is optional and never required for
 * the appointment to exist (RF-BE-15, RN-13).
 */
class Appointment extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'appointments';

    /**
     * Mass-assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'patient_id',
        'health_staff_id',
        'scheduled_at',
        'duration_minutes',
        'reason',
        'status',
        'external_sync',
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
        'scheduled_at' => 'datetime',
        'duration_minutes' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Patient the appointment belongs to.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Professional attending the appointment.
     */
    public function healthStaff(): BelongsTo
    {
        return $this->belongsTo(HealthStaff::class);
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
