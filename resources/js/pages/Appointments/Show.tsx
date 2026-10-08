import {Head, usePage} from '@inertiajs/react'
import {PageProps} from "@inertiajs/core";
import {Appointment, Treatment, User} from "@/types/Interfaces";
import * as Dropdown from "@radix-ui/react-dropdown-menu";
import {ChevronDownIcon} from "lucide-react";
import React from "react";

interface Props extends PageProps {
	Appointment: Appointment
}

export default function Show({}) {
	const {Appointment} = usePage<Props>().props;


	console.log(Appointment);
	return (
		<>
			<Head title="Zie afspraak in"/>

			<form className="flex flex-col place-items-center border-2 border-indigo-500 h-full py-4 w-full gap-y-8">
				<div>Soort behandeling: {Appointment.treatments[0].name}</div>
				<div>Datum: {Appointment.date.toString()} van {Appointment.starttime.toString()} tot {Appointment.endtime.toString()}</div>
				<div>Notitie: {Appointment.note}</div>
			</form>
		</>
	)
}
