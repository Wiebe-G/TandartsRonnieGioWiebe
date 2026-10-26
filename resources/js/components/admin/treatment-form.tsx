import { Form, usePage } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { AdminTreatment } from '@/types/admin';

type Props = {
	form: { action: string; method: 'get' | 'post' };
	submitLabel: string;
	/** Only when editing; a new treatment always starts active. */
	treatment?: AdminTreatment;
};

export default function TreatmentForm({ form, submitLabel, treatment }: Props) {
	const [active, setActive] = useState(treatment?.active ?? true);
	const { auth } = usePage().props;
	// Only admins may change the price of an existing treatment
	const priceLocked = treatment !== undefined && !auth.isAdmin;

	return (
		<Form
			{...form}
			options={{ preserveScroll: true }}
			className="max-w-xl space-y-6"
		>
			{({ processing, errors }) => (
				<>
					<div className="grid gap-2">
						<Label htmlFor="name">Naam</Label>
						<Input
							id="name"
							name="name"
							defaultValue={treatment?.name}
							required
						/>
						<InputError message={errors.name} />
					</div>

					<div className="grid gap-2">
						<Label htmlFor="description">Beschrijving</Label>
						<textarea
							id="description"
							name="description"
							defaultValue={treatment?.description ?? ''}
							rows={3}
							className="border-input dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
						/>
						<InputError message={errors.description} />
					</div>

					<div className="grid gap-6 sm:grid-cols-2">
						<div className="grid gap-2">
							<Label htmlFor="price">Prijs (€)</Label>
							<Input
								id="price"
								name="price"
								type="number"
								step="0.01"
								min="0"
								defaultValue={treatment?.price}
								disabled={priceLocked}
								required
							/>
							{priceLocked && (
								<p className="text-muted-foreground text-xs">
									Alleen een beheerder kan de prijs wijzigen.
								</p>
							)}
							<InputError message={errors.price} />
						</div>

						<div className="grid gap-2">
							<Label htmlFor="duration">Duur (minuten)</Label>
							<Input
								id="duration"
								name="duration"
								type="number"
								step="5"
								min="5"
								defaultValue={treatment?.duration ?? 30}
								required
							/>
							<p className="text-muted-foreground text-xs">
								In de agenda afgerond naar een half uur.
							</p>
							<InputError message={errors.duration} />
						</div>
					</div>

					{treatment && (
						<>
							<div className="flex items-start gap-3">
								<input
									type="hidden"
									name="active"
									value={active ? 1 : 0}
								/>
								<Checkbox
									id="active"
									checked={active}
									onCheckedChange={(checked) =>
										setActive(checked === true)
									}
								/>
								<div className="grid gap-1">
									<Label htmlFor="active">Actief</Label>
									<p className="text-muted-foreground text-sm">
										Alleen actieve behandelingen zijn te
										kiezen bij een nieuwe afspraak.
									</p>
								</div>
							</div>
							<InputError message={errors.active} />
						</>
					)}

					<Button disabled={processing}>
						{processing && <Spinner />}
						{submitLabel}
					</Button>
				</>
			)}
		</Form>
	);
}
