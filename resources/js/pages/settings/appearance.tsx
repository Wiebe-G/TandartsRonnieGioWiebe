import { Head } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import { edit as editAppearance } from '@/routes/appearance';

export default function Appearance() {
	return (
		<>
			<Head title="Weergave" />

			<h1 className="sr-only">Weergave</h1>

			<div className="space-y-6">
				<Heading
					variant="small"
					title="Weergave"
					description="Kies een lichte of donkere weergave."
				/>
				<AppearanceTabs />
			</div>
		</>
	);
}

Appearance.layout = {
	breadcrumbs: [
		{
			title: 'Weergave',
			href: editAppearance(),
		},
	],
};
