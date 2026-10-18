import {Head, Link, usePage} from '@inertiajs/react';
import {PlaceholderPattern} from '@/components/ui/placeholder-pattern';
import {dashboard} from '@/routes';
import {PageProps} from "@inertiajs/core";
import {Appointment, User} from "@/types/Interfaces";
import {EmployeePlanning} from "@/components/EmployeePlanning";

interface Props extends PageProps {
	auth: User;
	Appointments: Appointment[];
}

export default function Dashboard() {
	const {auth, Appointments} = usePage<Props>().props;

	const EmployeeAppointments: Appointment[] = [];

	Appointments.map((app) => {
		if (app.dentist_id === auth.user.id || app.assistant_id === auth.user.id) {
			EmployeeAppointments.push(app)
		}
	})

	const NextEmployeeAppointment = EmployeeAppointments[0];

	return (
		<>
			<Head title="Dashboard"/>
			<div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
				<div className="grid auto-rows-min gap-4 md:grid-cols-3">
					<div className="LayoutGrid">
						{auth.user.role_id == 1
							? "Volgende afspraak voor klant"
							:
							<div className="border-2 border-blue-700">
								<span>Volgende afspraak:</span>
								<br/>
								<Link href={`/appointments/view/${EmployeeAppointments[0].appointment_id}`}>
									{NextEmployeeAppointment.treatments[0].name} voor {NextEmployeeAppointment.customer.firstname} {NextEmployeeAppointment.customer.lastname}
									<br/>
									op {NextEmployeeAppointment.date.toString()} van {NextEmployeeAppointment.starttime.toString()} tot {NextEmployeeAppointment.endtime.toString()}
								</Link>
							</div>
						}
					</div>
					<div className="LayoutGrid">
						<PlaceholderPattern
							className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20"/>
					</div>
					<div className="LayoutGrid">
						<PlaceholderPattern
							className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20"/>
					</div>
				</div>
				<div
					className="border-sidebar-border/70 dark:border-sidebar-border relative min-h-screen flex-1 overflow-hidden rounded-xl border md:min-h-min">
					{
						auth.user.role_id == 1
							? <PlaceholderPattern
								className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20"/>
							:
							EmployeePlanning(EmployeeAppointments, Appointments)
					}
				</div>
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
