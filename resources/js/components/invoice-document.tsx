import { InvoiceStatus } from '@/components/invoice-status';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { InvoiceDetail } from '@/types/admin';

const euro = new Intl.NumberFormat('nl-NL', {
	style: 'currency',
	currency: 'EUR',
});
const longDate = new Intl.DateTimeFormat('nl-NL', {
	day: 'numeric',
	month: 'long',
	year: 'numeric',
});

export function formatInvoiceDate(value: string): string {
	return longDate.format(
		new Date(value.length === 10 ? `${value}T00:00:00` : value),
	);
}

/** The invoice itself; the same for the staff and for the customer. */
export function InvoiceDocument({ invoice }: { invoice: InvoiceDetail }) {
	return (
		<Card className="max-w-3xl">
			<CardHeader className="flex flex-row items-center justify-between">
				<CardTitle>Factuur {invoice.number}</CardTitle>
				<InvoiceStatus invoice={invoice} />
			</CardHeader>
			<CardContent className="space-y-6 text-sm">
				<div className="grid gap-6 sm:grid-cols-2">
					<div>
						<p className="text-muted-foreground mb-1">Aan</p>
						<p className="font-medium">{invoice.customer.name}</p>
						{invoice.customer.address ? (
							<>
								<p>{invoice.customer.address.street}</p>
								<p>{invoice.customer.address.city}</p>
							</>
						) : (
							<p className="text-muted-foreground">
								Geen adres bekend
							</p>
						)}
						<p className="text-muted-foreground">
							{invoice.customer.email}
						</p>
					</div>
					<dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1">
						<dt className="text-muted-foreground">Factuurnummer</dt>
						<dd>{invoice.number}</dd>
						<dt className="text-muted-foreground">Factuurdatum</dt>
						<dd>{formatInvoiceDate(invoice.invoicedate)}</dd>
						<dt className="text-muted-foreground">Betalen voor</dt>
						<dd>{formatInvoiceDate(invoice.expiration_date)}</dd>
						{invoice.paid_at && (
							<>
								<dt className="text-muted-foreground">
									Betaald op
								</dt>
								<dd>{formatInvoiceDate(invoice.paid_at)}</dd>
							</>
						)}
						<dt className="text-muted-foreground">Afspraak</dt>
						<dd>
							{formatInvoiceDate(invoice.appointment.date)},{' '}
							{invoice.appointment.starttime}
							{invoice.appointment.dentist &&
								` bij ${invoice.appointment.dentist}`}
						</dd>
					</dl>
				</div>

				<table className="w-full">
					<thead className="text-muted-foreground border-b text-left">
						<tr>
							<th className="py-2 font-medium">Behandeling</th>
							<th className="py-2 text-right font-medium">
								Prijs
							</th>
						</tr>
					</thead>
					<tbody className="divide-y">
						{invoice.appointment.treatments.map((treatment) => (
							<tr key={treatment.name}>
								<td className="py-2">{treatment.name}</td>
								<td className="py-2 text-right tabular-nums">
									{euro.format(Number(treatment.price))}
								</td>
							</tr>
						))}
					</tbody>
					<tfoot className="border-t font-medium">
						<tr>
							<td className="py-2">Totaal</td>
							<td className="py-2 text-right tabular-nums">
								{euro.format(Number(invoice.amount))}
							</td>
						</tr>
					</tfoot>
				</table>

				{invoice.status === 'pending' && (
					<p className="text-muted-foreground">
						Wilt u het bedrag uiterlijk{' '}
						{formatInvoiceDate(invoice.expiration_date)} overmaken
						onder vermelding van factuurnummer {invoice.number}?
					</p>
				)}
			</CardContent>
		</Card>
	);
}
