<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The invoice for one completed appointment.
 *
 * @property int $invoice_id
 * @property int $appointment_id
 * @property CarbonImmutable $invoicedate
 * @property CarbonImmutable $expiration_date
 * @property string $amount
 * @property CarbonImmutable|null $sent_at
 * @property CarbonImmutable|null $paid_at
 * @property-read Appointment $appointment
 */
#[Fillable(['appointment_id', 'invoicedate', 'expiration_date', 'amount', 'sent_at', 'paid_at'])]
class Invoice extends Model
{
    /** Days the customer has to pay */
    public const PAYMENT_DAYS = 14;

    protected $table = 'invoice';

    protected $primaryKey = 'invoice_id';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'invoicedate' => 'date:Y-m-d',
            'expiration_date' => 'date:Y-m-d',
            'amount' => 'decimal:2',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    /**
     * Not paid yet while the payment date has passed.
     */
    public function isOverdue(): bool
    {
        return ! $this->isPaid() && $this->expiration_date->lt(today());
    }

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    /**
     * Invoice number shown to the customer, e.g. F2026-0007.
     */
    public function number(): string
    {
        return 'F'.$this->invoicedate->format('Y').'-'.str_pad((string) $this->invoice_id, 4, '0', STR_PAD_LEFT);
    }
}
