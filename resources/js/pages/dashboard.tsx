import { Head, Link, usePage } from '@inertiajs/react';
import type { PageProps } from '@inertiajs/core';
import { CalendarClock, CalendarDays, Clock, Plus } from 'lucide-react';
import PracticeStatistics from '@/components/admin/practice-statistics';
import { EmployeePlanning } from '@/components/EmployeePlanning';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';
import type { PracticeStatistics as Statistics } from '@/types/admin';
import type { Appointment, User } from '@/types/Interfaces';

interface Props extends PageProps {
	auth: User;
	Appointments: Appointment[];
	Statistics: Statistics | null;
}

const longDate = new Intl.DateTimeFormat('nl-NL', {
	weekday: 'long',
	day: 'numeric',
	month: 'long',
});

export default function Dashboard() {
	const { auth, Appointments, Statistics } = usePage<Props>().props;

	const EmployeeAppointments: Appointment[] = [];

	Appointments.map((app) => {
		if (
			app.dentist_id === auth.user.id ||
			app.assistant_id === auth.user.id
		) {
			EmployeeAppointments.push(app);
		}
	});

	const isCustomer = auth.user.role_id == 1;
	// Klanten zien hun eigen afspraken, medewerkers die waar ze tandarts of assistent zijn
	const NextAppointment = isCustomer
		? Appointments[0]
		: EmployeeAppointments[0];

	return (
		<>
			<Head title="Dashboard" />

			<div className="flex flex-col gap-6 p-4">
				<div className="flex flex-wrap items-start justify-between gap-4">
					<Heading
						title="Dashboard"
						description={
							isCustomer
								? 'Je komende afspraak'
								: 'Je volgende afspraak en je planning'
						}
					/>
					{!isCustomer && (
						<Button asChild>
							<Link href={admin.appointments.create()}>
								<Plus />
								Nieuwe afspraak
							</Link>
						</Button>
					)}
				</div>

				{Statistics && <PracticeStatistics statistics={Statistics} />}

				<Card>
					<CardHeader className="flex flex-row items-center justify-between">
						<CardTitle>Volgende afspraak</CardTitle>
						<CalendarClock className="text-muted-foreground size-4" />
					</CardHeader>
					<CardContent>
						{!NextAppointment ? (
							<p className="text-muted-foreground text-sm">
								Geen komende afspraken.
							</p>
						) : (
							<div className="flex flex-wrap items-end justify-between gap-4">
								<div className="space-y-1">
									<p className="text-lg font-semibold">
										{NextAppointment.treatments[0]?.name ??
											'Afspraak'}
										{!isCustomer && (
											<span className="text-muted-foreground font-normal">
												{' '}
												voor{' '}
												{
													NextAppointment.customer
														.firstname
												}{' '}
												{
													NextAppointment.customer
														.lastname
												}
											</span>
										)}
									</p>
									<p className="text-muted-foreground flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
										<span className="flex items-center gap-1 capitalize">
											<CalendarDays className="size-4" />
											{longDate.format(
												new Date(
													`${NextAppointment.date.toString().slice(0, 10)}T00:00:00`,
												),
											)}
										</span>
										<span className="flex items-center gap-1 tabular-nums">
											<Clock className="size-4" />
											{NextAppointment.starttime
												.toString()
												.slice(0, 5)}{' '}
											–{' '}
											{NextAppointment.endtime
												.toString()
												.slice(0, 5)}
										</span>
									</p>
								</div>
								{!isCustomer && (
									<Button asChild variant="outline">
										<Link
											href={admin.appointments.show(
												NextAppointment.appointment_id,
											)}
										>
											Bekijk afspraak
										</Link>
									</Button>
								)}
							</div>
						)}
					</CardContent>
				</Card>

				{!isCustomer && (
					<Card>
						<CardHeader className="flex flex-row items-center justify-between">
							<CardTitle>
								Mijn planning ({EmployeeAppointments.length})
							</CardTitle>
							<CalendarDays className="text-muted-foreground size-4" />
						</CardHeader>
						<CardContent>
							{EmployeePlanning(
								EmployeeAppointments,
								Appointments,
							)}
						</CardContent>
					</Card>
				)}
			</div>
		</>
	);
}

Dashboard.layout = {
	breadcrumbs: [
		{
			title: 'Dashboard',
			href: dashboard(),
		},
	],
};
