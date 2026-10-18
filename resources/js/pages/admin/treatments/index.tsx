import { Head, Link } from '@inertiajs/react';
import { Pencil, Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';
import type { AdminTreatment } from '@/types/admin';

const money = new Intl.NumberFormat('nl-NL', {
	style: 'currency',
	currency: 'EUR',
});

export default function TreatmentsIndex({
	treatments,
}: {
	treatments: AdminTreatment[];
}) {
	return (
		<>
			<Head title="Behandelingen" />

			<div className="flex flex-col gap-6 p-4">
				<div className="flex flex-wrap items-start justify-between gap-4">
					<Heading
						title="Behandelingen"
						description="Prijzen en duur van alle behandelingen"
					/>
					<Button asChild>
						<Link href={admin.treatments.create()}>
							<Plus />
							Nieuwe behandeling
						</Link>
					</Button>
				</div>

				<div className="overflow-x-auto rounded-xl border">
					<table className="w-full text-sm">
						<thead className="bg-muted/50 text-muted-foreground text-left">
							<tr>
								<th className="px-4 py-3 font-medium">Naam</th>
								<th className="px-4 py-3 font-medium">Duur</th>
								<th className="px-4 py-3 font-medium">Prijs</th>
								<th className="px-4 py-3 font-medium">
									Afspraken
								</th>
								<th className="px-4 py-3 font-medium">
									Status
								</th>
								<th className="px-4 py-3" />
							</tr>
						</thead>
						<tbody className="divide-y">
							{treatments.length === 0 && (
								<tr>
									<td
										colSpan={6}
										className="text-muted-foreground px-4 py-8 text-center"
									>
										Nog geen behandelingen.
									</td>
								</tr>
							)}
							{treatments.map((treatment) => (
								<tr
									key={treatment.treatment_id}
									className="hover:bg-muted/30"
								>
									<td className="px-4 py-3">
										<span className="font-medium">
											{treatment.name}
										</span>
										{treatment.description && (
											<span className="text-muted-foreground block truncate text-xs">
												{treatment.description}
											</span>
										)}
									</td>
									<td className="px-4 py-3 tabular-nums">
										{treatment.duration} min
									</td>
									<td className="px-4 py-3 tabular-nums">
										{money.format(Number(treatment.price))}
									</td>
									<td className="px-4 py-3 tabular-nums">
										{treatment.appointments_count ?? 0}
									</td>
									<td className="px-4 py-3">
										<Badge
											variant={
												treatment.active
													? 'default'
													: 'secondary'
											}
										>
											{treatment.active
												? 'Actief'
												: 'Inactief'}
										</Badge>
									</td>
									<td className="px-4 py-3 text-right">
										<Button
											asChild
											variant="ghost"
											size="icon"
											aria-label={`${treatment.name} bewerken`}
										>
											<Link
												href={admin.treatments.edit(
													treatment.treatment_id,
												)}
											>
												<Pencil />
											</Link>
										</Button>
									</td>
								</tr>
							))}
						</tbody>
					</table>
				</div>
			</div>
		</>
	);
}

TreatmentsIndex.layout = {
	breadcrumbs: [{ title: 'Behandelingen', href: admin.treatments.index() }],
};
