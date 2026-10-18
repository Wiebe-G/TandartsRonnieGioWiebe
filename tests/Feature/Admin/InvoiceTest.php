<?php

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\User;
use App\Notifications\InvoiceSent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Carbon::setTestNow('2026-10-21 12:00:00');
    Notification::fake();

    $this->assistant = User::factory()->role(Role::ASSISTANT)->create();
    $this->dentist = User::factory()->role(Role::DENTIST)->create();
    $this->customer = User::factory()->withAddress()->create();

    $this->makeAppointment = function (string $status = 'completed') {
        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'dentist_id' => $this->dentist->id,
            'date' => '2026-10-20',
            'starttime' => '09:00:00',
            'endtime' => '10:00:00',
            'status' => $status,
        ]);
        $appointment->treatments()->attach([
            Treatment::create(['name' => 'Controle', 'price' => 28.50, 'duration' => 30, 'active' => true])->treatment_id,
            Treatment::create(['name' => 'Röntgenfoto', 'price' => 18, 'duration' => 15, 'active' => true])->treatment_id,
        ]);

        return $appointment;
    };
});

afterEach(fn () => Carbon::setTestNow());

test('the invoices page lists completed appointments that still need an invoice', function () {
    ($this->makeAppointment)();
    ($this->makeAppointment)('scheduled');

    $this->actingAs($this->assistant)
        ->get(route('admin.invoices.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/invoices/index')
            ->has('toInvoice', 1)
            ->where('toInvoice.0.total', '46.50')
            ->has('invoices', 0));
});

test('assistants can make an invoice for a completed appointment', function () {
    $appointment = ($this->makeAppointment)();

    $this->actingAs($this->assistant)
        ->post(route('admin.invoices.store', $appointment))
        ->assertSessionHasNoErrors();

    $invoice = Invoice::query()->sole();
    expect($invoice->amount)->toBe('46.50')
        ->and($invoice->invoicedate->toDateString())->toBe('2026-10-21')
        ->and($invoice->expiration_date->toDateString())->toBe('2026-11-04')
        ->and($invoice->sent_at)->toBeNull()
        ->and($invoice->number())->toBe('F2026-'.str_pad((string) $invoice->invoice_id, 4, '0', STR_PAD_LEFT));

    $this->actingAs($this->assistant)
        ->get(route('admin.invoices.show', $invoice))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/invoices/show')
            ->where('invoice.amount', '46.50')
            ->has('invoice.appointment.treatments', 2)
            ->where('invoice.customer.address.street', fn ($street) => str_starts_with($street, 'Dorpsstraat')));
});

test('an appointment gets one invoice, and only once it is completed', function () {
    $completed = ($this->makeAppointment)();
    $scheduled = ($this->makeAppointment)('scheduled');

    $this->actingAs($this->assistant)->post(route('admin.invoices.store', $completed))->assertSessionHasNoErrors();
    $this->actingAs($this->assistant)->post(route('admin.invoices.store', $completed))->assertSessionHasErrors('invoice');
    $this->actingAs($this->assistant)->post(route('admin.invoices.store', $scheduled))->assertSessionHasErrors('invoice');

    expect(Invoice::query()->count())->toBe(1);
});

test('sending emails the invoice to the customer', function () {
    $appointment = ($this->makeAppointment)();
    $this->actingAs($this->assistant)->post(route('admin.invoices.store', $appointment));
    $invoice = Invoice::query()->sole();

    $this->actingAs($this->assistant)
        ->post(route('admin.invoices.send', $invoice))
        ->assertSessionHasNoErrors();

    expect($invoice->refresh()->sent_at)->not->toBeNull();

    Notification::assertSentTo($this->customer, InvoiceSent::class, function (InvoiceSent $notification) use ($invoice) {
        $mail = $notification->toMail($this->customer)->render();

        return $notification->invoice->is($invoice)
            && str_contains($mail, $invoice->number())
            && str_contains($mail, '€ 46,50')
            && str_contains($mail, '4 november 2026')
            && str_contains($mail, 'Controle');
    });
});

test('only assistants and admins can invoice', function (string $role, bool $allowed) {
    $appointment = ($this->makeAppointment)();
    $user = User::factory()->role($role)->create();

    $page = $this->actingAs($user)->get(route('admin.invoices.index'));
    $make = $this->actingAs($user)->post(route('admin.invoices.store', $appointment));

    if ($allowed) {
        $page->assertOk();
        $make->assertSessionHasNoErrors();
    } else {
        $page->assertForbidden();
        $make->assertForbidden();
        expect(Invoice::query()->count())->toBe(0);
    }
})->with([
    'assistant' => [Role::ASSISTANT, true],
    'admin' => [Role::ADMIN, true],
    'dentist' => [Role::DENTIST, false],
    'customer' => [Role::CUSTOMER, false],
]);

test('an invoiced appointment cannot be deleted', function () {
    $appointment = ($this->makeAppointment)();
    $this->actingAs($this->assistant)->post(route('admin.invoices.store', $appointment));

    $this->actingAs($this->assistant)
        ->delete(route('admin.appointments.destroy', $appointment))
        ->assertSessionHasErrors('complete');

    expect(Appointment::query()->find($appointment->appointment_id))->not->toBeNull();
});

test('a new invoice is pending, and can be marked as paid and back', function () {
    $appointment = ($this->makeAppointment)();
    $this->actingAs($this->assistant)->post(route('admin.invoices.store', $appointment));
    $invoice = Invoice::query()->sole();

    $this->actingAs($this->assistant)
        ->get(route('admin.invoices.show', $invoice))
        ->assertInertia(fn ($page) => $page->where('invoice.status', 'pending')->where('invoice.overdue', false));

    $this->actingAs($this->assistant)
        ->patch(route('admin.invoices.payment', $invoice), ['paid' => true])
        ->assertSessionHasNoErrors();
    expect($invoice->refresh()->isPaid())->toBeTrue();

    $this->actingAs($this->assistant)
        ->get(route('admin.invoices.index'))
        ->assertInertia(fn ($page) => $page->where('invoices.0.status', 'paid'));

    $this->actingAs($this->assistant)
        ->patch(route('admin.invoices.payment', $invoice), ['paid' => false])
        ->assertSessionHasNoErrors();
    expect($invoice->refresh()->isPaid())->toBeFalse();
});

test('an unpaid invoice past its payment date is overdue', function () {
    $appointment = ($this->makeAppointment)();
    $this->actingAs($this->assistant)->post(route('admin.invoices.store', $appointment));
    $invoice = Invoice::query()->sole();

    Carbon::setTestNow('2026-11-05 09:00:00'); // payment date was 4 November

    expect($invoice->refresh()->isOverdue())->toBeTrue();

    $invoice->update(['paid_at' => now()]);
    expect($invoice->refresh()->isOverdue())->toBeFalse();
});

test('dentists cannot change the payment status', function () {
    $appointment = ($this->makeAppointment)();
    $this->actingAs($this->assistant)->post(route('admin.invoices.store', $appointment));

    $this->actingAs($this->dentist)
        ->patch(route('admin.invoices.payment', Invoice::query()->sole()), ['paid' => true])
        ->assertForbidden();
});

test('customers see the invoices that were sent to them', function () {
    $sent = ($this->makeAppointment)();
    $notSent = ($this->makeAppointment)();
    $this->actingAs($this->assistant)->post(route('admin.invoices.store', $sent));
    $this->actingAs($this->assistant)->post(route('admin.invoices.store', $notSent));
    $sentInvoice = Invoice::query()->where('appointment_id', $sent->appointment_id)->sole();
    $this->actingAs($this->assistant)->post(route('admin.invoices.send', $sentInvoice));

    $this->actingAs($this->customer)
        ->get(route('customer.invoices.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('customer/invoices/index')
            ->has('invoices', 1)
            ->where('invoices.0.invoice_id', $sentInvoice->invoice_id)
            ->where('invoices.0.status', 'pending'));

    $this->actingAs($this->customer)
        ->get(route('customer.invoices.show', $sentInvoice))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('customer/invoices/show')
            ->where('invoice.amount', '46.50')
            ->has('invoice.appointment.treatments', 2));
});

test('customers cannot see invoices that were not sent, or that belong to someone else', function () {
    $appointment = ($this->makeAppointment)();
    $this->actingAs($this->assistant)->post(route('admin.invoices.store', $appointment));
    $invoice = Invoice::query()->sole();

    // Not sent yet
    $this->actingAs($this->customer)->get(route('customer.invoices.show', $invoice))->assertNotFound();

    $this->actingAs($this->assistant)->post(route('admin.invoices.send', $invoice));
    $this->actingAs($this->customer)->get(route('customer.invoices.show', $invoice))->assertOk();

    // Another customer
    $other = User::factory()->create();
    $this->actingAs($other)->get(route('customer.invoices.show', $invoice))->assertNotFound();
    $this->actingAs($other)
        ->get(route('customer.invoices.index'))
        ->assertInertia(fn ($page) => $page->has('invoices', 0));
});

test('the invoice email links to the customer invoice page', function () {
    $appointment = ($this->makeAppointment)();
    $this->actingAs($this->assistant)->post(route('admin.invoices.store', $appointment));
    $invoice = Invoice::query()->sole();
    $this->actingAs($this->assistant)->post(route('admin.invoices.send', $invoice));

    Notification::assertSentTo($this->customer, InvoiceSent::class, fn (InvoiceSent $notification) => $notification->toMail($this->customer)->actionUrl === route('customer.invoices.show', $invoice));
});

test('customers can pay their own sent invoice once', function () {
    $appointment = ($this->makeAppointment)();
    $this->actingAs($this->assistant)->post(route('admin.invoices.store', $appointment));
    $invoice = Invoice::query()->sole();

    // Not sent yet: not theirs to pay
    $this->actingAs($this->customer)->post(route('customer.invoices.pay', $invoice))->assertNotFound();

    $this->actingAs($this->assistant)->post(route('admin.invoices.send', $invoice));

    $this->actingAs(User::factory()->create())->post(route('customer.invoices.pay', $invoice))->assertNotFound();

    $this->actingAs($this->customer)
        ->post(route('customer.invoices.pay', $invoice))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('customer.invoices.show', $invoice));
    expect($invoice->refresh()->isPaid())->toBeTrue();

    $this->actingAs($this->customer)
        ->post(route('customer.invoices.pay', $invoice))
        ->assertSessionHasErrors('payment');
});
