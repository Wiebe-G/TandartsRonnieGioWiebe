import { Head } from '@inertiajs/react';
import UserForm from '@/components/admin/user-form';
import Heading from '@/components/heading';
import admin from '@/routes/admin';
import type { ManagedUser, Role } from '@/types/admin';

export default function EditEmployee({
	employee,
	roles,
}: {
	employee: ManagedUser;
	roles: Role[];
}) {
	return (
		<>
			<Head title={`${employee.name} bewerken`} />

			<div className="p-4">
				<Heading
					title={`${employee.name} bewerken`}
					description="Pas de gegevens van deze medewerker aan"
				/>
				<UserForm
					form={admin.employees.update.form(employee.id)}
					user={employee}
					roles={roles}
					submitLabel="Wijzigingen opslaan"
				/>
			</div>
		</>
	);
}

EditEmployee.layout = {
	breadcrumbs: [
		{ title: 'Gebruikersbeheer', href: admin.users() },
		{ title: 'Medewerkers', href: admin.employees.index() },
		{ title: 'Bewerken', href: '#' },
	],
};
