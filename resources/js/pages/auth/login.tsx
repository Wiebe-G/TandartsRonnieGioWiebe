import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
	status?: string;
	canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
	return (
		<>
			<Head title="Inloggen" />

			<Form
				{...store.form()}
				resetOnSuccess={['password']}
				className="flex flex-col gap-6"
			>
				{({ processing, errors }) => (
					<>
						<div className="grid gap-6">
							<div className="grid gap-2">
								<Label htmlFor="email">E-mailadres</Label>
								<Input
									id="email"
									type="email"
									name="email"
									required
									autoFocus
									tabIndex={1}
									autoComplete="email"
									placeholder="naam@voorbeeld.nl"
								/>
								<InputError message={errors.email} />
							</div>

							<div className="grid gap-2">
								<div className="flex items-center">
									<Label htmlFor="password">Wachtwoord</Label>
									{canResetPassword && (
										<TextLink
											href={request()}
											className="ml-auto text-sm"
											tabIndex={5}
										>
											Wachtwoord vergeten?
										</TextLink>
									)}
								</div>
								<PasswordInput
									id="password"
									name="password"
									required
									tabIndex={2}
									autoComplete="current-password"
									placeholder="Wachtwoord"
								/>
								<InputError message={errors.password} />
							</div>

							<div className="flex items-center space-x-3">
								<Checkbox
									id="remember"
									name="remember"
									tabIndex={3}
								/>
								<Label htmlFor="remember">
									Ingelogd blijven
								</Label>
							</div>

							<Button
								type="submit"
								className="mt-4 w-full"
								tabIndex={4}
								disabled={processing}
								data-test="login-button"
							>
								{processing && <Spinner />}
								Inloggen
							</Button>
						</div>

						<div className="text-muted-foreground text-center text-sm">
							Nog geen account?{' '}
							<TextLink
								href={register()}
								data-test="register-link"
								tabIndex={5}
							>
								Registreren
							</TextLink>
						</div>
					</>
				)}
			</Form>

			{status && (
				<div className="mb-4 text-center text-sm font-medium text-green-600">
					{status}
				</div>
			)}
		</>
	);
}

Login.layout = {
	title: 'Inloggen',
	description: 'Log in met uw e-mailadres en wachtwoord',
};
