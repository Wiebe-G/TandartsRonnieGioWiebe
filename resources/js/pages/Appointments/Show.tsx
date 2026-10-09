import {Head, router, usePage} from '@inertiajs/react'
import {PageProps} from "@inertiajs/core";
import {Appointment, Treatment, User} from "@/types/Interfaces";
import * as Dropdown from "@radix-ui/react-dropdown-menu";
import {ChevronDownIcon} from "lucide-react";
import React, {useState} from "react";

interface Props extends PageProps {
	Appointment: Appointment
}

export default function Show({}) {
	const {Appointment} = usePage<Props>().props;

	const [editMode, setEditMode] = useState<boolean>(false);

	const handleUpdateAppointment = () => {
		router.post('/appointments/update', {
			Appointment_Id: Appointment.appointment_id
		})
	}
	return (
		<>
			<Head title="Zie afspraak in"/>

			<form className="flex flex-col place-items-center border-2 border-indigo-500 h-full py-4 w-full gap-y-8">
				<div>Soort behandeling: {Appointment.treatments[0].name}</div>
				<label htmlFor="note">Notitie: </label>
				<textarea className="w-4/5 flex justify-center text-center h-full" readOnly={!editMode}
						  defaultValue={Appointment.note}
				>
				</textarea>
				<div>
					<label htmlFor="date">Datum: </label>
					<input type="date" defaultValue={Appointment.date.toString()} readOnly={!editMode}/>
					<label htmlFor="starttime">Begintijd: </label>
					<input type="time" defaultValue={Appointment.starttime.toString()} readOnly={!editMode}/>
					<label htmlFor="endtime">Eindtijd: </label>
					<input type="time" defaultValue={Appointment.endtime.toString()} readOnly={!editMode}/>
				</div>
				<button type="button" onClick={() => setEditMode(!editMode)}
				className="bg-indigo-500 p-8 hover:font-bold">
					{editMode ? "Stop met bewerken" : "Bewerk afspraak"}
				</button>
				<button type={"submit"}
				        className="bg-indigo-500 p-8 hover:font-bold"
						onClick={handleUpdateAppointment}>
					Sla bewerkingen op
				</button>
			</form>
		</>
	)
}
