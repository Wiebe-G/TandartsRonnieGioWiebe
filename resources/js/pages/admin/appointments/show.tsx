import {Head, Link, router, usePage} from '@inertiajs/react';
import {
	ArrowLeft,
	CalendarClock,
	CircleAlert,
	CircleCheck,
	Trash2,
} from 'lucide-react';
import {useMemo, useState} from 'react';
import {
	statusBadgeClass,
	statusLabel,
} from '@/components/admin/appointment-status';
import SlotPicker from '@/components/admin/slot-picker';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import {Button} from '@/components/ui/button';
import {Card, CardContent, CardHeader, CardTitle} from '@/components/ui/card';
import {
	Dialog,
	DialogClose,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogTitle,
	DialogTrigger,
} from '@/components/ui/dialog';
import {Input} from '@/components/ui/input';
import {Label} from '@/components/ui/label';
import {Spinner} from '@/components/ui/spinner';
import type {BusyAppointment} from '@/lib/appointment-slots';
import {isWeekend, localDate, slotsFor} from '@/lib/appointment-slots';
import admin from '@/routes/admin';
import type {CalendarAppointment} from '@/types/admin';

type Props = {
	appointment: CalendarAppointment & {
		treatments: { treatment_id: number; name: string; duration: number }[];
	};
	rooms: { room_id: number; name: string }[];
	statuses: string[];
	busy: BusyAppointment[];
};

const longDate = new Intl.DateTimeFormat('nl-NL', {
	weekday: 'long',
	day: 'numeric',
	month: 'long',
	year: 'numeric',
});

const selectClass =
	'border-input dark:bg-input/30 h-9 w-full rounded-md border bg-transparent px-3 text-sm shadow-xs';

export default function ShowAppointment({
											appointment,
											rooms,
											statuses,
											busy,
										}: Props) {
	const {errors} = usePage().props;

	const originalDate = appointment.date.slice(0, 10);
	const originalStart = appointment.starttime.slice(0, 5);
	const treatment = appointment.treatments[0];
	const customerName = appointment.customer?.name ?? 'Onbekende klant';
	const formattedDate = longDate.format(new Date(`${originalDate}T00:00:00`));

	const [editMode, setEditMode] = useState(false);
	const [date, setDate] = useState(originalDate);
	const [starttime, setStarttime] = useState<string | null>(originalStart);
	const [status, setStatus] = useState(appointment.status);
	const [note, setNote] = useState(appointment.note ?? '');
	const [roomId, setRoomId] = useState(
		appointment.room_id ? String(appointment.room_id) : '',
	);
	const [saving, setSaving] = useState(false);

	const slots = useMemo(
		() =>
			date && !isWeekend(date)
				? slotsFor({
					date,
					durationMinutes: treatment?.duration ?? 30,
					busy,
					dentistId: appointment.dentist_id,
					assistantId: appointment.assistant_id,
					roomId: appointment.room_id,
					ignoreAppointmentId: appointment.appointment_id,
				})
				: [],
		[date, treatment, busy, appointment],
	);
	// The current time of the appointment itself is always fine to keep
	const keepsOwnTime = date === originalDate && starttime === originalStart;
	const timeIsValid =
		keepsOwnTime ||
		slots.some((slot) => slot.start === starttime && slot.free);

	const changeDate = (value: string) => {
		setDate(value);
		setStarttime(value === originalDate ? originalStart : null);
		if (value && !isWeekend(value)) {
			router.reload({only: ['busy'], data: {date: value}});
		}
	};

	const cancelEdit = () => {
		setEditMode(false);
		setDate(originalDate);
		setStarttime(originalStart);
		setStatus(appointment.status);
		setNote(appointment.note ?? '');
	};

	// A completed appointment is locked: only the note can still be changed
	const locked = appointment.status === 'completed';

	const save = () => {
		router.put(
			admin.appointments.update.url(appointment.appointment_id),
			locked ? {note} : {date, starttime, status, note},
			{
				preserveScroll: true,
				onStart: () => setSaving(true),
				onFinish: () => setSaving(false),
				onSuccess: () => setEditMode(false),
			},
		);
	};

	const saveRoom = () => {
		router.put(
			admin.appointments.room.url(appointment.appointment_id),
			{room_id: roomId},
			{preserveScroll: true},
		);
	};

	const remove = () => {
		router.delete(
			admin.appointments.destroy.url(appointment.appointment_id),
		);
	};

	// The server checks the time: only once the appointment has started
	const [completing, setCompleting] = useState(false);
	const complete = () => {
		router.patch(
			admin.appointments.complete.url(appointment.appointment_id),
			{},
			{
				preserveScroll: true,
				onStart: () => setCompleting(true),
				onFinish: () => setCompleting(false),
			},
		);
	};

	const details: [string, string][] = [
		['Klant', customerName],
		[
			'Behandeling',
			treatment
				? `${treatment.name} (${treatment.duration} min)`
				: 'Onbekende behandeling',
		],
		['Tandarts', appointment.dentist?.name ?? '-'],
		['Assistent', appointment.assistant?.name ?? 'Geen assistent'],
		['Datum', formattedDate],
		['Tijd', `${originalStart} - ${appointment.endtime.slice(0, 5)}`],
	];

	return (
		<>
			<Head title={`Afspraak ${customerName}`}/>

			<div className="flex flex-col gap-6 p-4">
				<div className="flex flex-wrap items-start justify-between gap-4">
					<div className="flex items-start gap-3">
						<Heading
							title={`${treatment?.name ?? 'Afspraak'} voor ${customerName}`}
							description={`${formattedDate}, ${originalStart}`}
						/>
						<span
							className={`mt-1 rounded-full px-3 py-0.5 text-sm font-medium ${statusBadgeClass(appointment.status)}`}
						>
							{statusLabel(appointment.status)}
						</span>
					</div>
					<div className="flex gap-2">
						<Button asChild variant="ghost">
							<Link href={admin.appointments.index()}>
								<ArrowLeft/>
								Terug
							</Link>
						</Button>
						{!editMode && appointment.status === 'scheduled' && (
							<Button onClick={complete} disabled={completing}>
								{completing ? <Spinner/> : <CircleCheck/>}
								Afronden
							</Button>
						)}
						{!editMode && (
							<Button
								variant="outline"
								onClick={() => setEditMode(true)}
							>
								<CalendarClock/>
								{locked ? 'Notitie bewerken' : 'Bewerken'}
							</Button>
						)}
						{appointment.status == "completed"
							? null
							: <Dialog>
								<DialogTrigger asChild>
									<Button variant="destructive">
										<Trash2/>
										Verwijderen
									</Button>
								</DialogTrigger>
								<DialogContent>
									<DialogTitle>Afspraak verwijderen?</DialogTitle>
									<DialogDescription>
										De afspraak van {customerName} op{' '}
										{formattedDate} om {originalStart} wordt
										definitief verwijderd.
									</DialogDescription>
									<DialogFooter className="gap-2">
										<DialogClose asChild>
											<Button variant="secondary">
												Annuleren
											</Button>
										</DialogClose>
										<Button
											variant="destructive"
											onClick={remove}
										>
											Verwijderen
										</Button>
									</DialogFooter>
								</DialogContent>
							</Dialog>
						}
					</div>
				</div>

				{
					errors.complete && (
						<p
							role="alert"
							className="flex items-center gap-2 rounded-md border border-red-500/40 bg-red-500/10 p-3 text-sm text-red-700 dark:text-red-400"
						>
							<CircleAlert className="size-4 shrink-0"/>
							{errors.complete}
						</p>
					)
				}

				<div className="grid gap-4 lg:grid-cols-[22rem_1fr]">
					<Card className="h-fit">
						<CardHeader>
							<CardTitle>Gegevens</CardTitle>
						</CardHeader>
						<CardContent className="space-y-4">
							<dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
								{details.map(([label, value]) => (
									<div key={label} className="contents">
										<dt className="text-muted-foreground">
											{label}
										</dt>
										<dd className="first-letter:uppercase">
											{value}
										</dd>
									</div>
								))}
							</dl>

							{locked ? (
								<dl className="grid grid-cols-[auto_1fr] gap-x-4 text-sm">
									<dt className="text-muted-foreground">
										Kamer
									</dt>
									<dd>
										{appointment.room?.name ?? 'Geen kamer'}
									</dd>
								</dl>
							) : (
								<div className="grid gap-2">
									<Label htmlFor="room">Kamer</Label>
									<div className="flex gap-2">
										<select
											id="room"
											value={roomId}
											onChange={(event) =>
												setRoomId(event.target.value)
											}
											className={selectClass}
										>
											<option value="" disabled>
												Kies een kamer
											</option>
											{rooms.map((room) => (
												<option
													key={room.room_id}
													value={room.room_id}
												>
													{room.name}
												</option>
											))}
										</select>
										<Button
											variant="outline"
											onClick={saveRoom}
											disabled={
												!roomId ||
												roomId ===
												String(
													appointment.room_id ??
													'',
												)
											}
										>
											Opslaan
										</Button>
									</div>
									<InputError message={errors.room_id}/>
								</div>
							)}
						</CardContent>
					</Card>

					<Card className="h-fit">
						<CardHeader>
							<CardTitle>
								{!editMode
									? 'Notitie'
									: locked
										? 'Notitie bewerken'
										: 'Afspraak bewerken'}
							</CardTitle>
						</CardHeader>
						<CardContent className="space-y-4">
							{!editMode ? (
								<p className="text-sm whitespace-pre-line">
									{appointment.note ? (
										appointment.note
									) : (
										<span className="text-muted-foreground">
											Geen notitie.
										</span>
									)}
								</p>
							) : (
								<>
									{locked && (
										<p className="text-muted-foreground text-sm">
											Deze afspraak is afgerond. Alleen de
											notitie kan nog aangepast worden.
										</p>
									)}
									{!locked && (
										<>
											<div className="grid gap-4 sm:grid-cols-2">
												<div className="grid gap-2">
													<Label htmlFor="date">
														Datum
													</Label>
													<Input
														id="date"
														type="date"
														min={localDate()}
														value={date}
														onChange={(event) =>
															changeDate(
																event.target
																	.value,
															)
														}
													/>
													<InputError
														message={errors.date}
													/>
												</div>
												<div className="grid gap-2">
													<Label htmlFor="status">
														Status
													</Label>
													<select
														id="status"
														value={status}
														onChange={(event) =>
															setStatus(
																event.target
																	.value,
															)
														}
														className={selectClass}
													>
														{statuses.map(
															(option) => (
																<option
																	key={option}
																	value={
																		option
																	}
																>
																	{statusLabel(
																		option,
																	)}
																</option>
															),
														)}
													</select>
													<InputError
														message={errors.status}
													/>
												</div>
											</div>

											{isWeekend(date) ? (
												<p className="rounded-md border border-red-500/40 bg-red-500/10 p-3 text-sm text-red-700 dark:text-red-400">
													Op zaterdag en zondag kunnen
													geen afspraken worden
													gepland. Kies een werkdag.
												</p>
											) : (
												<div className="grid gap-2">
													<Label>Begintijd</Label>
													<SlotPicker
														slots={slots}
														value={starttime}
														onChange={setStarttime}
													/>
													<InputError
														message={
															errors.starttime
														}
													/>
												</div>
											)}
										</>
									)}

									<div className="grid gap-2">
										<Label htmlFor="note">Notitie</Label>
										<textarea
											id="note"
											value={note}
											onChange={(event) =>
												setNote(event.target.value)
											}
											rows={3}
											maxLength={255}
											className="border-input dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
										/>
									</div>

									<div className="flex gap-2">
										<Button
											onClick={save}
											disabled={
												saving ||
												(!locked &&
													(!starttime ||
														!timeIsValid ||
														isWeekend(date)))
											}
										>
											{saving && <Spinner/>}
											Opslaan
										</Button>
										<Button
											variant="ghost"
											onClick={cancelEdit}
										>
											Annuleren
										</Button>
									</div>
								</>
							)}
						</CardContent>
					</Card>
				</div>
			</div>
		</>
	)
		;
}

ShowAppointment.layout = {
	breadcrumbs: [
		{title: 'Afspraken', href: admin.appointments.index()},
		{title: 'Afspraak', href: '#'},
	],
};
