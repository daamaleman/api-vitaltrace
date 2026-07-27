<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Non-diagnostic follow-up alert (RN-11).
 *
 * May originate from a measurement out of range, adherence issues or missed
 * controls. Its state changes are tracked in the alert_history table.
 */
class Alert extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'alerts';

    /**
     * Mass-assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'patient_id',
        'measurement_id',
        'type',
        'severity',
        'status',
        'description',
        'generated_at',
        'closed_at',
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
        'generated_at' => 'datetime',
        'closed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Patient the alert refers to.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Originating measurement, if any.
     */
    public function measurement(): BelongsTo
    {
        return $this->belongsTo(Measurement::class);
    }

    /**
     * Chronological history of this alert's state changes.
     */
    public function history(): HasMany
    {
        return $this->hasMany(AlertHistory::class);
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
