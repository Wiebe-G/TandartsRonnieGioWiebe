<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $appointment_id
 * @property int $customer_id
 * @property int $dentist_id
 * @property int|null $assistant_id
 * @property int|null $room_id
 * @property CarbonImmutable $date
 * @property string $starttime
 * @property string $endtime
 * @property string $status
 * @property string|null $note
 * @property-read User $customer
 * @property-read User $dentist
 * @property-read User|null $assistant
 * @property-read Room|null $room
 * @property-read Feedback|null $feedback
 *
 * @method static create(array $array)
 * @method static orderBy(string $string)
 */
#[Fillable(['customer_id', 'dentist_id', 'assistant_id', 'room_id', 'date', 'starttime', 'endtime', 'status', 'note'])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    public const SCHEDULED = 'scheduled';

    public const COMPLETED = 'completed';

    public const CANCELLED = 'cancelled';

    /**
     * How long before the start a customer can still cancel online.
     */
    public const CANCEL_HOURS = 48;

    protected $table = 'appointment';

    protected $primaryKey = 'appointment_id';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Sent to the pages as "2026-10-21", which is what all of them compare against
            'date' => 'date:Y-m-d',
        ];
    }

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

    /**
     * @return BelongsTo<User, $this>
     */
    public function assistant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assistant_id');
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    /**
     * @return BelongsToMany<Treatment, $this>
     */
    public function treatments(): BelongsToMany
    {
        // pak de info uit treatment tabel via appointment_treatment.appointment_id
        return $this->belongsToMany(Treatment::class, 'appointment_treatment', 'appointment_id', 'treatment_id');
    }

    /**
     * @return HasOne<Feedback, $this>
     */
    public function feedback(): HasOne
    {
        return $this->hasOne(Feedback::class, 'appointment_id');
    }

    /**
     * @return HasOne<Invoice, $this>
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'appointment_id');
    }

    public const LOCKED_MESSAGE = 'Deze afspraak is afgerond. Alleen de notitie kan nog aangepast worden.';

    /**
     * A completed appointment can no longer be changed, except for its note.
     */
    public function isLocked(): bool
    {
        return $this->status === self::COMPLETED;
    }

    /**
     * Moment the appointment starts (date + start time).
     */
    public function startsAt(): CarbonImmutable
    {
        return $this->date->copy()->setTimeFromTimeString($this->starttime);
    }

    /**
     * Still scheduled and not started yet.
     */
    public function isUpcoming(): bool
    {
        return $this->status === self::SCHEDULED && $this->startsAt()->isFuture();
    }

    /**
     * Customers may only cancel up to CANCEL_HOURS before the start.
     */
    public function cancellableByCustomer(): bool
    {
        return $this->isUpcoming() && now()->addHours(self::CANCEL_HOURS)->lte($this->startsAt());
    }

    /**
     * The appointment took place: its day has passed and it was not cancelled.
     */
    public function tookPlace(): bool
    {
        // Marked as completed counts straight away, also on the day itself
        return $this->status === self::COMPLETED
            || ($this->status !== self::CANCELLED && $this->date->lt(today()));
    }

    /**
     * Appointments that took place, see tookPlace().
     *
     * @param  Builder<Appointment>  $query
     */
    #[Scope]
    protected function past(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('status', self::COMPLETED)
            ->orWhere(fn (Builder $query) => $query
                ->where('status', '!=', self::CANCELLED)
                ->whereDate('date', '<', today())));
    }
}
