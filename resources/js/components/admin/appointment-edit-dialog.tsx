import { Form } from '@inertiajs/react';
import { useState } from 'react';
import type { ComponentProps, ReactNode } from 'react';
import InputError from '@/components/input-error';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';
import type { CalendarAppointment, CalendarOptions } from '@/types/admin';
import { statusLabel } from './appointment-status';

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

type Props = {
	appointment: CalendarAppointment;
	options: CalendarOptions;
	children: ReactNode;
};

export default function AppointmentEditDialog({
	appointment,
	options,
	children,
}: Props) {
	const [open, setOpen] = useState(false);

	return (
		<Dialog open={open} onOpenChange={setOpen}>
			<DialogTrigger asChild>{children}</DialogTrigger>
			<DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
				<DialogTitle>Afspraak bewerken</DialogTitle>
				<DialogDescription>
					{appointment.customer?.name ?? 'Onbekende klant'}
					{appointment.treatments.length > 0 &&
						` · ${appointment.treatments.map((treatment) => treatment.name).join(', ')}`}
				</DialogDescription>

				<Form
					{...admin.calendar.update.form(appointment.appointment_id)}
					options={{ preserveScroll: true, preserveState: true }}
					onSuccess={() => setOpen(false)}
					className="space-y-4"
				>
					{({ processing, errors }) => (
						<>
							<div className="grid gap-4 sm:grid-cols-3">
								<div className="grid gap-2">
									<Label htmlFor="date">Datum</Label>
									<Input
										id="date"
										type="date"
										name="date"
										defaultValue={appointment.date.slice(
											0,
											10,
										)}
										required
									/>
									<InputError message={errors.date} />
								</div>
								<div className="grid gap-2">
									<Label htmlFor="starttime">Begintijd</Label>
									<Input
										id="starttime"
										type="time"
										name="starttime"
										defaultValue={appointment.starttime.slice(
											0,
											5,
										)}
										required
									/>
								</div>
								<div className="grid gap-2">
									<Label htmlFor="endtime">Eindtijd</Label>
									<Input
										id="endtime"
										type="time"
										name="endtime"
										defaultValue={appointment.endtime.slice(
											0,
											5,
										)}
										required
									/>
								</div>
							</div>
							<InputError
								message={errors.starttime ?? errors.endtime}
							/>

							<div className="grid gap-4 sm:grid-cols-2">
								<div className="grid gap-2">
									<Label htmlFor="dentist_id">Tandarts</Label>
									<NativeSelect
										id="dentist_id"
										name="dentist_id"
										defaultValue={appointment.dentist_id}
										required
									>
										{options.dentists.map((dentist) => (
											<option
												key={dentist.id}
												value={dentist.id}
											>
												{dentist.name}
											</option>
										))}
									</NativeSelect>
									<InputError message={errors.dentist_id} />
								</div>

								<div className="grid gap-2">
									<Label htmlFor="assistant_id">
										Assistent
									</Label>
									<NativeSelect
										id="assistant_id"
										name="assistant_id"
										defaultValue={
											appointment.assistant_id ?? ''
										}
									>
										<option value="">Geen assistent</option>
										{options.assistants.map((employee) => (
											<option
												key={employee.id}
												value={employee.id}
											>
												{employee.name}
											</option>
										))}
									</NativeSelect>
									<InputError message={errors.assistant_id} />
								</div>

								<div className="grid gap-2">
									<Label htmlFor="room_id">Kamer</Label>
									<NativeSelect
										id="room_id"
										name="room_id"
										defaultValue={appointment.room_id ?? ''}
									>
										<option value="">Geen kamer</option>
										{options.rooms.map((room) => (
											<option
												key={room.room_id}
												value={room.room_id}
											>
												{room.name}
											</option>
										))}
									</NativeSelect>
									<InputError message={errors.room_id} />
								</div>

								<div className="grid gap-2">
									<Label htmlFor="status">Status</Label>
									<NativeSelect
										id="status"
										name="status"
										defaultValue={
											options.statuses.includes(
												appointment.status,
											)
												? appointment.status
												: 'scheduled'
										}
									>
										{options.statuses.map((status) => (
											<option key={status} value={status}>
												{statusLabel(status)}
											</option>
										))}
									</NativeSelect>
									<InputError message={errors.status} />
								</div>
							</div>

							<div className="grid gap-2">
								<Label htmlFor="note">Notitie</Label>
								<textarea
									id="note"
									name="note"
									defaultValue={appointment.note ?? ''}
									rows={3}
									className="border-input dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
								/>
								<InputError message={errors.note} />
							</div>

							<DialogFooter className="gap-2">
								<DialogClose asChild>
									<Button variant="secondary" type="button">
										Annuleren
									</Button>
								</DialogClose>
								<Button type="submit" disabled={processing}>
									{processing && <Spinner />}
									Opslaan
								</Button>
							</DialogFooter>
						</>
					)}
				</Form>
			</DialogContent>
		</Dialog>
	);
}
