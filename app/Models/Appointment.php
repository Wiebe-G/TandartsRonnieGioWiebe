<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static create(array $array)
 */
#[Table('appointment')]
#[Fillable('customer_id', 'dentist_id', 'assistant_id', 'date', 'starttime', 'endtime', 'status', 'note')]
class Appointment extends Model
{
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id', 'id');
    }

    public function treatment(): BelongsTo
    {
        // pak de info uit treatment tabel via appointment_treatment.appointment_id
        return $this->belongsTo(Treatment::class, 'treatment_id', 'id');
    }
}
