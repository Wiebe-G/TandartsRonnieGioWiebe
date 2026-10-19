import { Head, useForm } from '@inertiajs/react';
import { MessageSquare, Pencil, ThumbsDown, ThumbsUp } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { feedback as feedbackRoute } from '@/routes/appointments';
import { feedback as feedbackPage } from '@/routes/customer';

type Appointment = {
	id: number;
	date: string;
	starttime: string;
	dentist: string | null;
	treatments: string[];
};

type Feedback = {
	id: number;
	satisfied: boolean;
	comment: string | null;
	updated_at: string | null;
	appointment: Appointment;
};

type Props = {
	toReview: Appointment[];
	feedback: Feedback[];
};

const longDate = new Intl.DateTimeFormat('nl-NL', {
	weekday: 'long',
	day: 'numeric',
	month: 'long',
	year: 'numeric',
});

function describe(appointment: Appointment): string {
	return [
		longDate.format(new Date(`${appointment.date}T00:00:00`)),
		appointment.treatments.join(', ') || null,
		appointment.dentist ? `bij ${appointment.dentist}` : null,
	]
		.filter(Boolean)
		.join(' · ');
}

/** Satisfied or not, plus an optional comment; saved on the appointment (only your own). */
function FeedbackForm({
	appointment,
	initial,
	onDone,
}: {
	appointment: Appointment;
	initial?: Feedback;
	onDone?: () => void;
}) {
	const form = useForm<{ satisfied: boolean | null; comment: string }>({
		satisfied: initial?.satisfied ?? null,
		comment: initial?.comment ?? '',
	});

	function submit(event: FormEvent) {
		event.preventDefault();
		form.post(feedbackRoute.url(appointment.id), {
			preserveScroll: true,
			onSuccess: () => onDone?.(),
		});
	}

	const choices = [
		{ value: true, label: 'Tevreden', icon: ThumbsUp },
		{ value: false, label: 'Niet tevreden', icon: ThumbsDown },
	];

	return (
		<form onSubmit={submit} className="space-y-3">
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
			<textarea
				aria-label="Opmerking"
				rows={2}
				value={form.data.comment}
				onChange={(event) =>
					form.setData('comment', event.target.value)
				}
				placeholder="Wat ging er goed, en wat kan beter? (optioneel)"
				className="border-input bg-background placeholder:text-muted-foreground focus-visible:ring-ring/50 w-full rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:outline-none"
			/>
			<InputError message={form.errors.comment} />
			<div className="flex gap-2">
				<Button
					type="submit"
					size="sm"
					disabled={form.processing || form.data.satisfied === null}
				>
					Feedback versturen
				</Button>
				{onDone && (
					<Button
						type="button"
						size="sm"
						variant="ghost"
						onClick={onDone}
					>
						Annuleren
					</Button>
				)}
			</div>
		</form>
	);
}

function GivenFeedback({ item }: { item: Feedback }) {
	const [editing, setEditing] = useState(false);

	return (
		<li className="space-y-3 py-4">
			<div className="flex flex-wrap items-start justify-between gap-2">
				<div className="space-y-1">
					<p className="text-sm font-medium first-letter:uppercase">
						{describe(item.appointment)}
					</p>
					{!editing && (
						<p className="text-muted-foreground text-sm">
							{item.comment
								? `“${item.comment}”`
								: 'Geen opmerking.'}
						</p>
					)}
				</div>
				<div className="flex items-center gap-2">
					{item.satisfied ? (
						<Badge className="bg-green-500/15 text-green-700 dark:text-green-400">
							<ThumbsUp />
							Tevreden
						</Badge>
					) : (
						<Badge className="bg-red-500/15 text-red-700 dark:text-red-400">
							<ThumbsDown />
							Niet tevreden
						</Badge>
					)}
					{!editing && (
						<Button
							size="sm"
							variant="ghost"
							onClick={() => setEditing(true)}
							aria-label="Feedback aanpassen"
						>
							<Pencil />
						</Button>
					)}
				</div>
			</div>
			{editing && (
				<FeedbackForm
					appointment={item.appointment}
					initial={item}
					onDone={() => setEditing(false)}
				/>
			)}
		</li>
	);
}

export default function MyFeedback({ toReview, feedback }: Props) {
	return (
		<>
			<Head title="Mijn feedback" />

			<div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-6">
				<Heading
					title="Mijn feedback"
					description="Laat weten hoe tevreden u bent over uw behandelingen. Alleen u en de praktijk zien uw feedback."
				/>

				{toReview.length > 0 && (
					<Card>
						<CardHeader>
							<CardTitle>
								Nog te beoordelen ({toReview.length})
							</CardTitle>
						</CardHeader>
						<CardContent>
							<ul className="divide-y">
								{toReview.map((appointment) => (
									<li
										key={appointment.id}
										className="space-y-3 py-4 first:pt-0"
									>
										<p className="text-sm font-medium first-letter:uppercase">
											{describe(appointment)}
										</p>
										<FeedbackForm
											appointment={appointment}
										/>
									</li>
								))}
							</ul>
						</CardContent>
					</Card>
				)}

				<Card>
					<CardHeader>
						<CardTitle>
							Mijn beoordelingen ({feedback.length})
						</CardTitle>
					</CardHeader>
					<CardContent>
						{feedback.length === 0 ? (
							<p className="text-muted-foreground flex items-center gap-2 text-sm">
								<MessageSquare className="size-4" />U heeft nog
								geen feedback gegeven.
							</p>
						) : (
							<ul className="divide-y">
								{feedback.map((item) => (
									<GivenFeedback key={item.id} item={item} />
								))}
							</ul>
						)}
					</CardContent>
				</Card>
			</div>
		</>
	);
}

MyFeedback.layout = {
	breadcrumbs: [{ title: 'Mijn feedback', href: feedbackPage() }],
};
