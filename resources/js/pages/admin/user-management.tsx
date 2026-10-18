import { Head, Link } from '@inertiajs/react';
import {
	CalendarDays,
	Stethoscope,
	UserCheck,
	UserPlus,
	Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { formatDate, roleLabel } from '@/components/admin/role-label';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
	Card,
	CardContent,
	CardDescription,
	CardHeader,
	CardTitle,
} from '@/components/ui/card';
import admin from '@/routes/admin';
import type { ManagedUser, Role } from '@/types/admin';

type Props = {
	stats: {
		customers: number;
		activeCustomers: number;
		employees: number;
		appointmentsToday: number;
	};
	roles: Role[];
	latestCustomers: ManagedUser[];
	latestEmployees: ManagedUser[];
};

function StatCard({
	title,
	value,
	icon: Icon,
}: {
	title: string;
	value: number;
	icon: LucideIcon;
}) {
	return (
		<Card className="gap-2">
			<CardHeader className="flex flex-row items-center justify-between">
				<CardDescription>{title}</CardDescription>
				<Icon className="text-muted-foreground size-4" />
			</CardHeader>
			<CardContent>
				<p className="text-3xl font-semibold tabular-nums">{value}</p>
			</CardContent>
		</Card>
	);
}

export default function UserManagement({
	stats,
	roles,
	latestCustomers,
	latestEmployees,
}: Props) {
	return (
		<>
			<Head title="Gebruikersbeheer" />

			<div className="flex flex-col gap-6 p-4">
				<div className="flex flex-wrap items-start justify-between gap-4">
					<Heading
						title="Gebruikersbeheer"
						description="Overzicht van klanten en medewerkers van de praktijk"
					/>
					<div className="flex flex-wrap gap-2">
						<Button asChild variant="outline">
							<Link href={admin.customers.create()}>
								<UserPlus />
								Nieuwe klant
							</Link>
						</Button>
						<Button asChild>
							<Link href={admin.employees.create()}>
								<UserPlus />
								Nieuwe medewerker
							</Link>
						</Button>
					</div>
				</div>

				<div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
					<StatCard
						title="Klanten"
						value={stats.customers}
						icon={Users}
					/>
					<StatCard
						title="Actieve klanten"
						value={stats.activeCustomers}
						icon={UserCheck}
					/>
					<StatCard
						title="Medewerkers"
						value={stats.employees}
						icon={Stethoscope}
					/>
					<StatCard
						title="Afspraken vandaag"
						value={stats.appointmentsToday}
						icon={CalendarDays}
					/>
				</div>

				<div className="grid gap-4 lg:grid-cols-3">
					<Card>
						<CardHeader>
							<CardTitle>Medewerkers per rol</CardTitle>
						</CardHeader>
						<CardContent>
							<ul className="divide-y">
								{roles.map((role) => (
									<li key={role.role_id}>
										<Link
											href={admin.employees.index({
												query: { role: role.role_id },
											})}
											className="hover:bg-accent -mx-2 flex items-center justify-between rounded-md px-2 py-2 text-sm"
										>
											<span>{roleLabel(role.name)}</span>
											<Badge variant="secondary">
												{role.users_count ?? 0}
											</Badge>
										</Link>
									</li>
								))}
							</ul>
						</CardContent>
					</Card>

					<LatestUsers
						title="Nieuwste klanten"
						users={latestCustomers}
						allHref={admin.customers.index().url}
						showHref={(user) => admin.customers.show(user.id).url}
						empty="Nog geen klanten."
					/>

					<LatestUsers
						title="Nieuwste medewerkers"
						users={latestEmployees}
						allHref={admin.employees.index().url}
						showHref={(user) => admin.employees.show(user.id).url}
						empty="Nog geen medewerkers."
						withRole
					/>
				</div>
			</div>
		</>
	);
}

function LatestUsers({
	title,
	users,
	allHref,
	showHref,
	empty,
	withRole = false,
}: {
	title: string;
	users: ManagedUser[];
	allHref: string;
	showHref: (user: ManagedUser) => string;
	empty: string;
	withRole?: boolean;
}) {
	return (
		<Card>
			<CardHeader className="flex flex-row items-center justify-between">
				<CardTitle>{title}</CardTitle>
				<Link
					href={allHref}
					className="text-muted-foreground text-sm hover:underline"
				>
					Alles bekijken
				</Link>
			</CardHeader>
			<CardContent>
				{users.length === 0 ? (
					<p className="text-muted-foreground text-sm">{empty}</p>
				) : (
					<ul className="divide-y">
						{users.map((user) => (
							<li key={user.id}>
								<Link
									href={showHref(user)}
									className="hover:bg-accent -mx-2 flex items-center justify-between gap-2 rounded-md px-2 py-2 text-sm"
								>
									<span className="truncate">
										{user.name}
									</span>
									<span className="text-muted-foreground shrink-0 text-xs">
										{withRole
											? roleLabel(user.role?.name)
											: formatDate(user.created_at)}
									</span>
								</Link>
							</li>
						))}
					</ul>
				)}
			</CardContent>
		</Card>
	);
}

UserManagement.layout = {
	breadcrumbs: [{ title: 'Gebruikersbeheer', href: admin.users() }],
};
