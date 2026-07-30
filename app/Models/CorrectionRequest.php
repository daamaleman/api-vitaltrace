<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Administrative data correction request.
 *
 * A patient submits the request; Admission reviews and resolves it (RF-BE-09).
 * The requested change is applied by Admission, not by this record itself.
 */
class CorrectionRequest extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'correction_requests';

    /**
     * Mass-assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'patient_id',
        'requested_by',
        'field',
        'current_value',
        'requested_value',
        'reason',
        'status',
        'reviewed_by',
        'response',
        'reviewed_at',
        'created_by',
        'updated_by',
    ];

    /**
     * Attribute casting.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Patient the request refers to.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * User who submitted the request.
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Admission user who reviewed the request.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
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
     * Determine whether the request is still awaiting review.
     */
    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }
}
