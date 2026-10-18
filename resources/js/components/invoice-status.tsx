import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { InvoiceSummary } from '@/types/admin';

/** Openstaand / Betaald, plus "Te laat" for an open invoice past its payment date. */
export function InvoiceStatus({
	invoice,
	className,
}: {
	invoice: Pick<InvoiceSummary, 'status' | 'overdue'>;
	className?: string;
}) {
	if (invoice.status === 'paid') {
		return (
			<Badge
				className={cn(
					'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400',
					className,
				)}
			>
				Betaald
			</Badge>
		);
	}

	return (
		<span className={cn('inline-flex gap-1', className)}>
			<Badge className="bg-amber-500/15 text-amber-700 dark:text-amber-400">
				Openstaand
			</Badge>
			{invoice.overdue && (
				<Badge className="bg-red-500/15 text-red-700 dark:text-red-400">
					Te laat
				</Badge>
			)}
		</span>
	);
}
