import { Link } from '@inertiajs/react';
import {
	statusClass,
	statusLabel,
} from '@/components/admin/appointment-status';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';
import type { Appointment } from '@/types/Interfaces';

const dayFormat = new Intl.DateTimeFormat('nl-NL', {
	weekday: 'long',
	day: 'numeric',
	month: 'long',
});

/** The employee's upcoming appointments, grouped per day, in the same style as the admin dashboard. */
export function EmployeePlanning(
	EmployeeAppointments: Appointment[],
	_Appointments?: unknown,
) {
	if (EmployeeAppointments.length === 0) {
		return (
			<p className="text-muted-foreground text-sm">
				Geen komende afspraken.
			</p>
		);
	}

	// Groepeer per datum; de afspraken komen al op datum en tijd gesorteerd binnen
	const days = EmployeeAppointments.reduce<Record<string, Appointment[]>>(
		(grouped, app) => {
			const date = app.date.toString().slice(0, 10);
			(grouped[date] ??= []).push(app);

			return grouped;
		},
		{},
	);

	return (
		<div className="space-y-4">
			{Object.entries(days).map(([date, appointments]) => (
				<section key={date} className="space-y-2">
					<h3 className="text-muted-foreground text-sm font-medium capitalize">
						{dayFormat.format(new Date(`${date}T00:00:00`))} (
						{appointments.length})
					</h3>
					<ul className="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
						{appointments.map((app) => (
							<li key={app.appointment_id}>
								<Link
									href={admin.appointments.show(
										app.appointment_id,
									)}
									className={cn(
										'bg-muted/40 hover:bg-muted flex items-center justify-between gap-3 rounded-md border-l-4 px-3 py-2 text-sm transition-colors',
										statusClass(app.status),
									)}
								>
									<span className="min-w-0">
										<span className="block truncate font-medium">
											{app.customer.firstname}{' '}
											{app.customer.lastname}
										</span>
										<span className="text-muted-foreground block truncate">
											{app.treatments[0]?.name ??
												'Onbekende behandeling'}
										</span>
									</span>
									<span className="text-muted-foreground shrink-0 text-right tabular-nums">
										{app.starttime.toString().slice(0, 5)}
										{app.status !== 'scheduled' && (
											<span className="block text-xs">
												{statusLabel(app.status)}
											</span>
										)}
									</span>
								</Link>
							</li>
						))}
					</ul>
				</section>
			))}
		</div>
	);
}
