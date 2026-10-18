import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Eye, Pencil, Search, UserPlus } from 'lucide-react';
import DeleteUserDialog from '@/components/admin/delete-user-dialog';
import Pagination from '@/components/admin/pagination';
import { roleLabel, statusLabel } from '@/components/admin/role-label';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import admin from '@/routes/admin';
import type { Auth } from '@/types';
import type { ManagedUser, Paginated, Role } from '@/types/admin';

type Props = {
	employees: Paginated<ManagedUser>;
	roles: Role[];
	filters: { search: string; role: number | null };
};

export default function EmployeesIndex({ employees, roles, filters }: Props) {
	const { auth } = usePage<{ auth: Auth }>().props;

	return (
		<>
			<Head title="Medewerkers" />

			<div className="flex flex-col gap-6 p-4">
				<div className="flex flex-wrap items-start justify-between gap-4">
					<Heading
						title="Medewerkers"
						description="Alle medewerkers van de praktijk"
					/>
					<Button asChild>
						<Link href={admin.employees.create()}>
							<UserPlus />
							Nieuwe medewerker
						</Link>
					</Button>
				</div>

				<Form
					{...admin.employees.index.form()}
					className="flex max-w-xl flex-wrap gap-2"
				>
					<Input
						name="search"
						defaultValue={filters.search}
						placeholder="Zoek op naam of e-mail"
						className="min-w-48 flex-1"
					/>
					<select
						name="role"
						defaultValue={filters.role ?? ''}
						aria-label="Filter op rol"
						className="border-input dark:bg-input/30 h-9 rounded-md border bg-transparent px-3 text-sm shadow-xs"
					>
						<option value="">Alle rollen</option>
						{roles.map((role) => (
							<option key={role.role_id} value={role.role_id}>
								{roleLabel(role.name)}
							</option>
						))}
					</select>
					<Button
						type="submit"
						variant="outline"
						size="icon"
						aria-label="Zoeken"
					>
						<Search />
					</Button>
				</Form>

				<div className="overflow-x-auto rounded-xl border">
					<table className="w-full text-sm">
						<thead className="bg-muted/50 text-muted-foreground text-left">
							<tr>
								<th className="px-4 py-3 font-medium">Naam</th>
								<th className="px-4 py-3 font-medium">
									E-mail
								</th>
								<th className="px-4 py-3 font-medium">Rol</th>
								<th className="px-4 py-3 font-medium">
									Klanten
								</th>
								<th className="px-4 py-3 font-medium">
									Status
								</th>
								<th className="px-4 py-3" />
							</tr>
						</thead>
						<tbody className="divide-y">
							{employees.data.length === 0 && (
								<tr>
									<td
										colSpan={6}
										className="text-muted-foreground px-4 py-8 text-center"
									>
										Geen medewerkers gevonden.
									</td>
								</tr>
							)}
							{employees.data.map((employee) => (
								<tr
									key={employee.id}
									className="hover:bg-muted/30"
								>
									<td className="px-4 py-3 font-medium">
										<Link
											href={admin.employees.show(
												employee.id,
											)}
											className="hover:underline"
										>
											{employee.name}
										</Link>
									</td>
									<td className="px-4 py-3">
										{employee.email}
									</td>
									<td className="px-4 py-3">
										{roleLabel(employee.role?.name)}
									</td>
									<td className="px-4 py-3 tabular-nums">
										{employee.customers_count ?? 0}
									</td>
									<td className="px-4 py-3">
										<Badge
											variant={
												employee.status === 'active'
													? 'default'
													: 'secondary'
											}
										>
											{statusLabel(employee.status)}
										</Badge>
									</td>
									<td className="px-4 py-3">
										<div className="flex justify-end gap-1">
											<Button
												asChild
												variant="ghost"
												size="icon"
												aria-label="Bekijken"
											>
												<Link
													href={admin.employees.show(
														employee.id,
													)}
												>
													<Eye />
												</Link>
											</Button>
											<Button
												asChild
												variant="ghost"
												size="icon"
												aria-label="Bewerken"
											>
												<Link
													href={admin.employees.edit(
														employee.id,
													)}
												>
													<Pencil />
												</Link>
											</Button>
											{employee.id !== auth.user.id && (
												<DeleteUserDialog
													form={admin.employees.destroy.form(
														employee.id,
													)}
													name={employee.name}
													compact
												/>
											)}
										</div>
									</td>
								</tr>
							))}
						</tbody>
					</table>
				</div>

				<Pagination page={employees} />
			</div>
		</>
	);
}

EmployeesIndex.layout = {
	breadcrumbs: [
		{ title: 'Gebruikersbeheer', href: admin.users() },
		{ title: 'Medewerkers', href: admin.employees.index() },
	],
};
