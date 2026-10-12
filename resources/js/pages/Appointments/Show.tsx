import {Head, router, usePage} from '@inertiajs/react'
import {PageProps} from "@inertiajs/core";
import {Appointment, Treatment, User} from "@/types/Interfaces";
import * as Dropdown from "@radix-ui/react-dropdown-menu";
import {ChevronDownIcon} from "lucide-react";
import React, { useState } from "react";

interface Props extends PageProps {
	Appointment: Appointment
}

export default function Show({}) {
	const {Appointment} = usePage<Props>().props;

	const [editMode, setEditMode] = useState<boolean>(false);
	// const [values, setValues] = useState([
	// 	{ id: 'id', value: Appointment.appointment_id },
	// 	{ id: 'note', value: Appointment.note },
	// 	{ id: 'date', value: Appointment.date.toString() },
	// 	{ id: 'Starttime', value: Appointment.starttime.toString() },
	// 	{ id: 'Endtime', value: Appointment.endtime.toString() },
	// ]);
	const [note, setNote] = useState<string>(Appointment.note);
	const [date, setDate] = useState(Appointment.date.toString());
	const [starttime, setStarttime] = useState(Appointment.starttime.toString());

	// const handleChange = (index, newValue) => {
	// 	setValues(prevState =>
	// 		prevState.map((state, i) =>
	// 		i === index ? {...state, value: newValue } : state
	// 		)
	// 	);
	// }

	const handleUpdateAppointment = (e: React.MouseEvent) => {
		e.preventDefault();
		router.post('/appointments/update', {
			Appointment_Id: Appointment.appointment_id,
			Note: note,
			Date: date,
			Starttime: starttime,
		})
	}

	return (
		<>
			<Head title="Zie afspraak in"/>

			<form className="flex flex-col place-items-center border-2 border-indigo-500 h-full py-4 w-full gap-y-8">
				<div>Soort behandeling: {Appointment.treatments[0].name}</div>
				<label htmlFor="note">Notitie: </label>
				<textarea className="w-4/5 flex justify-center text-center h-full" readOnly={!editMode}
						  defaultValue={note}
						  onChange={(e) => setNote(e.target.value) }
				>
				</textarea>
				<div>
					<label htmlFor="date">Datum: </label>
					<input type="date" defaultValue={date} readOnly={!editMode}
					onChange={(e) => setDate(e.target.value)}/>

					<label htmlFor="starttime">Begintijd: </label>
					<input type="time" defaultValue={starttime} readOnly={!editMode}
					onChange={(e) => setStarttime(e.target.value)}/>

					<label htmlFor="endtime">Eindtijd: </label>
					<input type="time" defaultValue={Appointment.endtime.toString()} readOnly={true}/>
				</div>
				<button type="button" onClick={() => setEditMode(!editMode)}
				className="bg-indigo-500 p-8 hover:font-bold">
					{editMode ? "Stop met bewerken" : "Bewerk afspraak"}
				</button>
				<button type={"submit"}
				        className="bg-indigo-500 p-8 hover:font-bold"
						onClick={(e) => handleUpdateAppointment(e)}>
					Sla bewerkingen op
				</button>
			</form>
		</>
	)
}
