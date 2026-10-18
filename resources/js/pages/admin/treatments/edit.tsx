import { Head } from '@inertiajs/react';
import TreatmentForm from '@/components/admin/treatment-form';
import Heading from '@/components/heading';
import admin from '@/routes/admin';
import type { AdminTreatment } from '@/types/admin';

export default function EditTreatment({
	treatment,
	appointmentsCount,
}: {
	treatment: AdminTreatment;
	appointmentsCount: number;
}) {
	return (
		<>
			<Head title={`${treatment.name} bewerken`} />

			<div className="p-4">
				<Heading
					title={`${treatment.name} bewerken`}
					description={
						appointmentsCount > 0
							? `Gebruikt in ${appointmentsCount} afspraken. Een nieuwe prijs of duur geldt niet voor bestaande afspraken.`
							: 'Nog niet gebruikt in afspraken.'
					}
				/>
				<TreatmentForm
					form={admin.treatments.update.form(treatment.treatment_id)}
					treatment={treatment}
					submitLabel="Wijzigingen opslaan"
				/>
			</div>
		</>
	);
}

EditTreatment.layout = {
	breadcrumbs: [
		{ title: 'Behandelingen', href: admin.treatments.index() },
		{ title: 'Bewerken', href: '#' },
	],
};
