import { Head, Link } from '@inertiajs/react';
import { ChevronRight, ReceiptText } from 'lucide-react';
import Heading from '@/components/heading';
import { formatInvoiceDate } from '@/components/invoice-document';
import { InvoiceStatus } from '@/components/invoice-status';
import { Card, CardContent } from '@/components/ui/card';
import { index, show } from '@/routes/customer/invoices';
import type { InvoiceSummary } from '@/types/admin';

const euro = new Intl.NumberFormat('nl-NL', {
	style: 'currency',
	currency: 'EUR',
});

export default function CustomerInvoices({
	invoices,
}: {
	invoices: InvoiceSummary[];
}) {
	const toPay = invoices.filter((invoice) => invoice.status === 'pending');
	const totalToPay = toPay.reduce(
		(sum, invoice) => sum + Number(invoice.amount),
		0,
	);

	return (
		<>
			<Head title="Mijn facturen" />

			<div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-6">
				<Heading
					title="Mijn facturen"
					description={
						toPay.length > 0
							? `U heeft ${toPay.length} openstaande ${toPay.length === 1 ? 'factuur' : 'facturen'} (${euro.format(totalToPay)}).`
							: 'Alle facturen zijn betaald.'
					}
				/>

				{invoices.length === 0 ? (
					<Card>
						<CardContent className="text-muted-foreground flex items-center gap-3 pt-6 text-sm">
							<ReceiptText className="size-5" />U heeft nog geen
							facturen ontvangen.
						</CardContent>
					</Card>
				) : (
					<Card className="py-0">
						<ul className="divide-y">
							{invoices.map((invoice) => (
								<li key={invoice.invoice_id}>
									<Link
										href={show(invoice.invoice_id)}
										className="hover:bg-muted/40 flex items-center gap-4 px-6 py-4 text-sm transition-colors"
									>
										<span className="min-w-0 flex-1">
											<span className="block font-medium">
												Factuur {invoice.number}
											</span>
											<span className="text-muted-foreground block truncate">
												{formatInvoiceDate(
													invoice.invoicedate,
												)}{' '}
												·{' '}
												{invoice.treatments.join(', ')}
											</span>
										</span>
										<span className="tabular-nums">
											{euro.format(
												Number(invoice.amount),
											)}
										</span>
										<InvoiceStatus invoice={invoice} />
										<ChevronRight className="text-muted-foreground size-4 shrink-0" />
									</Link>
								</li>
							))}
						</ul>
					</Card>
				)}
			</div>
		</>
	);
}

CustomerInvoices.layout = {
	breadcrumbs: [{ title: 'Mijn facturen', href: index() }],
};
