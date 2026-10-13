<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $role_id
 * @property string $name
 */
#[Fillable(['name'])]
class Role extends Model
{
    public const CUSTOMER = 'customer';

    protected $table = 'role';

    protected $primaryKey = 'role_id';

    public $timestamps = false;

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    public static function customerId(): int
    {
        return static::query()->where('name', self::CUSTOMER)->value('role_id');
    }
}
