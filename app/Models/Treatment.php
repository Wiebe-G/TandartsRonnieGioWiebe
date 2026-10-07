<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HigherOrderCollectionProxy;

/**
 * @method static create(array $array)
 *
 * @property HigherOrderCollectionProxy|mixed $duration
 */
#[Table('treatment')]
#[Fillable('name', 'description', 'price', 'duration', 'active')]

class Treatment extends Model
{
    protected $primaryKey = 'treatment_id';
    //
}
