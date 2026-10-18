<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
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
 * @property string|null $address
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Role $role
 * @property-read User|null $employee
 * @property-read Postcode|null $postcode
 * @property-read Collection<int, Appointment> $appointments
 *
 * @method static create(array $array)
 */
#[Fillable(['role_id', 'employee_id', 'firstname', 'lastname', 'email', 'password', 'phonenumber', 'birthday', 'status', 'address'])]
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
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
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

    /**
     * Feedback customers gave about this dentist.
     *
     * @return HasMany<Feedback, $this>
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class, 'dentist_id');
    }

    /**
     * The full address row; users.address holds its key (e.g. "1234 AB 12").
     * Not called address(), that name is already the column itself.
     *
     * @return BelongsTo<Postcode, $this>
     */
    public function postcode(): BelongsTo
    {
        return $this->belongsTo(Postcode::class, 'address', 'address');
    }

    /**
     * The appointments this user has booked as a customer.
     *
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'customer_id');
    }

    /**
     * Dentists as {id, name} pairs, for the "choose your dentist" lists.
     *
     * @return list<array{id: int, name: string}>
     */
    public static function dentistOptions(): array
    {
        return array_values(static::query()
            ->where('role_id', Role::dentistId())
            ->orderBy('firstname')
            ->get()
            ->map(fn (User $dentist) => ['id' => $dentist->id, 'name' => $dentist->name])
            ->all());
    }

    /**
     * The dentist assigned to this customer, or null when they have none (or it is not a dentist).
     * Customers can only book appointments with this dentist.
     */
    public function assignedDentist(): ?User
    {
        $dentist = $this->employee;

        return $dentist && (int) $dentist->role_id === Role::dentistId() ? $dentist : null;
    }

    /**
     * What a customer still has to fill in (in their profile) before they can book an appointment.
     *
     * @return list<string> e.g. ['adres', 'telefoonnummer'], empty when everything is there
     */
    public function missingBookingDetails(): array
    {
        $address = $this->postcode;
        $addressComplete = $address
            && filled($address->street)
            && filled($address->house_number)
            && filled($address->city);

        return array_values(array_filter([
            $this->assignedDentist() ? null : 'tandarts',
            $addressComplete ? null : 'adres',
            filled($this->phonenumber) ? null : 'telefoonnummer',
        ]));
    }

    public function isAssistant(): bool
    {
        return $this->role?->name === Role::ASSISTANT;
    }

    /**
     * Making appointments and sending invoices is for assistants and admins only.
     */
    public function canPlanAndInvoice(): bool
    {
        return $this->isAssistant() || $this->isAdmin();
    }

    public function isCustomer(): bool
    {
        return $this->role?->name === Role::CUSTOMER;
    }

    public function isAdmin(): bool
    {
        return $this->role?->name === Role::ADMIN;
    }
}
