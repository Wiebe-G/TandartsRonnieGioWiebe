import { Head } from '@inertiajs/react';
import UserForm from '@/components/admin/user-form';
import Heading from '@/components/heading';
import admin from '@/routes/admin';
import type { Role } from '@/types/admin';

export default function CreateEmployee({ roles }: { roles: Role[] }) {
	return (
		<>
			<Head title="Nieuwe medewerker" />

			<div className="p-4">
				<Heading
					title="Nieuwe medewerker"
					description="Maak een account aan voor een nieuwe medewerker"
				/>
				<UserForm
					form={admin.employees.store.form()}
					roles={roles}
					submitLabel="Medewerker aanmaken"
				/>
			</div>
		</>
	);
}

CreateEmployee.layout = {
	breadcrumbs: [
		{ title: 'Gebruikersbeheer', href: admin.users() },
		{ title: 'Medewerkers', href: admin.employees.index() },
		{ title: 'Nieuwe medewerker', href: admin.employees.create() },
	],
};
