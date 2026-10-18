import { Form } from '@inertiajs/react';
import type { ComponentProps } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import type { Dentist, ManagedUser, Role } from '@/types/admin';
import { roleLabel } from './role-label';

type FormAttributes = { action: string; method: 'get' | 'post' };

type Props = {
	form: FormAttributes;
	submitLabel: string;
	user?: ManagedUser;
	/** Shown for employees: which role they get. */
	roles?: Role[];
	/** Shown for customers: which dentist they belong to. */
	dentists?: Dentist[];
};

function NativeSelect({ className, ...props }: ComponentProps<'select'>) {
	return (
		<select
			className={cn(
				'border-input dark:bg-input/30 flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-base shadow-xs outline-none md:text-sm',
				'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
				className,
			)}
			{...props}
		/>
	);
}

export default function UserForm({
	form,
	submitLabel,
	user,
	roles,
	dentists,
}: Props) {
	const isEdit = user !== undefined;

	return (
		<Form
			{...form}
			options={{ preserveScroll: true }}
			className="max-w-2xl space-y-6"
		>
			{({ processing, errors }) => (
				<>
					<div className="grid gap-6 sm:grid-cols-2">
						<div className="grid gap-2">
							<Label htmlFor="firstname">Voornaam</Label>
							<Input
								id="firstname"
								name="firstname"
								defaultValue={user?.firstname}
								required
								autoComplete="off"
							/>
							<InputError message={errors.firstname} />
						</div>

						<div className="grid gap-2">
							<Label htmlFor="lastname">Achternaam</Label>
							<Input
								id="lastname"
								name="lastname"
								defaultValue={user?.lastname}
								required
								autoComplete="off"
							/>
							<InputError message={errors.lastname} />
						</div>

						<div className="grid gap-2">
							<Label htmlFor="email">E-mailadres</Label>
							<Input
								id="email"
								type="email"
								name="email"
								defaultValue={user?.email}
								required
								autoComplete="off"
							/>
							<InputError message={errors.email} />
						</div>

						<div className="grid gap-2">
							<Label htmlFor="phonenumber">Telefoonnummer</Label>
							<Input
								id="phonenumber"
								type="tel"
								name="phonenumber"
								defaultValue={user?.phonenumber ?? ''}
							/>
							<InputError message={errors.phonenumber} />
						</div>

						<div className="grid gap-2">
							<Label htmlFor="birthday">Geboortedatum</Label>
							<Input
								id="birthday"
								type="date"
								name="birthday"
								defaultValue={
									user?.birthday?.slice(0, 10) ?? ''
								}
							/>
							<InputError message={errors.birthday} />
						</div>

						<div className="grid gap-2">
							<Label htmlFor="status">Status</Label>
							<NativeSelect
								id="status"
								name="status"
								defaultValue={user?.status ?? 'active'}
							>
								<option value="active">Actief</option>
								<option value="inactive">Inactief</option>
							</NativeSelect>
							<InputError message={errors.status} />
						</div>

						{roles && (
							<div className="grid gap-2">
								<Label htmlFor="role_id">Rol</Label>
								<NativeSelect
									id="role_id"
									name="role_id"
									defaultValue={user?.role_id ?? ''}
									required
								>
									<option value="" disabled>
										Kies een rol
									</option>
									{roles.map((role) => (
										<option
											key={role.role_id}
											value={role.role_id}
										>
											{roleLabel(role.name)}
										</option>
									))}
								</NativeSelect>
								<InputError message={errors.role_id} />
							</div>
						)}

						{dentists && (
							<div className="grid gap-2">
								<Label htmlFor="employee_id">Tandarts</Label>
								<NativeSelect
									id="employee_id"
									name="employee_id"
									defaultValue={user?.employee_id ?? ''}
									required
								>
									<option value="" disabled>
										Kies een tandarts
									</option>
									{dentists.map((dentist) => (
										<option
											key={dentist.id}
											value={dentist.id}
										>
											{dentist.firstname}{' '}
											{dentist.lastname}
										</option>
									))}
								</NativeSelect>
								<InputError message={errors.employee_id} />
							</div>
						)}

						<div className="grid gap-2 sm:col-span-2">
							<Label htmlFor="password">Wachtwoord</Label>
							<PasswordInput
								id="password"
								name="password"
								required={!isEdit}
								autoComplete="new-password"
								placeholder={
									isEdit
										? 'Leeg laten om het huidige wachtwoord te houden'
										: undefined
								}
							/>
							<InputError message={errors.password} />
						</div>
					</div>

					<Button disabled={processing}>
						{processing && <Spinner />}
						{submitLabel}
					</Button>
				</>
			)}
		</Form>
	);
}
