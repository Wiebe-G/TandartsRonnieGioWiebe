import {Head, Link, usePage} from '@inertiajs/react';
import {dashboard} from '@/routes';
import {PageProps} from "@inertiajs/core";
import {Appointment, User} from "@/types/Interfaces";
import {EmployeePlanning} from "@/components/EmployeePlanning";
import PracticeStatistics from "@/components/admin/practice-statistics";
import type {PracticeStatistics as Statistics} from "@/types/admin";

interface Props extends PageProps {
	auth: User;
	Appointments: Appointment[];
	Statistics: Statistics | null;
}

export default function Dashboard() {
	const {auth, Appointments, Statistics} = usePage<Props>().props;

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
				{Statistics && <PracticeStatistics statistics={Statistics}/>}
				<div className="grid auto-rows-min gap-4 md:grid-cols-3">
					<div className="LayoutGrid">
						{auth.user.role_id == 1
							? (Appointments[0]
								// Klanten zien alleen hun eigen afspraken (zonder link, de afsprakenpagina is voor medewerkers)
								? <div className="border-2 border-blue-700">
									<span>Volgende afspraak:</span>
									<br/>
									{Appointments[0].treatments[0]?.name}
									<br/>
									op {Appointments[0].date.toString()} van {Appointments[0].starttime.toString()} tot {Appointments[0].endtime.toString()}
								</div>
								: <div className="border-2 border-blue-700">Geen komende afspraken</div>)
							: !NextEmployeeAppointment
							? <div className="border-2 border-blue-700">Geen komende afspraken</div>
							:
							<div className="border-2 border-blue-700">
								<span>Volgende afspraak:</span>
								<br/>
								<Link href={`/appointments/view/${NextEmployeeAppointment.appointment_id}`}>
									{NextEmployeeAppointment.treatments[0]?.name} voor {NextEmployeeAppointment.customer.firstname} {NextEmployeeAppointment.customer.lastname}
									<br/>
									op {NextEmployeeAppointment.date.toString()} van {NextEmployeeAppointment.starttime.toString()} tot {NextEmployeeAppointment.endtime.toString()}
								</Link>
							</div>
						}
					</div>
				</div>
				{auth.user.role_id != 1 &&
					<div
						className="border-sidebar-border/70 dark:border-sidebar-border relative min-h-screen flex-1 overflow-hidden rounded-xl border md:min-h-min">
						{EmployeePlanning(EmployeeAppointments, Appointments)}
					</div>
				}
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
