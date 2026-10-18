import { Head, Link } from '@inertiajs/react';
import {
	CalendarDays,
	ChevronLeft,
	ChevronRight,
	Clock,
	DoorOpen,
	Search,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import AppointmentEditDialog from '@/components/admin/appointment-edit-dialog';
import {
	statusClass,
	statusLabel,
} from '@/components/admin/appointment-status';
import { roleLabel } from '@/components/admin/role-label';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';
import type {
	CalendarAppointment,
	CalendarEmployee,
	CalendarOptions,
} from '@/types/admin';

type Props = {
	employees: CalendarEmployee[];
	selected: CalendarEmployee | null;
	week: {
		start: string;
		previous: string;
		next: string;
		days: { date: string; appointments: CalendarAppointment[] }[];
	};
	options: CalendarOptions;
};

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

function minutesBetween(start: string, end: string): number {
	const [startHours, startMinutes] = start.split(':').map(Number);
	const [endHours, endMinutes] = end.split(':').map(Number);

	return endHours * 60 + endMinutes - (startHours * 60 + startMinutes);
}

export default function Calendar({
	employees,
	selected,
	week,
	options,
}: Props) {
	const [search, setSearch] = useState('');
	const [role, setRole] = useState('');

	// Roles that actually occur among the employees, for the filter dropdown
	const roles = useMemo(
		() => [
			...new Map(
				employees
					.filter((employee) => employee.role)
					.map((employee) => [
						employee.role!.role_id,
						employee.role!,
					]),
			).values(),
		],
		[employees],
	);

	const filtered = employees.filter((employee) => {
		const matchesSearch = employee.name
			.toLowerCase()
			.includes(search.trim().toLowerCase());
		const matchesRole = role === '' || String(employee.role_id) === role;

		return matchesSearch && matchesRole;
	});

	const appointments = week.days.flatMap((day) => day.appointments);
	const plannedMinutes = appointments
		.filter((appointment) => appointment.status !== 'cancelled')
		.reduce(
			(total, appointment) =>
				total +
				minutesBetween(appointment.starttime, appointment.endtime),
			0,
		);
	const today = new Date().toLocaleDateString('sv-SE'); // YYYY-MM-DD in local time

	const weekStart = new Date(`${week.start}T00:00:00`);
	const weekEnd = new Date(weekStart);
	weekEnd.setDate(weekEnd.getDate() + 6);

	const rosterUrl = (employeeId: number, weekDate: string = week.start) =>
		admin.calendar.index({
			query: { employee: employeeId, week: weekDate },
		});

	return (
		<>
			<Head title="Agenda" />

			<div className="flex flex-col gap-6 p-4">
				<Heading
					title="Agenda"
					description="Bekijk en wijzig het rooster van elke medewerker"
				/>

				<div className="grid gap-4 lg:grid-cols-[18rem_1fr]">
					<Card className="h-fit gap-4">
						<CardHeader>
							<CardTitle>Medewerkers</CardTitle>
						</CardHeader>
						<CardContent className="space-y-3">
							<div className="relative">
								<Search className="text-muted-foreground absolute top-2.5 left-2.5 size-4" />
								<Input
									value={search}
									onChange={(event) =>
										setSearch(event.target.value)
									}
									placeholder="Zoek medewerker"
									className="pl-8"
									aria-label="Zoek medewerker"
								/>
							</div>
							<select
								value={role}
								onChange={(event) =>
									setRole(event.target.value)
								}
								aria-label="Filter op rol"
								className="border-input dark:bg-input/30 h-9 w-full rounded-md border bg-transparent px-3 text-sm shadow-xs"
							>
								<option value="">Alle rollen</option>
								{roles.map((option) => (
									<option
										key={option.role_id}
										value={option.role_id}
									>
										{roleLabel(option.name)}
									</option>
								))}
							</select>

							<ul className="-mx-2 max-h-[60vh] space-y-1 overflow-y-auto">
								{filtered.length === 0 && (
									<li className="text-muted-foreground px-2 py-4 text-center text-sm">
										Geen medewerkers gevonden.
									</li>
								)}
								{filtered.map((employee) => {
									const isSelected =
										selected?.id === employee.id;

									return (
										<li
											key={employee.id}
											className={cn(
												'flex items-center justify-between gap-2 rounded-md px-2 py-1.5',
												isSelected && 'bg-accent',
											)}
										>
											<div className="min-w-0">
												<p className="truncate text-sm font-medium">
													{employee.name}
												</p>
												<p className="text-muted-foreground text-xs">
													{roleLabel(
														employee.role?.name,
													)}
												</p>
											</div>
											<Button
												asChild
												size="sm"
												variant={
													isSelected
														? 'default'
														: 'outline'
												}
											>
												<Link
													href={rosterUrl(
														employee.id,
													)}
													preserveScroll
												>
													{isSelected
														? 'Geselecteerd'
														: 'Bekijk rooster'}
												</Link>
											</Button>
										</li>
									);
								})}
							</ul>
						</CardContent>
					</Card>

					{selected === null ? (
						<Card className="flex min-h-64 items-center justify-center">
							<div className="text-muted-foreground flex flex-col items-center gap-2 text-center text-sm">
								<CalendarDays className="size-8" />
								<p>
									Kies een medewerker om het rooster te
									bekijken.
								</p>
							</div>
						</Card>
					) : (
						<Card className="gap-4">
							<CardHeader className="flex flex-row flex-wrap items-start justify-between gap-4">
								<div>
									<CardTitle>
										Rooster van {selected.name}
									</CardTitle>
									<p className="text-muted-foreground mt-1 text-sm">
										{rangeFormat.format(weekStart)} –{' '}
										{rangeFormat.format(weekEnd)} ·{' '}
										{appointments.length} afspraken ·{' '}
										{Math.floor(plannedMinutes / 60)}u{' '}
										{plannedMinutes % 60}m gepland
									</p>
								</div>
								<div className="flex gap-1">
									<Button
										asChild
										variant="outline"
										size="icon"
										aria-label="Vorige week"
									>
										<Link
											href={rosterUrl(
												selected.id,
												week.previous,
											)}
											preserveScroll
										>
											<ChevronLeft />
										</Link>
									</Button>
									<Button asChild variant="outline">
										<Link
											href={rosterUrl(selected.id, today)}
											preserveScroll
										>
											Deze week
										</Link>
									</Button>
									<Button
										asChild
										variant="outline"
										size="icon"
										aria-label="Volgende week"
									>
										<Link
											href={rosterUrl(
												selected.id,
												week.next,
											)}
											preserveScroll
										>
											<ChevronRight />
										</Link>
									</Button>
								</div>
							</CardHeader>
							<CardContent>
								<div className="grid gap-3 md:grid-cols-7">
									{week.days.map((day) => (
										<div
											key={day.date}
											className={cn(
												'flex min-h-32 flex-col gap-2 rounded-lg border p-2',
												day.date === today &&
													'border-primary',
											)}
										>
											<p
												className={cn(
													'text-xs font-medium capitalize',
													day.date === today
														? 'text-primary'
														: 'text-muted-foreground',
												)}
											>
												{dayFormat.format(
													new Date(
														`${day.date}T00:00:00`,
													),
												)}
											</p>
											{day.appointments.length === 0 && (
												<p className="text-muted-foreground/60 text-xs">
													Vrij
												</p>
											)}
											{day.appointments.map(
												(appointment) => (
													<AppointmentEditDialog
														key={
															appointment.appointment_id
														}
														appointment={
															appointment
														}
														options={options}
													>
														<button
															type="button"
															className={cn(
																'bg-muted/40 hover:bg-muted w-full rounded-md border-l-4 p-2 text-left text-xs transition-colors',
																statusClass(
																	appointment.status,
																),
															)}
														>
															<p className="flex items-center gap-1 font-medium tabular-nums">
																<Clock className="size-3" />
																{appointment.starttime.slice(
																	0,
																	5,
																)}
																–
																{appointment.endtime.slice(
																	0,
																	5,
																)}
															</p>
															<p className="truncate">
																{appointment
																	.customer
																	?.name ??
																	'Onbekende klant'}
															</p>
															{appointment
																.treatments
																.length > 0 && (
																<p className="text-muted-foreground truncate">
																	{appointment.treatments
																		.map(
																			(
																				treatment,
																			) =>
																				treatment.name,
																		)
																		.join(
																			', ',
																		)}
																</p>
															)}
															{appointment.room && (
																<p className="text-muted-foreground flex items-center gap-1 truncate">
																	<DoorOpen className="size-3" />
																	{
																		appointment
																			.room
																			.name
																	}
																</p>
															)}
															{appointment.assistant_id ===
																selected.id && (
																<Badge
																	variant="secondary"
																	className="mt-1"
																>
																	Als
																	assistent
																</Badge>
															)}
															{appointment.status !==
																'scheduled' && (
																<Badge
																	variant="outline"
																	className="mt-1"
																>
																	{statusLabel(
																		appointment.status,
																	)}
																</Badge>
															)}
														</button>
													</AppointmentEditDialog>
												),
											)}
										</div>
									))}
								</div>
							</CardContent>
						</Card>
					)}
				</div>
			</div>
		</>
	);
}

Calendar.layout = {
	breadcrumbs: [
		{ title: 'Gebruikersbeheer', href: admin.users() },
		{ title: 'Agenda', href: admin.calendar.index() },
	],
};
