import {Head, router} from '@inertiajs/react'
import React, { useState} from "react";

export default function Create() {

	const [name, setName] = useState("Naam");
	const [description, setDescription] = useState("Desc");
	const [price, setPrice] = useState(67);
	const [duration, setDuration] = useState(0);

	const handleCreateTreatment = () => {
		router.post('/treatments/create', {
			Name: name,
			Description: description,
			Price: price,
			DurationInMinutes: duration,
		}, {
			onSuccess: () => {
				console.log("Aangemaakt");
			},
			onError: () => {
				console.error("Oh shit not good");
			}
		});
	}

	return(
		<>
			<Head title={"Behandeling aanmaken"} />
			<form action="" className="flex flex-col">
				<label htmlFor="Name">Naam van de behandeling:</label>
				<input type="text" name="Name" value={name}
					   onChange={e => setName(e.currentTarget.value)} />

				<label htmlFor="Description">Beschrijving van de behandeling:</label>
				<input type="text" name="Description" value={description}
					   onChange={e => setDescription(e.currentTarget.value)} />

				<label htmlFor="Price">Prijs van de behandeling:</label>
				<input type="number" name="Price" value={price}
					onChange={e => setPrice(Number(e.currentTarget.value))}/>

				<label htmlFor="Duration">Lengte van de behandeling: (in minuten)</label>
				<input type="number" name="Duration" value={duration}
					onChange={e => setDuration(Number(e.currentTarget.value))}/>

				<div onClick={() => handleCreateTreatment()}
				className={"bg-indigo-500 hover:font-bold"}>
					Maak behandeling aan
				</div>
			</form>
		</>
	)
}
