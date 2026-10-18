import { Link, router } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import type { ComponentProps } from 'react';
import { Button } from '@/components/ui/button';
import { logout } from '@/routes';

/** Logs the user out; the server then sends them to the registration page. */
export function LogoutButton({
	variant = 'outline',
	size = 'sm',
	className,
}: Pick<ComponentProps<typeof Button>, 'variant' | 'size' | 'className'>) {
	return (
		<Button variant={variant} size={size} className={className} asChild>
			<Link
				href={logout()}
				as="button"
				onClick={() => router.flushAll()}
				data-test="logout-button"
			>
				<LogOut />
				Uitloggen
			</Link>
		</Button>
	);
}
