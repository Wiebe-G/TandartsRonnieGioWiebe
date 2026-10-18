import { Head, Link, usePage } from '@inertiajs/react';
import {
	CalendarClock,
	CalendarDays,
	CalendarPlus,
	History,
	Mail,
	Phone,
	Stethoscope,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
	Card,
	CardContent,
	CardDescription,
	CardHeader,
	CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { create, index } from '@/routes/appointments';
import { history } from '@/routes/treatments';

type Appointment = {
	id: number;
	date: string;
	starttime: string;
	endtime: string;
	status: string;
	note: string | null;
	dentist: string;
	treatments: string[];
};

type Dentist = {
	name: string;
	email: string;
	phonenumber: string | null;
};

type Props = {
	dentist: Dentist | null;
	upcoming: Appointment[];
	recent: Appointment[];
};

const statusLabels: Record<
	string,
	{
		label: string;
		variant: 'default' | 'secondary' | 'destructive' | 'outline';
	}
> = {
	scheduled: { label: 'Gepland', variant: 'default' },
	completed: { label: 'Afgerond', variant: 'secondary' },
	cancelled: { label: 'Geannuleerd', variant: 'destructive' },
};

function formatDate(
	date: string,
	options: Intl.DateTimeFormatOptions = {
		weekday: 'long',
		day: 'numeric',
		month: 'long',
	},
) {
	return new Date(`${date}T00:00:00`).toLocaleDateString('nl-NL', options);
}

function StatusBadge({ status }: { status: string }) {
	const { label, variant } = statusLabels[status] ?? {
		label: status,
		variant: 'outline',
	};

	return <Badge variant={variant}>{label}</Badge>;
}

function AppointmentRow({ appointment }: { appointment: Appointment }) {
	return (
		<li className="flex items-start justify-between gap-4 py-3">
			<div className="flex gap-3">
				<div className="flex w-12 shrink-0 flex-col items-center rounded-md border py-1 text-center">
					<span className="text-muted-foreground text-xs uppercase">
						{formatDate(appointment.date, { month: 'short' })}
					</span>
					<span className="text-lg leading-none font-semibold">
						{formatDate(appointment.date, { day: 'numeric' })}
					</span>
				</div>
				<div>
					<p className="font-medium">
						{appointment.treatments.length > 0
							? appointment.treatments.join(', ')
							: 'Afspraak'}
					</p>
					<p className="text-muted-foreground text-sm">
						{appointment.starttime} – {appointment.endtime} ·{' '}
						{appointment.dentist}
					</p>
				</div>
			</div>
			<StatusBadge status={appointment.status} />
		</li>
	);
}

export default function CustomerDashboard({
	dentist,
	upcoming,
	recent,
}: Props) {
	const { auth } = usePage().props;
	const [next, ...later] = upcoming;

	return (
		<>
			<Head title="Mijn overzicht" />
			<div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
				<div className="flex flex-wrap items-center justify-between gap-4">
					<div>
						<h1 className="text-2xl font-semibold">
							Welkom terug, {auth.user.firstname}
						</h1>
						<p className="text-muted-foreground">
							Hier vindt u uw afspraken en de gegevens van uw
							tandarts.
						</p>
					</div>
					<div className="flex gap-2">
						<Button variant="outline" asChild>
							<Link href={index()}>
								<CalendarDays />
								Agenda
							</Link>
						</Button>
						<Button asChild>
							<Link href={create()}>
								<CalendarPlus />
								Afspraak maken
							</Link>
						</Button>
					</div>
				</div>

				<Card className="border-primary/30 bg-primary/5">
					<CardHeader>
						<CardDescription className="flex items-center gap-2">
							<CalendarClock className="size-4" />
							Uw volgende afspraak
						</CardDescription>
						{next ? (
							<CardTitle className="text-xl capitalize">
								{formatDate(next.date)} om {next.starttime}
							</CardTitle>
						) : (
							<CardTitle className="text-xl">
								U heeft geen geplande afspraken
							</CardTitle>
						)}
					</CardHeader>
					<CardContent className="text-muted-foreground text-sm">
						{next ? (
							<div className="space-y-1">
								<p>
									{next.treatments.length > 0
										? next.treatments.join(', ')
										: 'Afspraak'}{' '}
									bij {next.dentist}, tot {next.endtime}
								</p>
								{next.note && (
									<p className="italic">{next.note}</p>
								)}
							</div>
						) : (
							<p>
								Wilt u een afspraak maken?{' '}
								<Link
									href={create()}
									className="text-foreground underline underline-offset-4"
								>
									Plan direct een afspraak in.
								</Link>
							</p>
						)}
					</CardContent>
				</Card>

				<div className="grid gap-6 lg:grid-cols-3">
					<Card className="lg:col-span-2">
						<CardHeader>
							<CardTitle className="flex items-center gap-2 text-base">
								<CalendarDays className="size-4" />
								Geplande afspraken
							</CardTitle>
						</CardHeader>
						<CardContent>
							{later.length > 0 ? (
								<ul className="divide-y">
									{later.map((appointment) => (
										<AppointmentRow
											key={appointment.id}
											appointment={appointment}
										/>
									))}
								</ul>
							) : (
								<p className="text-muted-foreground text-sm">
									Geen andere afspraken gepland.
								</p>
							)}
						</CardContent>
					</Card>

					<Card>
						<CardHeader>
							<CardTitle className="flex items-center gap-2 text-base">
								<Stethoscope className="size-4" />
								Uw tandarts
							</CardTitle>
						</CardHeader>
						<CardContent className="space-y-2 text-sm">
							{dentist ? (
								<>
									<p className="font-medium">
										{dentist.name}
									</p>
									<a
										href={`mailto:${dentist.email}`}
										className="text-muted-foreground hover:text-foreground flex items-center gap-2"
									>
										<Mail className="size-4" />
										{dentist.email}
									</a>
									{dentist.phonenumber && (
										<a
											href={`tel:${dentist.phonenumber}`}
											className="text-muted-foreground hover:text-foreground flex items-center gap-2"
										>
											<Phone className="size-4" />
											{dentist.phonenumber}
										</a>
									)}
								</>
							) : (
								<p className="text-muted-foreground">
									Er is nog geen tandarts aan u gekoppeld.
								</p>
							)}
						</CardContent>
					</Card>
				</div>

				<Card>
					<CardHeader className="flex flex-row items-center justify-between">
						<CardTitle className="flex items-center gap-2 text-base">
							<History className="size-4" />
							Eerdere afspraken
						</CardTitle>
						<Button variant="ghost" size="sm" asChild>
							<Link href={history()}>
								Geschiedenis en feedback
							</Link>
						</Button>
					</CardHeader>
					<CardContent>
						{recent.length > 0 ? (
							<ul className="divide-y">
								{recent.map((appointment) => (
									<AppointmentRow
										key={appointment.id}
										appointment={appointment}
									/>
								))}
							</ul>
						) : (
							<p className="text-muted-foreground text-sm">
								U heeft nog geen eerdere afspraken.
							</p>
						)}
					</CardContent>
				</Card>
			</div>
		</>
	);
}

CustomerDashboard.layout = {
	breadcrumbs: [
		{
			title: 'Mijn overzicht',
			href: dashboard(),
		},
	],
};
