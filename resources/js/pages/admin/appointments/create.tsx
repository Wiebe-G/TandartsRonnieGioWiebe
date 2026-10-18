import { Head, Link, router, useForm } from '@inertiajs/react';
import {
	CalendarDays,
	Clock,
	DoorOpen,
	Stethoscope,
	User as UserIcon,
} from 'lucide-react';
import type { ComponentProps, ReactNode } from 'react';
import { useMemo } from 'react';
import SlotPicker from '@/components/admin/slot-picker';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { BusyAppointment } from '@/lib/appointment-slots';
import {
	isWeekend,
	localDate,
	slotLength,
	slotsFor,
} from '@/lib/appointment-slots';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';
import type {
	AdminTreatment,
	CalendarOptions,
	CustomerOption,
} from '@/types/admin';

type Props = {
	customers: CustomerOption[];
	treatments: AdminTreatment[];
	options: CalendarOptions;
	busy: BusyAppointment[];
};

const money = new Intl.NumberFormat('nl-NL', {
	style: 'currency',
	currency: 'EUR',
});
const longDate = new Intl.DateTimeFormat('nl-NL', {
	weekday: 'long',
	day: 'numeric',
	month: 'long',
	year: 'numeric',
});

function NativeSelect({ className, ...props }: ComponentProps<'select'>) {
	return (
		<select
			className={cn(
				'border-input dark:bg-input/30 flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-base shadow-xs outline-none md:text-sm',
				'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
				className,
			)}
			{...props}
		/>
	);
}

function Field({
	label,
	htmlFor,
	error,
	children,
}: {
	label: string;
	htmlFor: string;
	error?: string;
	children: ReactNode;
}) {
	return (
		<div className="grid gap-2">
			<Label htmlFor={htmlFor}>{label}</Label>
			{children}
			<InputError message={error} />
		</div>
	);
}

export default function CreateAppointment({
	customers,
	treatments,
	options,
	busy,
}: Props) {
	const form = useForm({
		customer_id: '',
		treatment_id: '',
		dentist_id: '',
		assistant_id: '',
		room_id: '',
		date: '',
		starttime: '',
		note: '',
	});
	const { data, setData, errors, processing } = form;

	const treatment = treatments.find(
		(item) => String(item.treatment_id) === data.treatment_id,
	);
	const customer = customers.find(
		(item) => String(item.id) === data.customer_id,
	);
	const dentist = options.dentists.find(
		(item) => String(item.id) === data.dentist_id,
	);
	const room = options.rooms.find(
		(item) => String(item.room_id) === data.room_id,
	);
	const weekend = data.date !== '' && isWeekend(data.date);

	const slots = useMemo(
		() =>
			treatment && data.date && !weekend
				? slotsFor({
						date: data.date,
						durationMinutes: treatment.duration,
						busy,
						dentistId: Number(data.dentist_id) || null,
						assistantId: Number(data.assistant_id) || null,
						roomId: Number(data.room_id) || null,
					})
				: [],
		[
			treatment,
			data.date,
			weekend,
			busy,
			data.dentist_id,
			data.assistant_id,
			data.room_id,
		],
	);
	// A chosen time that becomes taken (other dentist, longer treatment) simply stops counting as chosen
	const chosenSlot = slots.find(
		(slot) => slot.start === data.starttime && slot.free,
	);

	const chooseCustomer = (id: string) => {
		const picked = customers.find((item) => String(item.id) === id);
		// Start with the customer's own dentist, when they have one
		setData((previous) => ({
			...previous,
			customer_id: id,
			dentist_id:
				previous.dentist_id ||
				(picked?.employee_id ? String(picked.employee_id) : ''),
		}));
	};

	const chooseDate = (date: string) => {
		setData((previous) => ({ ...previous, date, starttime: '' }));
		if (date && !isWeekend(date)) {
			router.reload({ only: ['busy'], data: { date } });
		}
	};

	const missing = [
		!data.customer_id && 'klant',
		!data.treatment_id && 'behandeling',
		!data.dentist_id && 'tandarts',
		!data.assistant_id && 'assistent',
		!data.room_id && 'kamer',
		!chosenSlot && 'tijd',
	].filter(Boolean);

	const submit = (event: React.FormEvent) => {
		event.preventDefault();
		form.post(admin.appointments.store.url(), { preserveScroll: true });
	};

	return (
		<>
			<Head title="Nieuwe afspraak" />

			<form onSubmit={submit} className="flex flex-col gap-6 p-4">
				<Heading
					title="Nieuwe afspraak"
					description="Kies de klant en behandeling, daarna een vrij tijdslot"
				/>

				<div className="grid gap-4 lg:grid-cols-[1fr_20rem]">
					<div className="flex flex-col gap-4">
						<Card>
							<CardHeader>
								<CardTitle>1. Klant en behandeling</CardTitle>
							</CardHeader>
							<CardContent className="grid gap-4 sm:grid-cols-2">
								<Field
									label="Klant"
									htmlFor="customer"
									error={errors.customer_id}
								>
									<NativeSelect
										id="customer"
										value={data.customer_id}
										onChange={(event) =>
											chooseCustomer(event.target.value)
										}
										required
									>
										<option value="" disabled>
											Selecteer een klant
										</option>
										{customers.map((item) => (
											<option
												key={item.id}
												value={item.id}
											>
												{item.lastname},{' '}
												{item.firstname}
											</option>
										))}
									</NativeSelect>
								</Field>

								<Field
									label="Behandeling"
									htmlFor="treatment"
									error={errors.treatment_id}
								>
									<NativeSelect
										id="treatment"
										value={data.treatment_id}
										onChange={(event) =>
											setData(
												'treatment_id',
												event.target.value,
											)
										}
										required
									>
										<option value="" disabled>
											Selecteer een behandeling
										</option>
										{treatments.map((item) => (
											<option
												key={item.treatment_id}
												value={item.treatment_id}
											>
												{item.name} · {item.duration}{' '}
												min ·{' '}
												{money.format(
													Number(item.price),
												)}
											</option>
										))}
									</NativeSelect>
								</Field>
							</CardContent>
						</Card>

						<Card>
							<CardHeader>
								<CardTitle>2. Behandelaar en kamer</CardTitle>
							</CardHeader>
							<CardContent className="grid gap-4 sm:grid-cols-3">
								<Field
									label="Tandarts"
									htmlFor="dentist"
									error={errors.dentist_id}
								>
									<NativeSelect
										id="dentist"
										value={data.dentist_id}
										onChange={(event) =>
											setData(
												'dentist_id',
												event.target.value,
											)
										}
										required
									>
										<option value="" disabled>
											Selecteer een tandarts
										</option>
										{options.dentists.map((item) => (
											<option
												key={item.id}
												value={item.id}
											>
												{item.name}
											</option>
										))}
									</NativeSelect>
								</Field>

								<Field
									label="Assistent"
									htmlFor="assistant"
									error={errors.assistant_id}
								>
									<NativeSelect
										id="assistant"
										value={data.assistant_id}
										onChange={(event) =>
											setData(
												'assistant_id',
												event.target.value,
											)
										}
										required
									>
										<option value="" disabled>
											Selecteer een assistent
										</option>
										{options.assistants.map((item) => (
											<option
												key={item.id}
												value={item.id}
											>
												{item.name}
											</option>
										))}
									</NativeSelect>
								</Field>

								<Field
									label="Kamer"
									htmlFor="room"
									error={errors.room_id}
								>
									<NativeSelect
										id="room"
										value={data.room_id}
										onChange={(event) =>
											setData(
												'room_id',
												event.target.value,
											)
										}
										required
									>
										<option value="" disabled>
											Selecteer een kamer
										</option>
										{options.rooms.map((item) => (
											<option
												key={item.room_id}
												value={item.room_id}
											>
												{item.name}
											</option>
										))}
									</NativeSelect>
								</Field>
							</CardContent>
						</Card>

						<Card>
							<CardHeader>
								<CardTitle>3. Datum en tijd</CardTitle>
							</CardHeader>
							<CardContent className="grid gap-4">
								<Field
									label="Datum"
									htmlFor="date"
									error={errors.date}
								>
									<Input
										id="date"
										type="date"
										min={localDate()}
										value={data.date}
										onChange={(event) =>
											chooseDate(event.target.value)
										}
										className="max-w-xs"
										required
									/>
								</Field>
								<p className="text-muted-foreground -mt-2 text-sm">
									Maandag t/m vrijdag van 9:00 tot 17:00, in
									blokken van een half uur.
								</p>

								{weekend ? (
									<p className="rounded-md border border-red-500/40 bg-red-500/10 p-3 text-sm text-red-700 dark:text-red-400">
										Op zaterdag en zondag kunnen geen
										afspraken worden gepland. Kies een
										werkdag.
									</p>
								) : !data.date ||
								  !treatment ||
								  !data.dentist_id ? (
									<p className="text-muted-foreground rounded-md border border-dashed p-4 text-center text-sm">
										Kies eerst een behandeling, tandarts en
										datum om de vrije tijden te zien.
									</p>
								) : (
									<div className="grid gap-2">
										<Label>
											Begintijd
											<span className="text-muted-foreground font-normal">
												{' '}
												· {treatment.name} duurt{' '}
												{slotLength(treatment.duration)}{' '}
												minuten in de agenda
											</span>
										</Label>
										<SlotPicker
											slots={slots}
											value={data.starttime}
											onChange={(time) =>
												setData('starttime', time)
											}
										/>
										<InputError
											message={errors.starttime}
										/>
									</div>
								)}
							</CardContent>
						</Card>
					</div>

					<Card className="h-fit lg:sticky lg:top-4">
						<CardHeader>
							<CardTitle>Overzicht</CardTitle>
						</CardHeader>
						<CardContent className="space-y-4 text-sm">
							<ul className="space-y-2">
								<li className="flex items-center gap-2">
									<UserIcon className="text-muted-foreground size-4" />
									{customer ? (
										`${customer.firstname} ${customer.lastname}`
									) : (
										<span className="text-muted-foreground">
											Nog geen klant
										</span>
									)}
								</li>
								<li className="flex items-center gap-2">
									<Stethoscope className="text-muted-foreground size-4" />
									{treatment ? (
										treatment.name
									) : (
										<span className="text-muted-foreground">
											Nog geen behandeling
										</span>
									)}
									{dentist && (
										<span className="text-muted-foreground">
											bij {dentist.firstname}
										</span>
									)}
								</li>
								<li className="flex items-center gap-2">
									<CalendarDays className="text-muted-foreground size-4" />
									{data.date && !weekend ? (
										<span className="capitalize">
											{longDate.format(
												new Date(
													`${data.date}T00:00:00`,
												),
											)}
										</span>
									) : (
										<span className="text-muted-foreground">
											Nog geen datum
										</span>
									)}
								</li>
								<li className="flex items-center gap-2">
									<Clock className="text-muted-foreground size-4" />
									{chosenSlot ? (
										`${chosenSlot.start} - ${chosenSlot.end}`
									) : (
										<span className="text-muted-foreground">
											Nog geen tijd
										</span>
									)}
								</li>
								<li className="flex items-center gap-2">
									<DoorOpen className="text-muted-foreground size-4" />
									{room ? (
										room.name
									) : (
										<span className="text-muted-foreground">
											Nog geen kamer
										</span>
									)}
								</li>
							</ul>

							<Field
								label="Notitie (optioneel)"
								htmlFor="note"
								error={errors.note}
							>
								<textarea
									id="note"
									value={data.note}
									onChange={(event) =>
										setData('note', event.target.value)
									}
									rows={3}
									maxLength={255}
									className="border-input dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
								/>
							</Field>

							{missing.length > 0 && (
								<p className="text-muted-foreground">
									Nog kiezen: {missing.join(', ')}.
								</p>
							)}

							<div className="flex flex-col gap-2">
								<Button
									type="submit"
									disabled={processing || missing.length > 0}
								>
									{processing && <Spinner />}
									Afspraak inplannen
								</Button>
								<Button asChild variant="ghost">
									<Link href={admin.appointments.index()}>
										Annuleren
									</Link>
								</Button>
							</div>
						</CardContent>
					</Card>
				</div>
			</form>
		</>
	);
}

CreateAppointment.layout = {
	breadcrumbs: [
		{ title: 'Afspraken', href: admin.appointments.index() },
		{ title: 'Nieuwe afspraak', href: admin.appointments.create() },
	],
};
