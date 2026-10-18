import { Form, Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, CreditCard } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { InvoiceDocument } from '@/components/invoice-document';
import { Button } from '@/components/ui/button';
import {
	Dialog,
	DialogClose,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogTitle,
	DialogTrigger,
} from '@/components/ui/dialog';
import { index, pay } from '@/routes/customer/invoices';
import type { InvoiceDetail } from '@/types/admin';

const euro = new Intl.NumberFormat('nl-NL', {
	style: 'currency',
	currency: 'EUR',
});

export default function CustomerInvoice({
	invoice,
}: {
	invoice: InvoiceDetail;
}) {
	const { errors } = usePage().props;
	const [open, setOpen] = useState(false);

	return (
		<>
			<Head title={`Factuur ${invoice.number}`} />

			<div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-6">
				<div className="flex flex-wrap items-start justify-between gap-4">
					<Heading
						title={`Factuur ${invoice.number}`}
						description={
							invoice.status === 'paid'
								? 'Deze factuur is betaald. Bedankt!'
								: invoice.overdue
									? 'De betaaltermijn van deze factuur is verlopen.'
									: 'Deze factuur staat nog open.'
						}
					/>
					<div className="flex gap-2">
						<Button asChild variant="ghost">
							<Link href={index()}>
								<ArrowLeft />
								Alle facturen
							</Link>
						</Button>
						{invoice.status === 'pending' && (
							<Dialog open={open} onOpenChange={setOpen}>
								<DialogTrigger asChild>
									<Button>
										<CreditCard />
										Betalen
									</Button>
								</DialogTrigger>
								<DialogContent>
									<DialogTitle>
										Factuur {invoice.number} betalen
									</DialogTitle>
									<DialogDescription>
										U betaalt{' '}
										{euro.format(Number(invoice.amount))}.
										Dit is een demo-betaling: er wordt geen
										echt geld afgeschreven.
									</DialogDescription>
									<Form
										{...pay.form(invoice.invoice_id)}
										onFinish={() => setOpen(false)}
									>
										{({ processing }) => (
											<DialogFooter className="gap-2">
												<DialogClose asChild>
													<Button
														type="button"
														variant="secondary"
													>
														Annuleren
													</Button>
												</DialogClose>
												<Button
													type="submit"
													disabled={processing}
												>
													Betaling bevestigen
												</Button>
											</DialogFooter>
										)}
									</Form>
								</DialogContent>
							</Dialog>
						)}
					</div>
				</div>

				<InputError message={errors.payment} />

				<InvoiceDocument invoice={invoice} />
			</div>
		</>
	);
}

CustomerInvoice.layout = {
	breadcrumbs: [
		{ title: 'Mijn facturen', href: index() },
		{ title: 'Factuur', href: '#' },
	],
};
