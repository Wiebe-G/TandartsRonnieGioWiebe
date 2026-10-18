import { Head } from '@inertiajs/react';
import UserForm from '@/components/admin/user-form';
import Heading from '@/components/heading';
import admin from '@/routes/admin';
import type { Dentist } from '@/types/admin';

export default function CreateCustomer({ dentists }: { dentists: Dentist[] }) {
	return (
		<>
			<Head title="Nieuwe klant" />

			<div className="p-4">
				<Heading
					title="Nieuwe klant"
					description="Maak een account aan voor een nieuwe klant"
				/>
				<UserForm
					form={admin.customers.store.form()}
					dentists={dentists}
					submitLabel="Klant aanmaken"
				/>
			</div>
		</>
	);
}

CreateCustomer.layout = {
	breadcrumbs: [
		{ title: 'Gebruikersbeheer', href: admin.users() },
		{ title: 'Klanten', href: admin.customers.index() },
		{ title: 'Nieuwe klant', href: admin.customers.create() },
	],
};
