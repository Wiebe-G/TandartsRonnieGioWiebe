<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A customer's rating (1 to 5) of a dentist, optionally for one appointment.
 *
 * @property int $feedback_id
 * @property int $customer_id
 * @property int $dentist_id
 * @property int|null $appointment_id
 * @property int $rating
 * @property string|null $comment
 */
#[Table('feedback')]
#[Fillable(['customer_id', 'dentist_id', 'appointment_id', 'rating', 'comment'])]
class Feedback extends Model
{
    protected $primaryKey = 'feedback_id';

    /**
     * @return BelongsTo<User, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function dentist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }
}
