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
	const {Appointment, errors} = usePage<Props>().props;

	const [editMode, setEditMode] = useState<boolean>(false);
	// const [values, setValues] = useState([
	// 	{ id: 'id', value: Appointment.appointment_id },
	// 	{ id: 'note', value: Appointment.note },
	// 	{ id: 'date', value: Appointment.date.toString() },
	// 	{ id: 'Starttime', value: Appointment.starttime.toString() },
	// 	{ id: 'Endtime', value: Appointment.endtime.toString() },
	// ]);
	const [note, setNote] = useState<string>(Appointment.note ?? "");
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

	const handleDeleteAppointment = () => {
		if(!window.confirm("Weet je zeker dat je de afspraak wilt verwijderen?"))
		{
			return;
		}
		// if(!window.confirm("Ok maar weet je het zeker?"))
		// {
		// 	return;
		// }
		// if(!window.confirm("Weet je het ECHT heel zeker?"))
		// {
		// 	return;
		// }
		// if(!window.confirm("OK"))
		// {
		// 	return;
		// }
		router.post('/appointments/delete', {
			id: Appointment.appointment_id,
		}, {
			onSuccess: () => {
				console.log("Succesvol afspraak verwijderd.")
			}, onError: () => {
				console.error("Afspraak niet verwijderd.")
			}
		})
	}

	return (
		<>
			<Head title="Zie afspraak in"/>

			<form className="flex flex-col place-items-center border-2 border-indigo-500 h-full py-4 w-full gap-y-8">
				{/* Foutmeldingen van de server, anders lijkt het alsof er niets gebeurt */}
				{Object.values(errors).map((message) => (
					<span key={message} className="text-red-600">{message}</span>
				))}
				<div className="flex gap-x-8">
					<span>Klant: {Appointment.customer.firstname} {Appointment.customer.lastname}</span>
					<span>Tandarts: {Appointment.dentist.firstname} {Appointment.dentist.lastname}</span>
					<span>Assistent: {Appointment.assistant ? `${Appointment.assistant.firstname} ${Appointment.assistant.lastname}` : "Geen assistent"}</span>
				</div>
				<div>Soort behandeling: {Appointment.treatments[0]?.name ?? "Onbekende behandeling"}</div>
				<label htmlFor="note">Notitie: </label>
				<textarea className="w-4/5 flex justify-center text-center h-full" readOnly={!editMode}
				          defaultValue={note}
				          onChange={(e) => setNote(e.target.value)}
				>
				</textarea>
				<div className="flex flex-col gap-y-8">
					<label htmlFor="date">Datum: </label>
					<input type="date" defaultValue={date} readOnly={!editMode}
					       onChange={(e) => setDate(e.target.value)}/>

					<label htmlFor="starttime">Begintijd: </label>
					<input type="time" defaultValue={starttime} readOnly={!editMode}
					       min="09:00" max="17:00" step={1800}
					       onChange={(e) => setStarttime(e.target.value)}/>

					<label htmlFor="endtime">Eindtijd: </label>
					<input type="time" defaultValue={Appointment.endtime.toString()} readOnly={true}/>
				</div>
				<div className="flex gap-x-8">
					<button type="button" onClick={() => setEditMode(!editMode)}
					        className="btn btn-primary">
						{editMode ? "Stop met bewerken" : "Bewerk afspraak"}
					</button>
					<button type={"submit"}
					        className="btn btn-primary"
					        disabled={!editMode}
					        onClick={(e) => handleUpdateAppointment(e)}>
						Sla bewerkingen op
					</button>
					<button
						type="button"
						className="btn btn-error "
						onClick={handleDeleteAppointment}>
						Verwijderen
					</button>
				</div>
			</form>
		</>
	)
}
