import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, CircleCheck, RotateCcw, Send } from 'lucide-react';
import Heading from '@/components/heading';
import {
	formatInvoiceDate,
	InvoiceDocument,
} from '@/components/invoice-document';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';
import type { InvoiceDetail } from '@/types/admin';

export default function ShowInvoice({ invoice }: { invoice: InvoiceDetail }) {
	return (
		<>
			<Head title={`Factuur ${invoice.number}`} />

			<div className="flex flex-col gap-6 p-4">
				<div className="flex flex-wrap items-start justify-between gap-4">
					<Heading
						title={`Factuur ${invoice.number}`}
						description={
							invoice.sent_at
								? `Verstuurd naar ${invoice.customer.email} op ${formatInvoiceDate(invoice.sent_at)}`
								: `Nog niet verstuurd naar ${invoice.customer.name}`
						}
					/>
					<div className="flex flex-wrap gap-2">
						<Button asChild variant="ghost">
							<Link href={admin.invoices.index()}>
								<ArrowLeft />
								Terug
							</Link>
						</Button>
						<Form
							{...admin.invoices.payment.form(invoice.invoice_id)}
							options={{ preserveScroll: true }}
						>
							{({ processing }) => (
								<>
									<input
										type="hidden"
										name="paid"
										value={
											invoice.status === 'paid' ? 0 : 1
										}
									/>
									<Button
										type="submit"
										variant="outline"
										disabled={processing}
									>
										{invoice.status === 'paid' ? (
											<>
												<RotateCcw />
												Terug naar openstaand
											</>
										) : (
											<>
												<CircleCheck />
												Markeer als betaald
											</>
										)}
									</Button>
								</>
							)}
						</Form>
						<Form
							{...admin.invoices.send.form(invoice.invoice_id)}
							options={{ preserveScroll: true }}
						>
							{({ processing }) => (
								<Button type="submit" disabled={processing}>
									<Send />
									{invoice.sent_at
										? 'Opnieuw versturen'
										: `Versturen naar ${invoice.customer.email}`}
								</Button>
							)}
						</Form>
					</div>
				</div>

				<InvoiceDocument invoice={invoice} />
			</div>
		</>
	);
}

ShowInvoice.layout = {
	breadcrumbs: [
		{ title: 'Facturen', href: admin.invoices.index() },
		{ title: 'Factuur', href: '#' },
	],
};
