<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessionalAssignment extends Model
{
    use HasFactory;

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function healthStaff(): BelongsTo
    {
        return $this->belongsTo(HealthStaff::class);
    }
}