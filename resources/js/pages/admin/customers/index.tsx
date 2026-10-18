import { Form, Head, Link } from '@inertiajs/react';
import { Eye, Pencil, Search, UserPlus } from 'lucide-react';
import DeleteUserDialog from '@/components/admin/delete-user-dialog';
import Pagination from '@/components/admin/pagination';
import { statusLabel } from '@/components/admin/role-label';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import admin from '@/routes/admin';
import type { ManagedUser, Paginated } from '@/types/admin';

type Props = {
	customers: Paginated<ManagedUser>;
	filters: { search: string };
};

export default function CustomersIndex({ customers, filters }: Props) {
	return (
		<>
			<Head title="Klanten" />

			<div className="flex flex-col gap-6 p-4">
				<div className="flex flex-wrap items-start justify-between gap-4">
					<Heading
						title="Klanten"
						description="Alle klanten van de praktijk"
					/>
					<Button asChild>
						<Link href={admin.customers.create()}>
							<UserPlus />
							Nieuwe klant
						</Link>
					</Button>
				</div>

				<Form
					{...admin.customers.index.form()}
					className="flex max-w-md gap-2"
				>
					<Input
						name="search"
						defaultValue={filters.search}
						placeholder="Zoek op naam of e-mail"
					/>
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
								<th className="px-4 py-3 font-medium">
									Telefoon
								</th>
								<th className="px-4 py-3 font-medium">
									Tandarts
								</th>
								<th className="px-4 py-3 font-medium">
									Status
								</th>
								<th className="px-4 py-3" />
							</tr>
						</thead>
						<tbody className="divide-y">
							{customers.data.length === 0 && (
								<tr>
									<td
										colSpan={6}
										className="text-muted-foreground px-4 py-8 text-center"
									>
										Geen klanten gevonden.
									</td>
								</tr>
							)}
							{customers.data.map((customer) => (
								<tr
									key={customer.id}
									className="hover:bg-muted/30"
								>
									<td className="px-4 py-3 font-medium">
										<Link
											href={admin.customers.show(
												customer.id,
											)}
											className="hover:underline"
										>
											{customer.name}
										</Link>
									</td>
									<td className="px-4 py-3">
										{customer.email}
									</td>
									<td className="px-4 py-3">
										{customer.phonenumber ?? '-'}
									</td>
									<td className="px-4 py-3">
										{customer.employee?.name ?? '-'}
									</td>
									<td className="px-4 py-3">
										<Badge
											variant={
												customer.status === 'active'
													? 'default'
													: 'secondary'
											}
										>
											{statusLabel(customer.status)}
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
													href={admin.customers.show(
														customer.id,
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
													href={admin.customers.edit(
														customer.id,
													)}
												>
													<Pencil />
												</Link>
											</Button>
											<DeleteUserDialog
												form={admin.customers.destroy.form(
													customer.id,
												)}
												name={customer.name}
												compact
											/>
										</div>
									</td>
								</tr>
							))}
						</tbody>
					</table>
				</div>

				<Pagination page={customers} />
			</div>
		</>
	);
}

CustomersIndex.layout = {
	breadcrumbs: [
		{ title: 'Gebruikersbeheer', href: admin.users() },
		{ title: 'Klanten', href: admin.customers.index() },
	],
};
