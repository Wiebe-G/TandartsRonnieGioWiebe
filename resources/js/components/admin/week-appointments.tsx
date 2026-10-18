import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Clock, DoorOpen } from 'lucide-react';
import { useState } from 'react';
import { statusClass } from '@/components/admin/appointment-status';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';
import type { CalendarAppointment as Appointment } from '@/types/admin';

const dayFormat = new Intl.DateTimeFormat('nl-NL', {
	weekday: 'short',
	day: 'numeric',
	month: 'short',
});
const rangeFormat = new Intl.DateTimeFormat('nl-NL', {
	day: 'numeric',
	month: 'long',
	year: 'numeric',
});

// YYYY-MM-DD in local time, the same format as the date column
function toDateString(date: Date): string {
	return date.toLocaleDateString('sv-SE');
}

function mondayOf(date: Date): Date {
	const monday = new Date(
		date.getFullYear(),
		date.getMonth(),
		date.getDate(),
	);
	monday.setDate(monday.getDate() - ((monday.getDay() + 6) % 7));

	return monday;
}

export function WeekAppointments({
	appointments,
}: {
	appointments: Appointment[];
}) {
	const [weekStart, setWeekStart] = useState(() => mondayOf(new Date()));

	const today = toDateString(new Date());
	const days = Array.from({ length: 7 }, (_, offset) => {
		const day = new Date(weekStart);
		day.setDate(day.getDate() + offset);

		return day;
	});
	const weekEnd = days[6];

	const moveWeek = (weeks: number) => {
		const next = new Date(weekStart);
		next.setDate(next.getDate() + weeks * 7);
		setWeekStart(next);
	};

	const appointmentsOn = (day: Date) =>
		appointments
			.filter(
				(appointment) =>
					appointment.date.toString().slice(0, 10) ===
					toDateString(day),
			)
			.sort((a, b) =>
				a.starttime.toString().localeCompare(b.starttime.toString()),
			);

	const weekCount = days.reduce(
		(total, day) => total + appointmentsOn(day).length,
		0,
	);

	return (
		<div className="flex flex-col gap-4 p-4">
			<div className="flex flex-wrap items-center justify-between gap-2">
				<div>
					<h2 className="font-semibold">Weekoverzicht</h2>
					<p className="text-muted-foreground text-sm">
						{rangeFormat.format(weekStart)} –{' '}
						{rangeFormat.format(weekEnd)} · {weekCount} afspraken
					</p>
				</div>
				<div className="flex gap-1">
					<Button
						variant="outline"
						size="icon"
						aria-label="Vorige week"
						onClick={() => moveWeek(-1)}
					>
						<ChevronLeft />
					</Button>
					<Button
						variant="outline"
						onClick={() => setWeekStart(mondayOf(new Date()))}
					>
						Deze week
					</Button>
					<Button
						variant="outline"
						size="icon"
						aria-label="Volgende week"
						onClick={() => moveWeek(1)}
					>
						<ChevronRight />
					</Button>
				</div>
			</div>

			<div className="grid gap-3 md:grid-cols-7">
				{days.map((day) => {
					const dayAppointments = appointmentsOn(day);
					const isToday = toDateString(day) === today;

					return (
						<div
							key={toDateString(day)}
							className={cn(
								'flex min-h-32 flex-col gap-2 rounded-lg border p-2',
								isToday && 'border-primary',
							)}
						>
							<p
								className={cn(
									'text-xs font-medium capitalize',
									isToday
										? 'text-primary'
										: 'text-muted-foreground',
								)}
							>
								{dayFormat.format(day)}
							</p>
							{dayAppointments.length === 0 && (
								<p className="text-muted-foreground/60 text-xs">
									Geen afspraken
								</p>
							)}
							{dayAppointments.map((appointment) => (
								<Link
									key={appointment.appointment_id}
									href={admin.appointments.show(
										appointment.appointment_id,
									)}
									className={cn(
										'bg-muted/40 hover:bg-muted block rounded-md border-l-4 p-2 text-xs transition-colors',
										statusClass(appointment.status),
									)}
								>
									<p className="flex items-center gap-1 font-medium tabular-nums">
										<Clock className="size-3" />
										{appointment.starttime
											.toString()
											.slice(0, 5)}
										–
										{appointment.endtime
											.toString()
											.slice(0, 5)}
									</p>
									<p className="truncate">
										{appointment.customer?.name ??
											'Onbekende klant'}
									</p>
									<p className="text-muted-foreground truncate">
										{appointment.treatments[0]?.name ??
											'Onbekende behandeling'}
										{appointment.dentist &&
											` · ${appointment.dentist.firstname}`}
									</p>
									{appointment.room && (
										<p className="text-muted-foreground flex items-center gap-1 truncate">
											<DoorOpen className="size-3" />
											{appointment.room.name}
										</p>
									)}
								</Link>
							))}
						</div>
					);
				})}
			</div>
		</div>
	);
}
