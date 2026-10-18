import { Form, Head, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { LogoutButton } from '@/components/logout-button';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { edit } from '@/routes/profile';
import type { Auth } from '@/types';
import { send } from '@/routes/verification';

type PageProps = {
	auth: Auth;
};

type Address = {
	street: string;
	house_number: string;
	postcode: string;
	city: string;
};

export default function Profile({
	mustVerifyEmail,
	status,
	address,
	dentists,
}: {
	mustVerifyEmail: boolean;
	status?: string;
	address: Address | null;
	dentists: { id: number; name: string }[] | null;
}) {
	const { auth } = usePage<PageProps>().props;

	return (
		<>
			<Head title="Mijn profiel" />

			<h1 className="sr-only">Mijn profiel</h1>

			<Form
				{...ProfileController.update.form()}
				options={{
					preserveScroll: true,
				}}
				className="space-y-10"
			>
				{({ processing, errors }) => (
					<>
						<section className="space-y-6">
							<Heading
								variant="small"
								title="Persoonlijke gegevens"
								description="Uw naam, e-mailadres en contactgegevens"
							/>

							<div className="grid gap-4 sm:grid-cols-2">
								<div className="grid gap-2">
									<Label htmlFor="firstname">Voornaam</Label>
									<Input
										id="firstname"
										defaultValue={auth.user.firstname}
										name="firstname"
										required
										autoComplete="given-name"
									/>
									<InputError message={errors.firstname} />
								</div>

								<div className="grid gap-2">
									<Label htmlFor="lastname">Achternaam</Label>
									<Input
										id="lastname"
										defaultValue={auth.user.lastname}
										name="lastname"
										required
										autoComplete="family-name"
									/>
									<InputError message={errors.lastname} />
								</div>
							</div>

							<div className="grid gap-2">
								<Label htmlFor="email">E-mailadres</Label>
								<Input
									id="email"
									type="email"
									defaultValue={auth.user.email}
									name="email"
									required
									autoComplete="username"
								/>
								<InputError message={errors.email} />
							</div>

							{mustVerifyEmail &&
								auth.user.email_verified_at === null && (
									<div>
										<p className="text-muted-foreground -mt-4 text-sm">
											Uw e-mailadres is nog niet
											bevestigd.{' '}
											<Link
												href={send()}
												as="button"
												className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
											>
												Klik hier om de bevestigingsmail
												opnieuw te versturen.
											</Link>
										</p>

										{status ===
											'verification-link-sent' && (
											<div className="mt-2 text-sm font-medium text-green-600">
												Er is een nieuwe
												bevestigingslink naar uw
												e-mailadres gestuurd.
											</div>
										)}
									</div>
								)}

							<div className="grid gap-4 sm:grid-cols-2">
								<div className="grid gap-2">
									<Label htmlFor="phonenumber">
										Telefoonnummer
									</Label>
									<Input
										id="phonenumber"
										type="tel"
										defaultValue={
											auth.user.phonenumber ?? ''
										}
										name="phonenumber"
										autoComplete="tel"
										placeholder="06 12345678"
									/>
									<InputError message={errors.phonenumber} />
								</div>

								<div className="grid gap-2">
									<Label htmlFor="birthday">
										Geboortedatum
									</Label>
									<Input
										id="birthday"
										type="date"
										defaultValue={
											auth.user.birthday?.slice(0, 10) ??
											''
										}
										name="birthday"
										autoComplete="bday"
									/>
									<InputError message={errors.birthday} />
								</div>
							</div>
						</section>

						{dentists && (
							<section className="space-y-6">
								<Heading
									variant="small"
									title="Mijn tandarts"
									description="De tandarts die u vast behandelt. Bij een nieuwe afspraak staat deze al voor u klaar."
								/>

								<div className="grid gap-2">
									<Label htmlFor="employee_id">
										Tandarts
									</Label>
									<NativeSelect
										id="employee_id"
										name="employee_id"
										defaultValue={
											auth.user.employee_id ?? ''
										}
									>
										<option value="">
											Nog geen voorkeur
										</option>
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
							</section>
						)}

						<section className="space-y-6">
							<Heading
								variant="small"
								title="Adres"
								description="Waar we u per post kunnen bereiken"
							/>

							<div className="grid gap-4 sm:grid-cols-[1fr_8rem]">
								<div className="grid gap-2">
									<Label htmlFor="street">Straat</Label>
									<Input
										id="street"
										defaultValue={address?.street ?? ''}
										name="street"
										autoComplete="address-line1"
									/>
									<InputError message={errors.street} />
								</div>

								<div className="grid gap-2">
									<Label htmlFor="house_number">
										Huisnummer
									</Label>
									<Input
										id="house_number"
										defaultValue={
											address?.house_number ?? ''
										}
										name="house_number"
									/>
									<InputError message={errors.house_number} />
								</div>
							</div>

							<div className="grid gap-4 sm:grid-cols-[8rem_1fr]">
								<div className="grid gap-2">
									<Label htmlFor="postcode">Postcode</Label>
									<Input
										id="postcode"
										defaultValue={address?.postcode ?? ''}
										name="postcode"
										autoComplete="postal-code"
										placeholder="1234 AB"
									/>
									<InputError message={errors.postcode} />
								</div>

								<div className="grid gap-2">
									<Label htmlFor="city">Plaats</Label>
									<Input
										id="city"
										defaultValue={address?.city ?? ''}
										name="city"
										autoComplete="address-level2"
									/>
									<InputError message={errors.city} />
								</div>
							</div>
						</section>

						<div className="flex items-center gap-4">
							<Button
								disabled={processing}
								data-test="update-profile-button"
							>
								Opslaan
							</Button>
						</div>
					</>
				)}
			</Form>

			<section className="space-y-4">
				<Heading
					variant="small"
					title="Uitloggen"
					description="Klaar? Log uit om uw gegevens af te schermen, zeker op een gedeelde computer."
				/>
				<LogoutButton variant="secondary" size="default" />
			</section>

			<DeleteUser />
		</>
	);
}

Profile.layout = {
	breadcrumbs: [
		{
			title: 'Mijn profiel',
			href: edit(),
		},
	],
};
