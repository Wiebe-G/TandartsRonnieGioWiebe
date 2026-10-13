<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * Customers and employees both live in this table; role_id tells them apart.
 *
 * @property int $id
 * @property int $role_id
 * @property int|null $employee_id
 * @property string $firstname
 * @property string $lastname
 * @property-read string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $phonenumber
 * @property Carbon|null $birthday
 * @property string $status
 * @property string|null $adress
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Role $role
 * @property-read User|null $employee
 *
 * @method static create(array $array)
 */
#[Fillable(['role_id', 'employee_id', 'firstname', 'lastname', 'email', 'password', 'phonenumber', 'birthday', 'status', 'adress'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
#[Appends(['name'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birthday' => 'date',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Full name, used wherever the UI shows a single name.
     *
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->firstname} {$this->lastname}"));
    }

    /**
     * The dentist assigned to this customer.
     *
     * @return BelongsTo<User, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * The customers assigned to this employee.
     *
     * @return HasMany<User, $this>
     */
    public function customers(): HasMany
    {
        return $this->hasMany(User::class, 'employee_id');
    }

    public function isCustomer(): bool
    {
        return $this->role->name === Role::CUSTOMER;
    }
}
