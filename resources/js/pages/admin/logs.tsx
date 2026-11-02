import { Form, Head } from '@inertiajs/react';
import { Search } from 'lucide-react';
import Pagination from '@/components/admin/pagination';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import admin from '@/routes/admin';
import type { Paginated } from '@/types/admin';

type ActivityEntry = {
	id: number;
	action: string;
	subject_type: string | null;
	subject_id: number | null;
	description: string;
	changes: Record<string, [unknown, unknown]> | null;
	created_at: string;
	user: { id: number; name: string } | null;
};

type Props = {
	filters: { search: string; action: string };
	activity: Paginated<ActivityEntry>;
};

const actions: Record<string, string> = {
	created: 'Aangemaakt',
	updated: 'Gewijzigd',
	deleted: 'Verwijderd',
	login: 'Ingelogd',
	logout: 'Uitgelogd',
	login_failed: 'Mislukte login',
};

const dateTime = new Intl.DateTimeFormat('nl-NL', {
	dateStyle: 'short',
	timeStyle: 'medium',
});

function actionVariant(action: string) {
	if (action === 'deleted' || action === 'login_failed') {
		return 'destructive';
	}

	return action === 'created' ? 'default' : 'secondary';
}

function show(value: unknown) {
	if (value === null || value === undefined || value === '') {
		return '–';
	}

	return typeof value === 'object' ? JSON.stringify(value) : String(value);
}

export default function Logs({ filters, activity }: Props) {
	return (
		<>
			<Head title="Logboek" />

			<div className="flex flex-col gap-6 p-4">
				<Heading
					title="Logboek"
					description="Wie wat gedaan heeft in de applicatie"
				/>

				<Form
					{...admin.logs.index.form()}
					className="flex flex-wrap gap-2"
				>
					<Input
						name="search"
						defaultValue={filters.search}
						placeholder="Zoek op omschrijving of gebruiker"
						className="max-w-xs"
					/>
					<NativeSelect
						name="action"
						defaultValue={filters.action}
						className="w-48"
						aria-label="Actie"
					>
						<option value="">Alle acties</option>
						{Object.entries(actions).map(
							([value, label]) => (
								<option key={value} value={value}>
									{label}
								</option>
							),
						)}
					</NativeSelect>
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
								<th className="px-4 py-3 font-medium">
									Tijdstip
								</th>
								<th className="px-4 py-3 font-medium">
									Gebruiker
								</th>
								<th className="px-4 py-3 font-medium">
									Actie
								</th>
								<th className="px-4 py-3 font-medium">
									Omschrijving
								</th>
							</tr>
						</thead>
						<tbody className="divide-y">
							{activity.data.length === 0 && (
								<tr>
									<td
										colSpan={4}
										className="text-muted-foreground px-4 py-8 text-center"
									>
										Nog niets gelogd.
									</td>
								</tr>
							)}
							{activity.data.map((entry) => (
								<tr key={entry.id} className="align-top">
									<td className="px-4 py-3 whitespace-nowrap">
										{dateTime.format(
											new Date(entry.created_at),
										)}
									</td>
									<td className="px-4 py-3">
										{entry.user?.name ?? (
											<span className="text-muted-foreground">
												Onbekend
											</span>
										)}
									</td>
									<td className="px-4 py-3">
										<Badge
											variant={actionVariant(
												entry.action,
											)}
										>
											{actions[entry.action] ??
												entry.action}
										</Badge>
									</td>
									<td className="px-4 py-3">
										{entry.description}
										{entry.changes && (
											<details className="mt-1">
												<summary className="text-muted-foreground cursor-pointer text-xs">
													{
														Object.keys(
															entry.changes,
														).length
													}{' '}
													veld(en) gewijzigd
												</summary>
												<ul className="mt-1 space-y-0.5 text-xs">
													{Object.entries(
														entry.changes,
													).map(
														([
															field,
															[
																before,
																after,
															],
														]) => (
															<li
																key={
																	field
																}
															>
																<span className="font-medium">
																	{
																		field
																	}
																</span>
																:{' '}
																<span className="text-muted-foreground line-through">
																	{show(
																		before,
																	)}
																</span>{' '}
																→{' '}
																{show(
																	after,
																)}
															</li>
														),
													)}
												</ul>
											</details>
										)}
									</td>
								</tr>
							))}
						</tbody>
					</table>
				</div>

				<Pagination page={activity} />
			</div>
		</>
	);
}

Logs.layout = {
	breadcrumbs: [{ title: 'Logboek', href: admin.logs.index() }],
};
