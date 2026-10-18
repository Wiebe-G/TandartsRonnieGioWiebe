import { Form, usePage } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { stop } from '@/routes/impersonation';

/** Shown on every page while an admin looks at the app as a customer. */
export default function ImpersonationBanner() {
	const { auth } = usePage().props;

	if (!auth.impersonating) {
		return null;
	}

	return (
		<div className="flex flex-wrap items-center justify-between gap-2 border-b border-amber-500/40 bg-amber-500/15 px-4 py-2 text-sm text-amber-900 dark:text-amber-200">
			<span className="flex items-center gap-2">
				<Eye className="size-4" />
				Je bekijkt de app als <strong>{auth.user.name}</strong>
			</span>
			<Form {...stop.form()}>
				{({ processing }) => (
					<Button
						type="submit"
						size="sm"
						variant="outline"
						disabled={processing}
					>
						Terug naar beheer
					</Button>
				)}
			</Form>
		</div>
	);
}
