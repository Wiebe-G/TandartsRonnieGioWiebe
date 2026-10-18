import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/admin';

export default function Pagination<T>({ page }: { page: Paginated<T> }) {
	if (page.links.length <= 3) {
		return null;
	}

	return (
		<nav className="flex flex-wrap items-center justify-between gap-2 text-sm">
			<span className="text-muted-foreground">
				{page.from}–{page.to} van {page.total}
			</span>
			<div className="flex flex-wrap gap-1">
				{page.links.map((link, index) => (
					<Link
						key={index}
						href={link.url ?? '#'}
						preserveScroll
						className={cn(
							'rounded-md border px-3 py-1',
							link.active && 'bg-primary text-primary-foreground',
							!link.url && 'pointer-events-none opacity-50',
						)}
					>
						{/* Laravel sends labels like "&laquo; Previous" */}
						<span
							dangerouslySetInnerHTML={{ __html: link.label }}
						/>
					</Link>
				))}
			</div>
		</nav>
	);
}
