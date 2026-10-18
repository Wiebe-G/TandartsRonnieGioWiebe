import { Head, Link, usePage } from '@inertiajs/react';
import { CalendarCheck, CalendarClock, Plus } from 'lucide-react';
import {
	statusClass,
	statusLabel,
} from '@/components/admin/appointment-status';
import { WeekAppointments } from '@/components/admin/week-appointments';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { localDate } from '@/lib/appointment-slots';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';
import type { CalendarAppointment } from '@/types/admin';

const shortDate = new Intl.DateTimeFormat('nl-NL', {
	weekday: 'short',
	day: 'numeric',
	month: 'short',
});

function AppointmentRow({
	appointment,
	showDate = false,
}: {
	appointment: CalendarAppointment;
	showDate?: boolean;
}) {
	const date = appointment.date.slice(0, 10);

	return (
		<li>
			<Link
				href={admin.appointments.show(appointment.appointment_id)}
				className={cn(
					'bg-muted/40 hover:bg-muted flex items-center justify-between gap-3 rounded-md border-l-4 px-3 py-2 text-sm transition-colors',
					statusClass(appointment.status),
				)}
			>
				<span className="min-w-0">
					<span className="block truncate font-medium">
						{appointment.customer?.name ?? 'Onbekende klant'}
					</span>
					<span className="text-muted-foreground block truncate">
						{appointment.treatments[0]?.name ??
							'Onbekende behandeling'}
						{appointment.dentist &&
							` · ${appointment.dentist.firstname}`}
					</span>
				</span>
				<span className="text-muted-foreground shrink-0 text-right tabular-nums">
					{showDate && (
						<span className="block capitalize">
							{shortDate.format(new Date(`${date}T00:00:00`))}
						</span>
					)}
					{appointment.starttime.slice(0, 5)}
					{appointment.status !== 'scheduled' && (
						<span className="block text-xs">
							{statusLabel(appointment.status)}
						</span>
					)}
				</span>
			</Link>
		</li>
	);
}

export default function AppointmentsIndex({
	appointments,
}: {
	appointments: CalendarAppointment[];
}) {
	const { auth } = usePage().props;
	const today = localDate();
	const appointmentsToday = appointments.filter(
		(appointment) => appointment.date.slice(0, 10) === today,
	);
	const recentlyCompleted = appointments
		.filter((appointment) => appointment.status === 'completed')
		.sort((a, b) =>
			(b.date + b.starttime).localeCompare(a.date + a.starttime),
		)
		.slice(0, 5);
	const upcoming = appointments.filter(
		(appointment) =>
			appointment.date.slice(0, 10) > today &&
			appointment.status === 'scheduled',
	).length;

	return (
		<>
			<Head title="Afspraken" />

			<div className="flex flex-col gap-6 p-4">
				<div className="flex flex-wrap items-start justify-between gap-4">
					<Heading
						title="Afspraken"
						description={`${appointmentsToday.length} vandaag · ${upcoming} gepland na vandaag`}
					/>
					{/* Making appointments: assistants and admins only */}
					{auth.canPlanAndInvoice && (
						<Button asChild>
							<Link href={admin.appointments.create()}>
								<Plus />
								Nieuwe afspraak
							</Link>
						</Button>
					)}
				</div>

				<div className="grid gap-4 md:grid-cols-2">
					<Card>
						<CardHeader className="flex flex-row items-center justify-between">
							<CardTitle>Vandaag</CardTitle>
							<CalendarClock className="text-muted-foreground size-4" />
						</CardHeader>
						<CardContent>
							{appointmentsToday.length === 0 ? (
								<p className="text-muted-foreground text-sm">
									Geen afspraken vandaag.
								</p>
							) : (
								<ul className="space-y-2">
									{appointmentsToday.map((appointment) => (
										<AppointmentRow
											key={appointment.appointment_id}
											appointment={appointment}
										/>
									))}
								</ul>
							)}
						</CardContent>
					</Card>

					<Card>
						<CardHeader className="flex flex-row items-center justify-between">
							<CardTitle>Recent afgerond</CardTitle>
							<CalendarCheck className="text-muted-foreground size-4" />
						</CardHeader>
						<CardContent>
							{recentlyCompleted.length === 0 ? (
								<p className="text-muted-foreground text-sm">
									Nog geen afgeronde afspraken.
								</p>
							) : (
								<ul className="space-y-2">
									{recentlyCompleted.map((appointment) => (
										<AppointmentRow
											key={appointment.appointment_id}
											appointment={appointment}
											showDate
										/>
									))}
								</ul>
							)}
						</CardContent>
					</Card>
				</div>

				<Card className="py-0">
					<WeekAppointments appointments={appointments} />
				</Card>
			</div>
		</>
	);
}

AppointmentsIndex.layout = {
	breadcrumbs: [{ title: 'Afspraken', href: admin.appointments.index() }],
};
