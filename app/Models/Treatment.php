<?php

namespace App\Models;

use Database\Factories\TreatmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $treatment_id
 * @property string $name
 * @property string|null $description
 * @property string $price
 * @property int $duration
 * @property bool $active
 */
#[Fillable(['name', 'description', 'price', 'duration', 'active'])]
class Treatment extends Model
{
    /** @use HasFactory<TreatmentFactory> */
    use HasFactory;

    protected $table = 'treatment';

    protected $primaryKey = 'treatment_id';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Appointment, $this>
     */
    public function appointments(): BelongsToMany
    {
        return $this->belongsToMany(Appointment::class, 'appointment_treatment', 'treatment_id', 'appointment_id');
    }
}
