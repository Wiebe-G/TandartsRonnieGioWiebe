import { Head } from '@inertiajs/react';
import UserForm from '@/components/admin/user-form';
import Heading from '@/components/heading';
import admin from '@/routes/admin';
import type { Dentist, ManagedUser } from '@/types/admin';

export default function EditCustomer({
	customer,
	dentists,
}: {
	customer: ManagedUser;
	dentists: Dentist[];
}) {
	return (
		<>
			<Head title={`${customer.name} bewerken`} />

			<div className="p-4">
				<Heading
					title={`${customer.name} bewerken`}
					description="Pas de gegevens van deze klant aan"
				/>
				<UserForm
					form={admin.customers.update.form(customer.id)}
					user={customer}
					dentists={dentists}
					submitLabel="Wijzigingen opslaan"
				/>
			</div>
		</>
	);
}

EditCustomer.layout = {
	breadcrumbs: [
		{ title: 'Gebruikersbeheer', href: admin.users() },
		{ title: 'Klanten', href: admin.customers.index() },
		{ title: 'Bewerken', href: '#' },
	],
};
