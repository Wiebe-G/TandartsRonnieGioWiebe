// import Layout from '@/layouts/app-layout'
import { Head, Link, usePage } from '@inertiajs/react';
import { PageProps } from '@inertiajs/core';
import { Appointment } from '@/types/Interfaces';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { PlusSquareIcon } from 'lucide-react';

interface Props extends PageProps {
	Appointments: Appointment[];
}

export default function Appointments() {
	const { Appointments } = usePage<Props>().props;

	// filter naar appointments waar date == new Date() (lokale datum, niet UTC)
	let AppointmentsToday: Appointment[] = [];
	Appointments.forEach((app) => {
		if (app.date.toString() == new Date().toLocaleDateString('sv-SE')) {
			AppointmentsToday = [...AppointmentsToday, app];
		}
	});

	// De laatste afgeronde afspraken, nieuwste eerst
	const CompletedAppointments = Appointments.filter(
		(app) => app.status == 'completed',
	)
		.sort((a, b) =>
			(b.date.toString() + b.starttime.toString()).localeCompare(
				a.date.toString() + a.starttime.toString(),
			),
		)
		.slice(0, 5);

	return (
		<>
			<Head title="Afspraken" />
			<div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
				<div className="grid auto-rows-min items-center gap-4 md:grid-cols-3">
					<Link
						className="LayoutGrid h-full place-items-center"
						href="/appointments/create"
					>
						<h1>Nieuwe afspraak</h1>
						<PlusSquareIcon />
					</Link>
					<div className="LayoutGrid text-center">
						<h1>Afspraken vandaag:</h1>
						{AppointmentsToday.map((app) => (
							<Link
								href={`/appointments/view/${app.appointment_id}`}
								key={app.appointment_id}
								className="grid auto-cols-min md:grid-cols-2"
							>
								<span>
									{app.treatments[0]?.name ??
										'Onbekende behandeling'}
								</span>
								<span>
									{app.customer.firstname}{' '}
									{app.customer.lastname}
								</span>
							</Link>
						))}
					</div>
					<div className="LayoutGrid">
						<h1>Afgeronde afspraken</h1>
						{CompletedAppointments.map((app) => (
							<Link
								href={`/appointments/view/${app.appointment_id}`}
								key={app.appointment_id}
								className="grid auto-cols-min md:grid-cols-2"
							>
								<span>
									{app.treatments[0]?.name ??
										'Onbekende behandeling'}
								</span>
								<span>
									{app.customer.firstname}{' '}
									{app.customer.lastname}
								</span>
							</Link>
						))}
					</div>
				</div>
				<div className="border-sidebar-border/70 dark:border-sidebar-border relative min-h-screen flex-1 overflow-hidden rounded-xl border md:min-h-min">
					<PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
				</div>
			</div>
		</>
	);
}

Appointments.layout = {
	breadcrumbs: [
		{
			title: 'Afspraken',
			href: '/appointments',
		},
	],
};
