import { Head, router, useForm } from '@inertiajs/react';
import { Check, Clock } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { cn } from '@/lib/utils';
import { create, index, store } from '@/routes/appointments';

type Treatment = {
	id: number;
	name: string;
	description: string | null;
	duration: number;
	price: string;
};

type Dentist = {
	id: number;
	name: string;
};

type Props = {
	treatments: Treatment[];
	dentists: Dentist[];
	defaultDentist: number | null;
	duration: number;
	slots: string[];
	alternatives: (Dentist & { freeSlots: number })[];
};

type BookingForm = {
	treatments: number[];
	dentist_id: number | null;
	date: string;
	starttime: string;
	note: string;
};

const euro = new Intl.NumberFormat('nl-NL', {
	style: 'currency',
	currency: 'EUR',
});

function todayString() {
	const now = new Date();

	return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
}

function addMinutes(time: string, minutes: number) {
	const [hours, mins] = time.split(':').map(Number);
	const total = hours * 60 + mins + minutes;

	return `${String(Math.floor(total / 60)).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}`;
}

function isWeekend(date: string) {
	const day = new Date(`${date}T00:00:00`).getDay();

	return day === 0 || day === 6;
}

export default function CreateAppointment({
	treatments,
	dentists,
	defaultDentist,
	duration,
	slots,
	alternatives,
}: Props) {
	// After choosing everything the customer first sees a summary, and only books from there.
	const [reviewing, setReviewing] = useState(false);

	const form = useForm<BookingForm>({
		treatments: [],
		dentist_id: defaultDentist ?? dentists[0]?.id ?? null,
		date: '',
		starttime: '',
		note: '',
	});

	const ready =
		form.data.treatments.length > 0 &&
		form.data.dentist_id !== null &&
		form.data.date !== '';

	// Ask the server for the free times again whenever a choice that affects them changes.
	function update(changes: Partial<BookingForm>) {
		const next = { ...form.data, ...changes, starttime: '' };
		form.setData(next);

		if (next.treatments.length > 0 && next.dentist_id && next.date) {
			router.reload({
				only: ['slots', 'duration', 'alternatives'],
				data: {
					treatments: next.treatments,
					dentist: next.dentist_id,
					date: next.date,
				},
			});
		}
	}

	function toggleTreatment(id: number) {
		const chosen = form.data.treatments.includes(id)
			? form.data.treatments.filter((treatment) => treatment !== id)
			: [...form.data.treatments, id];

		update({ treatments: chosen });
	}

	function submit(event: FormEvent) {
		event.preventDefault();

		if (!reviewing) {
			setReviewing(true);
			window.scrollTo({ top: 0 });

			return;
		}

		// Back to the form when the server rejects something, so the error is visible.
		form.post(store.url(), { onError: () => setReviewing(false) });
	}

	const chosenTreatments = treatments.filter((treatment) =>
		form.data.treatments.includes(treatment.id),
	);
	const chosenDentist = dentists.find(
		(dentist) => dentist.id === form.data.dentist_id,
	);
	const total = chosenTreatments.reduce(
		(sum, treatment) => sum + Number(treatment.price),
		0,
	);

	if (reviewing) {
		const endtime = addMinutes(form.data.starttime, duration);

		return (
			<>
				<Head title="Afspraak controleren" />
				<form
					onSubmit={submit}
					className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-6"
				>
					<Heading
						title="Controleer uw afspraak"
						description="Klopt alles? Bevestig dan uw afspraak. U ontvangt daarna een bevestiging per e-mail."
					/>

					<Card>
						<CardContent className="pt-6">
							<dl className="grid gap-4 text-sm sm:grid-cols-[10rem_1fr]">
								<dt className="text-muted-foreground">Datum</dt>
								<dd className="font-medium first-letter:uppercase">
									{new Date(
										`${form.data.date}T00:00:00`,
									).toLocaleDateString('nl-NL', {
										weekday: 'long',
										day: 'numeric',
										month: 'long',
										year: 'numeric',
									})}
								</dd>
								<dt className="text-muted-foreground">Tijd</dt>
								<dd className="font-medium">
									{form.data.starttime} – {endtime} (
									{duration} minuten)
								</dd>
								<dt className="text-muted-foreground">
									Tandarts
								</dt>
								<dd className="font-medium">
									{chosenDentist?.name}
								</dd>
								<dt className="text-muted-foreground">
									Behandeling
								</dt>
								<dd>
									<ul className="space-y-1">
										{chosenTreatments.map((treatment) => (
											<li
												key={treatment.id}
												className="flex justify-between gap-4"
											>
												<span className="font-medium">
													{treatment.name}
												</span>
												<span className="text-muted-foreground">
													{euro.format(
														Number(treatment.price),
													)}
												</span>
											</li>
										))}
									</ul>
								</dd>
								<dt className="text-muted-foreground">
									Geschatte kosten
								</dt>
								<dd className="font-medium">
									{euro.format(total)}
								</dd>
								{form.data.note && (
									<>
										<dt className="text-muted-foreground">
											Opmerking
										</dt>
										<dd className="italic">
											{form.data.note}
										</dd>
									</>
								)}
							</dl>
						</CardContent>
					</Card>

					<p className="text-muted-foreground text-sm">
						Annuleren kan online tot 48 uur van tevoren.
					</p>

					<div className="flex flex-wrap justify-end gap-2">
						<Button
							type="button"
							variant="outline"
							onClick={() => setReviewing(false)}
						>
							Wijzigen
						</Button>
						<Button type="submit" disabled={form.processing}>
							Afspraak bevestigen
						</Button>
					</div>
				</form>
			</>
		);
	}

	return (
		<>
			<Head title="Afspraak maken" />
			<form
				onSubmit={submit}
				className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-6"
			>
				<Heading
					title="Afspraak maken"
					description="Kies een behandeling, uw tandarts en een moment dat u uitkomt."
				/>

				<Card>
					<CardHeader>
						<CardTitle className="text-base">
							1. Behandeling
						</CardTitle>
					</CardHeader>
					<CardContent className="grid gap-2">
						{treatments.length === 0 && (
							<p className="text-muted-foreground text-sm">
								Er zijn op dit moment geen behandelingen
								beschikbaar.
							</p>
						)}
						{treatments.map((treatment) => {
							const chosen = form.data.treatments.includes(
								treatment.id,
							);

							return (
								<button
									key={treatment.id}
									type="button"
									onClick={() =>
										toggleTreatment(treatment.id)
									}
									aria-pressed={chosen}
									className={cn(
										'hover:bg-accent flex items-center justify-between gap-4 rounded-lg border p-3 text-left transition-colors',
										chosen && 'border-primary bg-primary/5',
									)}
								>
									<span className="flex items-center gap-3">
										<span
											className={cn(
												'flex size-5 shrink-0 items-center justify-center rounded border',
												chosen &&
													'border-primary bg-primary text-primary-foreground',
											)}
										>
											{chosen && (
												<Check className="size-3.5" />
											)}
										</span>
										<span>
											<span className="block font-medium">
												{treatment.name}
											</span>
											{treatment.description && (
												<span className="text-muted-foreground block text-sm">
													{treatment.description}
												</span>
											)}
										</span>
									</span>
									<span className="text-muted-foreground shrink-0 text-right text-sm">
										<span className="block">
											{treatment.duration} min
										</span>
										<span className="block">
											{euro.format(
												Number(treatment.price),
											)}
										</span>
									</span>
								</button>
							);
						})}
						<InputError
							message={
								form.errors.treatments ??
								form.errors['treatments.0']
							}
						/>
					</CardContent>
				</Card>

				<Card>
					<CardHeader>
						<CardTitle className="text-base">
							2. Tandarts en datum
						</CardTitle>
					</CardHeader>
					<CardContent className="grid gap-4 sm:grid-cols-2">
						<div className="grid gap-2">
							<Label htmlFor="dentist_id">Tandarts</Label>
							<NativeSelect
								id="dentist_id"
								value={form.data.dentist_id ?? ''}
								onChange={(event) =>
									update({
										dentist_id: Number(event.target.value),
									})
								}
							>
								{dentists.map((dentist) => (
									<option key={dentist.id} value={dentist.id}>
										{dentist.name}
										{dentist.id === defaultDentist
											? ' (uw tandarts)'
											: ''}
									</option>
								))}
							</NativeSelect>
							<InputError message={form.errors.dentist_id} />
						</div>

						<div className="grid gap-2">
							<Label htmlFor="date">Datum</Label>
							<Input
								id="date"
								type="date"
								min={todayString()}
								value={form.data.date}
								onChange={(event) =>
									update({ date: event.target.value })
								}
							/>
							<InputError message={form.errors.date} />
						</div>
					</CardContent>
				</Card>

				<Card>
					<CardHeader>
						<CardTitle className="flex items-center gap-2 text-base">
							3. Tijdstip
							{ready && (
								<span className="text-muted-foreground flex items-center gap-1 text-sm font-normal">
									<Clock className="size-3.5" />
									{duration} minuten
								</span>
							)}
						</CardTitle>
					</CardHeader>
					<CardContent className="space-y-2">
						{!ready ? (
							<p className="text-muted-foreground text-sm">
								Kies eerst een behandeling, tandarts en datum.
							</p>
						) : isWeekend(form.data.date) ? (
							<p className="text-muted-foreground text-sm">
								In het weekend is de praktijk gesloten. Kies een
								werkdag.
							</p>
						) : slots.length === 0 && alternatives.length > 0 ? (
							<div className="space-y-3">
								<p className="text-sm">
									{chosenDentist?.name ?? 'Deze tandarts'}{' '}
									heeft op deze dag geen tijd meer. Deze
									tandartsen hebben wel plek:
								</p>
								<div className="flex flex-wrap gap-2">
									{alternatives.map((alternative) => (
										<Button
											key={alternative.id}
											type="button"
											variant="outline"
											onClick={() =>
												update({
													dentist_id: alternative.id,
												})
											}
										>
											{alternative.name}
											<span className="text-muted-foreground text-xs">
												{alternative.freeSlots} vrije
												tijden
											</span>
										</Button>
									))}
								</div>
								<p className="text-muted-foreground text-sm">
									Of kies een andere datum.
								</p>
							</div>
						) : slots.length === 0 ? (
							<p className="text-muted-foreground text-sm">
								Op deze dag is niets meer vrij. Probeer een
								andere datum.
							</p>
						) : (
							<div className="grid grid-cols-3 gap-2 sm:grid-cols-6">
								{slots.map((slot) => (
									<Button
										key={slot}
										type="button"
										variant={
											form.data.starttime === slot
												? 'default'
												: 'outline'
										}
										onClick={() =>
											form.setData('starttime', slot)
										}
									>
										{slot}
									</Button>
								))}
							</div>
						)}
						<InputError message={form.errors.starttime} />
					</CardContent>
				</Card>

				<Card>
					<CardHeader>
						<CardTitle className="text-base">
							4. Opmerking (optioneel)
						</CardTitle>
					</CardHeader>
					<CardContent className="grid gap-2">
						<textarea
							id="note"
							rows={3}
							value={form.data.note}
							onChange={(event) =>
								form.setData('note', event.target.value)
							}
							placeholder="Bijvoorbeeld: ik heb last van een kies linksonder."
							className="border-input placeholder:text-muted-foreground focus-visible:ring-ring/50 rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:outline-none"
						/>
						<InputError message={form.errors.note} />
					</CardContent>
				</Card>

				<div className="flex flex-wrap items-center justify-between gap-4">
					<p className="text-muted-foreground text-sm">
						{form.data.treatments.length > 0 && (
							<>Geschatte kosten: {euro.format(total)}</>
						)}
					</p>
					<div className="flex gap-2">
						<Button
							type="button"
							variant="ghost"
							onClick={() => router.visit(index.url())}
						>
							Annuleren
						</Button>
						<Button
							type="submit"
							disabled={
								form.processing || form.data.starttime === ''
							}
						>
							Volgende: controleren
						</Button>
					</div>
				</div>
			</form>
		</>
	);
}

CreateAppointment.layout = {
	breadcrumbs: [
		{ title: 'Agenda', href: index() },
		{ title: 'Afspraak maken', href: create() },
	],
};
