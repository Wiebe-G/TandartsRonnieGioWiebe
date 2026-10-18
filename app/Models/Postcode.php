<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One row per address; users point to it through users.address.
 *
 * @property string $address
 * @property string $street
 * @property string $residence
 * @property string $house_number
 * @property string $city
 */
#[Fillable(['address', 'street', 'residence', 'house_number', 'city'])]
class Postcode extends Model
{
    protected $table = 'postcode';

    protected $primaryKey = 'address';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    /**
     * Build the key for an address, e.g. "1234 AB 12".
     */
    public static function keyFor(string $postcode, string $houseNumber): string
    {
        $postcode = strtoupper(preg_replace('/\s+/', '', $postcode) ?? $postcode);

        return substr($postcode, 0, 4).' '.substr($postcode, 4).' '.trim($houseNumber);
    }

    /**
     * The postcode part of the key, e.g. "1234 AB".
     */
    public function postcode(): string
    {
        return substr($this->address, 0, 7);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'address', 'address');
    }
}
