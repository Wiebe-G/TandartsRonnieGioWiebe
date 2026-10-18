<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A customer's review of an appointment that took place.
 *
 * @property int $feedback_id
 * @property int $appointment_id
 * @property int $customer_id
 * @property bool $satisfied
 * @property string|null $comment
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Appointment $appointment
 * @property-read User $customer
 */
#[Fillable(['appointment_id', 'customer_id', 'satisfied', 'comment'])]
class Feedback extends Model
{
    protected $table = 'feedback';

    protected $primaryKey = 'feedback_id';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'satisfied' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
