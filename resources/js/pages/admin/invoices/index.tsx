import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Eye, FilePlus2, ReceiptText, Send } from 'lucide-react';
import Heading from '@/components/heading';
import { InvoiceStatus } from '@/components/invoice-status';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import admin from '@/routes/admin';
import type { AppointmentToInvoice, InvoiceSummary } from '@/types/admin';

type Props = {
	toInvoice: AppointmentToInvoice[];
	invoices: InvoiceSummary[];
};

const euro = new Intl.NumberFormat('nl-NL', {
	style: 'currency',
	currency: 'EUR',
});
const shortDate = new Intl.DateTimeFormat('nl-NL', {
	day: 'numeric',
	month: 'short',
	year: 'numeric',
});

function formatDate(value: string): string {
	return shortDate.format(
		new Date(value.length === 10 ? `${value}T00:00:00` : value),
	);
}

export default function InvoicesIndex({ toInvoice, invoices }: Props) {
	const { errors } = usePage().props;
	const notSent = invoices.filter((invoice) => !invoice.sent_at).length;
	const pending = invoices.filter(
		(invoice) => invoice.status === 'pending',
	).length;

	return (
		<>
			<Head title="Facturen" />

			<div className="flex flex-col gap-6 p-4">
				<Heading
					title="Facturen"
					description={`${toInvoice.length} afspraken te factureren · ${notSent} nog niet verstuurd · ${pending} openstaand`}
				/>

				<InputError message={errors.invoice} />

				<Card>
					<CardHeader className="flex flex-row items-center justify-between">
						<CardTitle>
							Te factureren ({toInvoice.length})
						</CardTitle>
						<FilePlus2 className="text-muted-foreground size-4" />
					</CardHeader>
					<CardContent>
						{toInvoice.length === 0 ? (
							<p className="text-muted-foreground text-sm">
								Alle afgeronde afspraken zijn gefactureerd.
							</p>
						) : (
							<ul className="divide-y">
								{toInvoice.map((appointment) => (
									<li
										key={appointment.appointment_id}
										className="flex flex-wrap items-center justify-between gap-3 py-3 text-sm"
									>
										<span className="min-w-0">
											<span className="block font-medium">
												{appointment.customer?.name ??
													'Onbekende klant'}
											</span>
											<span className="text-muted-foreground block">
												{formatDate(appointment.date)} ·{' '}
												{appointment.treatments
													.map(
														(treatment) =>
															treatment.name,
													)
													.join(', ') ||
													'Geen behandeling'}
												{appointment.dentist &&
													` · ${appointment.dentist.name}`}
											</span>
										</span>
										<span className="flex items-center gap-3">
											<span className="font-medium tabular-nums">
												{euro.format(
													Number(appointment.total),
												)}
											</span>
											<Form
												{...admin.invoices.store.form(
													appointment.appointment_id,
												)}
											>
												{({ processing }) => (
													<Button
														type="submit"
														size="sm"
														disabled={processing}
													>
														Factuur maken
													</Button>
												)}
											</Form>
										</span>
									</li>
								))}
							</ul>
						)}
					</CardContent>
				</Card>

				<Card>
					<CardHeader className="flex flex-row items-center justify-between">
						<CardTitle>Facturen ({invoices.length})</CardTitle>
						<ReceiptText className="text-muted-foreground size-4" />
					</CardHeader>
					<CardContent>
						{invoices.length === 0 ? (
							<p className="text-muted-foreground text-sm">
								Nog geen facturen.
							</p>
						) : (
							<div className="overflow-x-auto">
								<table className="w-full text-sm">
									<thead className="text-muted-foreground text-left">
										<tr>
											<th className="py-2 pr-4 font-medium">
												Nummer
											</th>
											<th className="py-2 pr-4 font-medium">
												Klant
											</th>
											<th className="py-2 pr-4 font-medium">
												Datum
											</th>
											<th className="py-2 pr-4 font-medium">
												Bedrag
											</th>
											<th className="py-2 pr-4 font-medium">
												Betaling
											</th>
											<th className="py-2 pr-4 font-medium">
												Verstuurd
											</th>
											<th className="py-2" />
										</tr>
									</thead>
									<tbody className="divide-y">
										{invoices.map((invoice) => (
											<tr key={invoice.invoice_id}>
												<td className="py-2 pr-4 font-medium">
													<Link
														href={admin.invoices.show(
															invoice.invoice_id,
														)}
														className="hover:underline"
													>
														{invoice.number}
													</Link>
												</td>
												<td className="py-2 pr-4">
													{invoice.customer_name}
												</td>
												<td className="py-2 pr-4 tabular-nums">
													{formatDate(
														invoice.invoicedate,
													)}
												</td>
												<td className="py-2 pr-4 tabular-nums">
													{euro.format(
														Number(invoice.amount),
													)}
												</td>
												<td className="py-2 pr-4">
													<InvoiceStatus
														invoice={invoice}
													/>
												</td>
												<td className="py-2 pr-4">
													{invoice.sent_at ? (
														<Badge variant="secondary">
															{formatDate(
																invoice.sent_at,
															)}
														</Badge>
													) : (
														<Badge>
															Nog niet verstuurd
														</Badge>
													)}
												</td>
												<td className="flex justify-end gap-2 py-2">
													<Button
														asChild
														size="sm"
														variant="outline"
													>
														<Link
															href={admin.invoices.show(
																invoice.invoice_id,
															)}
														>
															<Eye />
															Bekijken
														</Link>
													</Button>
													<Form
														{...admin.invoices.send.form(
															invoice.invoice_id,
														)}
														options={{
															preserveScroll: true,
														}}
													>
														{({ processing }) => (
															<Button
																type="submit"
																size="sm"
																variant={
																	invoice.sent_at
																		? 'outline'
																		: 'default'
																}
																disabled={
																	processing
																}
															>
																<Send />
																{invoice.sent_at
																	? 'Opnieuw versturen'
																	: 'Versturen'}
															</Button>
														)}
													</Form>
												</td>
											</tr>
										))}
									</tbody>
								</table>
							</div>
						)}
					</CardContent>
				</Card>
			</div>
		</>
	);
}

InvoicesIndex.layout = {
	breadcrumbs: [{ title: 'Facturen', href: admin.invoices.index() }],
};
