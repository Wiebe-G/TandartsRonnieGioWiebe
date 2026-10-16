import {Appointment} from "@/types/Interfaces";
import {Link} from "@inertiajs/react";

export function EmployeePlanning(EmployeeAppointments: Appointment[], Appointments: any) {
	return <div>
		{EmployeeAppointments.map((app: Appointment, index: number) => (
			<Link key={app.appointment_id} href={`/appointments/view/${app.appointment_id}`}>
										<span className="font-bold">
											{/* Zorg ervoor dat boven aan sowieso datum staat */}
											{
												index == 0
													? `${app.date}`
													: null
											}
											{/* Als de datum groter is (dus nieuwe datum) */}
											{app.date > Appointments[index - 1]?.date
												? `${app.date}`
												: null}
										</span>
				<br/>
				<span>Klant: {app.customer.firstname} {app.customer.lastname}</span>
				<br/>
			</Link>
		))}
	</div>;
}
