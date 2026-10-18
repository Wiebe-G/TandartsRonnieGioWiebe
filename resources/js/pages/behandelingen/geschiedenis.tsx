import { Head, Link, useForm } from '@inertiajs/react';
import {
	CalendarPlus,
	MessageSquare,
	ThumbsDown,
	ThumbsUp,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { create, feedback as feedbackRoute } from '@/routes/appointments';
import { history } from '@/routes/treatments';

type Feedback = {
	satisfied: boolean;
	comment: string | null;
};

type Appointment = {
	id: number;
	date: string;
	starttime: string;
	endtime: string;
	note: string | null;
	dentist: string;
	assistant: string | null;
	treatments: { name: string; price: string }[];
	feedback: Feedback | null;
};

const euro = new Intl.NumberFormat('nl-NL', {
	style: 'currency',
	currency: 'EUR',
});

function formatDate(date: string) {
	return new Date(`${date}T00:00:00`).toLocaleDateString('nl-NL', {
		weekday: 'long',
		day: 'numeric',
		month: 'long',
		year: 'numeric',
	});
}

function SatisfiedBadge({ satisfied }: { satisfied: boolean }) {
	return satisfied ? (
		<Badge variant="secondary">
			<ThumbsUp />
			Tevreden
		</Badge>
	) : (
		<Badge variant="outline">
			<ThumbsDown />
			Niet tevreden
		</Badge>
	);
}

function FeedbackForm({
	appointment,
	onDone,
}: {
	appointment: Appointment;
	onDone: () => void;
}) {
	const form = useForm<{ satisfied: boolean | null; comment: string }>({
		satisfied: appointment.feedback?.satisfied ?? null,
		comment: appointment.feedback?.comment ?? '',
	});

	function submit(event: FormEvent) {
		event.preventDefault();
		form.post(feedbackRoute.url(appointment.id), {
			preserveScroll: true,
			onSuccess: onDone,
		});
	}

	const choices = [
		{ value: true, label: 'Tevreden', icon: ThumbsUp },
		{ value: false, label: 'Niet tevreden', icon: ThumbsDown },
	];

	return (
		<form
			onSubmit={submit}
			className="bg-muted/30 space-y-4 rounded-lg border p-4"
		>
			<div className="space-y-2">
				<p className="text-sm font-medium">
					Hoe tevreden bent u over uw behandeling op{' '}
					{formatDate(appointment.date)}?
				</p>
				<div className="flex flex-wrap gap-2">
					{choices.map(({ value, label, icon: Icon }) => (
						<Button
							key={label}
							type="button"
							variant="outline"
							aria-pressed={form.data.satisfied === value}
							onClick={() => form.setData('satisfied', value)}
							className={cn(
								form.data.satisfied === value &&
									(value
										? 'border-green-600 bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300'
										: 'border-red-600 bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300'),
							)}
						>
							<Icon />
							{label}
						</Button>
					))}
				</div>
				<InputError message={form.errors.satisfied} />
			</div>
			<div className="space-y-1">
				<label
					htmlFor={`comment-${appointment.id}`}
					className="text-sm font-medium"
				>
					Notitie (optioneel)
				</label>
				<textarea
					id={`comment-${appointment.id}`}
					rows={3}
					value={form.data.comment}
					onChange={(event) =>
						form.setData('comment', event.target.value)
					}
					placeholder="Wat ging er goed, en wat kan beter?"
					className="border-input bg-background placeholder:text-muted-foreground focus-visible:ring-ring/50 w-full rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:outline-none"
				/>
				<InputError message={form.errors.comment} />
			</div>
			<div className="flex gap-2">
				<Button
					type="submit"
					size="sm"
					disabled={form.processing || form.data.satisfied === null}
				>
					Feedback versturen
				</Button>
				<Button
					type="button"
					size="sm"
					variant="ghost"
					onClick={onDone}
				>
					Annuleren
				</Button>
			</div>
		</form>
	);
}

function HistoryItem({ appointment }: { appointment: Appointment }) {
	const [editing, setEditing] = useState(false);
	const total = appointment.treatments.reduce(
		(sum, treatment) => sum + Number(treatment.price),
		0,
	);

	return (
		<Card>
			<CardHeader className="flex flex-row flex-wrap items-start justify-between gap-2">
				<div>
					<CardTitle className="text-base first-letter:uppercase">
						{formatDate(appointment.date)}
					</CardTitle>
					<p className="text-muted-foreground text-sm">
						{appointment.starttime} – {appointment.endtime} ·
						Tandarts: {appointment.dentist}
						{appointment.assistant &&
							` · Assistent: ${appointment.assistant}`}
					</p>
				</div>
				{appointment.feedback && !editing && (
					<SatisfiedBadge
						satisfied={appointment.feedback.satisfied}
					/>
				)}
			</CardHeader>
			<CardContent className="space-y-4">
				{appointment.treatments.length > 0 ? (
					<ul className="divide-y rounded-lg border text-sm">
						{appointment.treatments.map((treatment) => (
							<li
								key={treatment.name}
								className="flex justify-between px-3 py-2"
							>
								<span>{treatment.name}</span>
								<span className="text-muted-foreground">
									{euro.format(Number(treatment.price))}
								</span>
							</li>
						))}
						{appointment.treatments.length > 1 && (
							<li className="flex justify-between px-3 py-2 font-medium">
								<span>Totaal</span>
								<span>{euro.format(total)}</span>
							</li>
						)}
					</ul>
				) : (
					<p className="text-muted-foreground text-sm">
						Geen behandelingen vastgelegd.
					</p>
				)}

				{appointment.note && (
					<p className="text-sm italic">
						Uw opmerking: {appointment.note}
					</p>
				)}

				{editing ? (
					<FeedbackForm
						appointment={appointment}
						onDone={() => setEditing(false)}
					/>
				) : appointment.feedback ? (
					<div className="flex flex-wrap items-start justify-between gap-2 text-sm">
						<p className="text-muted-foreground">
							{appointment.feedback.comment
								? `“${appointment.feedback.comment}”`
								: 'Bedankt, u heeft feedback gegeven.'}
						</p>
						<Button
							variant="ghost"
							size="sm"
							onClick={() => setEditing(true)}
						>
							Feedback aanpassen
						</Button>
					</div>
				) : (
					<Button
						variant="outline"
						size="sm"
						onClick={() => setEditing(true)}
					>
						<MessageSquare />
						Feedback geven
					</Button>
				)}
			</CardContent>
		</Card>
	);
}

export default function TreatmentHistory({
	appointments,
}: {
	appointments: Appointment[];
}) {
	return (
		<>
			<Head title="Behandelingsgeschiedenis" />
			<div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-6">
				<Heading
					title="Behandelingsgeschiedenis"
					description="Alle behandelingen die u bij ons heeft gehad. Laat ons gerust weten hoe het ging."
				/>

				{appointments.length === 0 ? (
					<Card>
						<CardContent className="space-y-4 py-6 text-center">
							<p className="text-muted-foreground">
								U heeft nog geen behandelingen bij ons gehad.
							</p>
							<Button asChild>
								<Link href={create()}>
									<CalendarPlus />
									Afspraak maken
								</Link>
							</Button>
						</CardContent>
					</Card>
				) : (
					appointments.map((appointment) => (
						<HistoryItem
							key={appointment.id}
							appointment={appointment}
						/>
					))
				)}
			</div>
		</>
	);
}

TreatmentHistory.layout = {
	breadcrumbs: [{ title: 'Behandelingsgeschiedenis', href: history() }],
};
