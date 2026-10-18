import { Head, Link } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import DeleteUserDialog from '@/components/admin/delete-user-dialog';
import { formatDate, statusLabel } from '@/components/admin/role-label';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import admin from '@/routes/admin';
import type { AdminAppointment, ManagedUser } from '@/types/admin';

type Props = {
	customer: ManagedUser;
	appointments: AdminAppointment[];
};

export default function ShowCustomer({ customer, appointments }: Props) {
	return (
		<>
			<Head title={customer.name} />

			<div className="flex flex-col gap-6 p-4">
				<div className="flex flex-wrap items-start justify-between gap-4">
					<Heading title={customer.name} description="Klant" />
					<div className="flex gap-2">
						<Button asChild variant="outline">
							<Link href={admin.customers.edit(customer.id)}>
								<Pencil />
								Bewerken
							</Link>
						</Button>
						<DeleteUserDialog
							form={admin.customers.destroy.form(customer.id)}
							name={customer.name}
						/>
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
								<dd className="break-all">{customer.email}</dd>
								<dt className="text-muted-foreground">
									Telefoon
								</dt>
								<dd>{customer.phonenumber ?? '-'}</dd>
								<dt className="text-muted-foreground">
									Geboortedatum
								</dt>
								<dd>{formatDate(customer.birthday)}</dd>
								<dt className="text-muted-foreground">
									Tandarts
								</dt>
								<dd>{customer.employee?.name ?? '-'}</dd>
								<dt className="text-muted-foreground">
									Status
								</dt>
								<dd>
									<Badge
										variant={
											customer.status === 'active'
												? 'default'
												: 'secondary'
										}
									>
										{statusLabel(customer.status)}
									</Badge>
								</dd>
								<dt className="text-muted-foreground">
									Klant sinds
								</dt>
								<dd>{formatDate(customer.created_at)}</dd>
							</dl>
						</CardContent>
					</Card>

					<Card className="lg:col-span-2">
						<CardHeader>
							<CardTitle>
								Afspraken ({appointments.length})
							</CardTitle>
						</CardHeader>
						<CardContent>
							{appointments.length === 0 ? (
								<p className="text-muted-foreground text-sm">
									Deze klant heeft nog geen afspraken.
								</p>
							) : (
								<ul className="divide-y text-sm">
									{appointments.map((appointment) => (
										<li
											key={appointment.appointment_id}
											className="flex flex-wrap justify-between gap-2 py-2"
										>
											<span>
												{appointment.treatments
													.map(
														(treatment) =>
															treatment.name,
													)
													.join(', ') || 'Afspraak'}
												{appointment.dentist && (
													<span className="text-muted-foreground">
														{' '}
														bij{' '}
														{
															appointment.dentist
																.name
														}
													</span>
												)}
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

ShowCustomer.layout = {
	breadcrumbs: [
		{ title: 'Gebruikersbeheer', href: admin.users() },
		{ title: 'Klanten', href: admin.customers.index() },
		{ title: 'Klant', href: '#' },
	],
};
