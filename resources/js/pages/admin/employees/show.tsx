import { Head, Link, usePage } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import DeleteUserDialog from '@/components/admin/delete-user-dialog';
import {
	formatDate,
	roleLabel,
	statusLabel,
} from '@/components/admin/role-label';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import admin from '@/routes/admin';
import type { Auth } from '@/types';
import type { AdminAppointment, ManagedUser } from '@/types/admin';

type Props = {
	employee: ManagedUser;
	customers: ManagedUser[];
	appointments: AdminAppointment[];
};

export default function ShowEmployee({
	employee,
	customers,
	appointments,
}: Props) {
	const { auth } = usePage<{ auth: Auth }>().props;

	return (
		<>
			<Head title={employee.name} />

			<div className="flex flex-col gap-6 p-4">
				<div className="flex flex-wrap items-start justify-between gap-4">
					<Heading
						title={employee.name}
						description={roleLabel(employee.role?.name)}
					/>
					<div className="flex gap-2">
						<Button asChild variant="outline">
							<Link href={admin.employees.edit(employee.id)}>
								<Pencil />
								Bewerken
							</Link>
						</Button>
						{employee.id !== auth.user.id && (
							<DeleteUserDialog
								form={admin.employees.destroy.form(employee.id)}
								name={employee.name}
							/>
						)}
					</div>
				</div>

				<div className="grid gap-4 lg:grid-cols-3">
					<Card>
						<CardHeader>
							<CardTitle>Gegevens</CardTitle>
						</CardHeader>
						<CardContent>
							<dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
								<dt className="text-muted-foreground">
									E-mail
								</dt>
								<dd className="break-all">{employee.email}</dd>
								<dt className="text-muted-foreground">
									Telefoon
								</dt>
								<dd>{employee.phonenumber ?? '-'}</dd>
								<dt className="text-muted-foreground">
									Geboortedatum
								</dt>
								<dd>{formatDate(employee.birthday)}</dd>
								<dt className="text-muted-foreground">Rol</dt>
								<dd>{roleLabel(employee.role?.name)}</dd>
								<dt className="text-muted-foreground">
									Status
								</dt>
								<dd>
									<Badge
										variant={
											employee.status === 'active'
												? 'default'
												: 'secondary'
										}
									>
										{statusLabel(employee.status)}
									</Badge>
								</dd>
								<dt className="text-muted-foreground">
									In dienst sinds
								</dt>
								<dd>{formatDate(employee.created_at)}</dd>
							</dl>
						</CardContent>
					</Card>

					<Card>
						<CardHeader>
							<CardTitle>
								Eigen klanten ({customers.length})
							</CardTitle>
						</CardHeader>
						<CardContent>
							{customers.length === 0 ? (
								<p className="text-muted-foreground text-sm">
									Geen klanten toegewezen.
								</p>
							) : (
								<ul className="divide-y text-sm">
									{customers.map((customer) => (
										<li key={customer.id}>
											<Link
												href={admin.customers.show(
													customer.id,
												)}
												className="block py-2 hover:underline"
											>
												{customer.name}
											</Link>
										</li>
									))}
								</ul>
							)}
						</CardContent>
					</Card>

					<Card>
						<CardHeader>
							<CardTitle>
								Komende afspraken ({appointments.length})
							</CardTitle>
						</CardHeader>
						<CardContent>
							{appointments.length === 0 ? (
								<p className="text-muted-foreground text-sm">
									Geen komende afspraken.
								</p>
							) : (
								<ul className="divide-y text-sm">
									{appointments.map((appointment) => (
										<li
											key={appointment.appointment_id}
											className="flex flex-wrap justify-between gap-2 py-2"
										>
											<span>
												{appointment.customer?.name ??
													'-'}
											</span>
											<span className="text-muted-foreground tabular-nums">
												{formatDate(appointment.date)}{' '}
												{appointment.starttime.slice(
													0,
													5,
												)}
											</span>
										</li>
									))}
								</ul>
							)}
						</CardContent>
					</Card>
				</div>
			</div>
		</>
	);
}

ShowEmployee.layout = {
	breadcrumbs: [
		{ title: 'Gebruikersbeheer', href: admin.users() },
		{ title: 'Medewerkers', href: admin.employees.index() },
		{ title: 'Medewerker', href: '#' },
	],
};
