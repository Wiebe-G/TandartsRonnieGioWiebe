import { Head, Link } from '@inertiajs/react';
import { CalendarClock, Plus } from 'lucide-react';
import {
	statusClass,
	statusLabel,
} from '@/components/admin/appointment-status';
import PracticeStatistics from '@/components/admin/practice-statistics';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';
import type {
	CalendarAppointment,
	PracticeStatistics as Statistics,
} from '@/types/admin';

type Props = {
	statistics: Statistics;
	today: CalendarAppointment[];
};

export default function AdminDashboard({ statistics, today }: Props) {
	return (
		<>
			<Head title="Dashboard" />

			<div className="flex flex-col gap-6 p-4">
				<div className="flex flex-wrap items-start justify-between gap-4">
					<Heading
						title="Dashboard"
						description="Hoe staat de praktijk er nu voor"
					/>
					<Button asChild>
						<Link href={admin.appointments.create()}>
							<Plus />
							Nieuwe afspraak
						</Link>
					</Button>
				</div>

				<PracticeStatistics statistics={statistics} />

				<Card>
					<CardHeader className="flex flex-row items-center justify-between">
						<CardTitle>
							Afspraken vandaag ({today.length})
						</CardTitle>
						<CalendarClock className="text-muted-foreground size-4" />
					</CardHeader>
					<CardContent>
						{today.length === 0 ? (
							<p className="text-muted-foreground text-sm">
								Geen afspraken vandaag.
							</p>
						) : (
							<ul className="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
								{today.map((appointment) => (
									<li key={appointment.appointment_id}>
										<Link
											href={admin.appointments.show(
												appointment.appointment_id,
											)}
											className={cn(
												'bg-muted/40 hover:bg-muted flex items-center justify-between gap-3 rounded-md border-l-4 px-3 py-2 text-sm transition-colors',
												statusClass(appointment.status),
											)}
										>
											<span className="min-w-0">
												<span className="block truncate font-medium">
													{appointment.customer
														?.name ??
														'Onbekende klant'}
												</span>
												<span className="text-muted-foreground block truncate">
													{appointment.treatments[0]
														?.name ?? 'Afspraak'}
													{appointment.dentist &&
														` · ${appointment.dentist.firstname}`}
													{appointment.room &&
														` · ${appointment.room.name}`}
												</span>
											</span>
											<span className="text-muted-foreground shrink-0 text-right tabular-nums">
												{appointment.starttime.slice(
													0,
													5,
												)}
												{appointment.status !==
													'scheduled' && (
													<span className="block text-xs">
														{statusLabel(
															appointment.status,
														)}
													</span>
												)}
											</span>
										</Link>
									</li>
								))}
							</ul>
						)}
					</CardContent>
				</Card>
			</div>
		</>
	);
}

AdminDashboard.layout = {
	breadcrumbs: [{ title: 'Dashboard', href: admin.dashboard() }],
};
