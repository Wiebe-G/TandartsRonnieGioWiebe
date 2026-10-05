<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $array)
 */
#[Table('Treatment')]
#[Fillable('name', 'description', 'price', 'duration', 'active')]

class Treatment extends Model
{
    //
}
