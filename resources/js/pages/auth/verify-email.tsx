// Components
import { Form, Head } from '@inertiajs/react';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

export default function VerifyEmail({ status }: { status?: string }) {
	return (
		<>
			<Head title="E-mailadres bevestigen" />

			{status === 'verification-link-sent' && (
				<div className="mb-4 text-center text-sm font-medium text-green-600">
					Er is een nieuwe bevestigingslink gestuurd naar het
					e-mailadres dat u bij het registreren heeft opgegeven.
				</div>
			)}

			<Form {...send.form()} className="space-y-6 text-center">
				{({ processing }) => (
					<>
						<Button disabled={processing} variant="secondary">
							{processing && <Spinner />}
							Stuur de bevestigingsmail opnieuw
						</Button>

						<TextLink
							href={logout()}
							className="mx-auto block text-sm"
						>
							Uitloggen
						</TextLink>
					</>
				)}
			</Form>
		</>
	);
}

VerifyEmail.layout = {
	title: 'E-mailadres bevestigen',
	description:
		'Bevestig uw e-mailadres met de link die we u net gemaild hebben.',
};
