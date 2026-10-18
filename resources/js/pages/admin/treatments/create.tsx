import { Head } from '@inertiajs/react';
import TreatmentForm from '@/components/admin/treatment-form';
import Heading from '@/components/heading';
import admin from '@/routes/admin';

export default function CreateTreatment() {
	return (
		<>
			<Head title="Nieuwe behandeling" />

			<div className="p-4">
				<Heading
					title="Nieuwe behandeling"
					description="Voeg een behandeling toe die bij afspraken gekozen kan worden"
				/>
				<TreatmentForm
					form={admin.treatments.store.form()}
					submitLabel="Behandeling aanmaken"
				/>
			</div>
		</>
	);
}

CreateTreatment.layout = {
	breadcrumbs: [
		{ title: 'Behandelingen', href: admin.treatments.index() },
		{ title: 'Nieuwe behandeling', href: admin.treatments.create() },
	],
};
