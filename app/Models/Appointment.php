<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $array)
 */
#[Table('appointment')]
#[Fillable('customer_id', 'dentist_id', 'assistant_id', 'date', 'starttime', 'endtime', 'status', 'note')]
class Appointment extends Model
{
    //
}
