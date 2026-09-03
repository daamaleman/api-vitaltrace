<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The database table associated with this model.
     */
    protected $table = 'patients';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'person_id',
        'record_number',
        'admission_date',
        'administrative_status',
        'emergency_contact_name',
        'emergency_contact_phone',
        'administrative_notes',
        'registered_by',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'admission_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the person associated with the patient.
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
    
    public function professionalAssignments(): HasMany
    {
        return $this->hasMany(ProfessionalAssignment::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(Measurement::class);
    }

    public function latestMeasurement(): HasOne
    {
        return $this->hasOne(Measurement::class)->latestOfMany('measured_at');
    }

    public function nextAppointment(): HasOne
    {
        return $this->hasOne(Appointment::class)
            ->whereIn('status', ['SCHEDULED', 'CONFIRMED'])
            ->where('scheduled_at', '>', now())
            ->orderBy('scheduled_at');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * Get the user who registered the patient.
     */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /**
     * Get the user who created the patient record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the patient record.
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who soft deleted the patient record.
     */
    public function eraser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
