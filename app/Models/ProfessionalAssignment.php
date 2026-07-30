<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessionalAssignment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'patient_id',
        'health_staff_id',
        'assignment_type',
        'start_date',
        'end_date',
        'status',
        'change_reason',
        'assigned_by',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function healthStaff(): BelongsTo
    {
        return $this->belongsTo(HealthStaff::class);
    }
}