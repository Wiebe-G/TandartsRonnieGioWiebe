import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

type Props = {
	passwordRules: string;
	dentists: { id: number; name: string }[];
};

export default function Register({ passwordRules, dentists }: Props) {
	return (
		<>
			<Head title="Registreren" />
			<Form
				{...store.form()}
				resetOnSuccess={['password', 'password_confirmation']}
				disableWhileProcessing
				className="flex flex-col gap-6"
			>
				{({ processing, errors }) => (
					<>
						<div className="grid gap-6">
							<div className="grid gap-4 sm:grid-cols-2">
								<div className="grid gap-2">
									<Label htmlFor="firstname">Voornaam</Label>
									<Input
										id="firstname"
										type="text"
										required
										autoFocus
										tabIndex={1}
										autoComplete="given-name"
										name="firstname"
									/>
									<InputError
										message={errors.firstname}
										className="mt-2"
									/>
								</div>

								<div className="grid gap-2">
									<Label htmlFor="lastname">Achternaam</Label>
									<Input
										id="lastname"
										type="text"
										required
										tabIndex={2}
										autoComplete="family-name"
										name="lastname"
									/>
									<InputError
										message={errors.lastname}
										className="mt-2"
									/>
								</div>
							</div>

							<div className="grid gap-2">
								<Label htmlFor="email">E-mailadres</Label>
								<Input
									id="email"
									type="email"
									required
									tabIndex={3}
									autoComplete="email"
									name="email"
									placeholder="naam@voorbeeld.nl"
								/>
								<InputError message={errors.email} />
							</div>

							<div className="grid gap-2">
								<Label htmlFor="phonenumber">
									Telefoonnummer
								</Label>
								<Input
									id="phonenumber"
									type="tel"
									required
									tabIndex={4}
									autoComplete="tel"
									name="phonenumber"
									placeholder="06 12345678"
								/>
								<InputError message={errors.phonenumber} />
							</div>

							<div className="grid gap-2">
								<Label htmlFor="employee_id">
									Kies uw tandarts{' '}
									<span className="text-muted-foreground font-normal">
										(optioneel)
									</span>
								</Label>
								<NativeSelect
									id="employee_id"
									name="employee_id"
									tabIndex={5}
									defaultValue=""
								>
									<option value="">Nog geen voorkeur</option>
									{dentists.map((dentist) => (
										<option
											key={dentist.id}
											value={dentist.id}
										>
											{dentist.name}
										</option>
									))}
								</NativeSelect>
								<InputError message={errors.employee_id} />
							</div>

							<div className="grid gap-2">
								<Label htmlFor="password">Wachtwoord</Label>
								<PasswordInput
									id="password"
									required
									tabIndex={6}
									autoComplete="new-password"
									name="password"
									passwordrules={passwordRules}
								/>
								<InputError message={errors.password} />
							</div>

							<div className="grid gap-2">
								<Label htmlFor="password_confirmation">
									Herhaal wachtwoord
								</Label>
								<PasswordInput
									id="password_confirmation"
									required
									tabIndex={7}
									autoComplete="new-password"
									name="password_confirmation"
									passwordrules={passwordRules}
								/>
								<InputError
									message={errors.password_confirmation}
								/>
							</div>

							<Button
								type="submit"
								className="mt-2 w-full"
								tabIndex={8}
								data-test="register-user-button"
							>
								{processing && <Spinner />}
								Account aanmaken
							</Button>
						</div>

						<div className="text-muted-foreground text-center text-sm">
							Heeft u al een account?{' '}
							<TextLink
								href={login()}
								data-test="login-link"
								tabIndex={9}
							>
								Inloggen
							</TextLink>
						</div>
					</>
				)}
			</Form>
		</>
	);
}

Register.layout = {
	title: 'Account aanmaken',
	description:
		'Vul uw gegevens in om een account bij de praktijk aan te maken',
};
