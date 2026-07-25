<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AdministrativeStaff model
 *
 * Represents an administrative staff member linked to a Person record.
 * Stores employment details and tracks creator/editor/eraser users via
 * created_by, updated_by and deleted_by foreign keys. Uses soft deletes.
 *
 * @property int $id
 * @property int $person_id
 * @property string $employee_code
 * @property string $type
 * @property string $position
 * @property bool $active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class AdministrativeStaff extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'administrative_staff';

    protected $fillable = [
        'person_id',
        'employee_code',
        'type',
        'position',
        'active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function person(): BelongsTo
    {
        // Relation to the Person model that this administrative staff record belongs to
        return $this->belongsTo(Person::class);
    }

    public function creator(): BelongsTo
    {
        // User who created this record
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        // User who last updated this record
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function eraser(): BelongsTo
    {
        // User who deleted (soft) this record
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
