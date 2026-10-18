import {Head, router, usePage} from '@inertiajs/react'
import {PageProps} from "@inertiajs/core";
import React, { useState} from "react";
import {Treatment} from "@/types/Interfaces";

interface Props extends PageProps {
	Treatment: Treatment & { active: boolean | number };
}

// Zelfde formulier als Create, maar dan voor een bestaande behandeling
export default function Edit() {
	const {Treatment, errors} = usePage<Props>().props;

	const [name, setName] = useState(Treatment.name);
	const [description, setDescription] = useState(Treatment.description ?? "");
	const [price, setPrice] = useState(Number(Treatment.price));
	const [duration, setDuration] = useState(Number(Treatment.duration));
	const [active, setActive] = useState(Boolean(Treatment.active));
	const [isSubmitting, setIsSubmitting] = useState(false);

	const handleUpdateTreatment = (e: React.FormEvent) => {
		e.preventDefault();
		router.put(`/treatments/${Treatment.treatment_id}`, {
			Name: name,
			Description: description,
			Price: price,
			DurationInMinutes: duration,
			Active: active,
		}, {
			onStart: () => setIsSubmitting(true),
			onFinish: () => setIsSubmitting(false),
		});
	}

	return(
		<>
			<Head title={"Behandeling bewerken"} />
			<form action="" className="flex flex-col" onSubmit={handleUpdateTreatment}>
				<label htmlFor="Name">Naam van de behandeling:</label>
				<input type="text" name="Name" value={name}
					   onChange={e => setName(e.currentTarget.value)} />
				{errors.Name && <span className="text-red-600">{errors.Name}</span>}

				<label htmlFor="Description">Beschrijving van de behandeling:</label>
				<input type="text" name="Description" value={description}
					   onChange={e => setDescription(e.currentTarget.value)} />
				{errors.Description && <span className="text-red-600">{errors.Description}</span>}

				<label htmlFor="Price">Prijs van de behandeling:</label>
				<input type="number" name="Price" value={price} min={0} step="0.01"
					onChange={e => setPrice(Number(e.currentTarget.value))}/>
				{errors.Price && <span className="text-red-600">{errors.Price}</span>}

				<label htmlFor="Duration">Lengte van de behandeling: (in minuten)</label>
				<input type="number" name="Duration" value={duration} min={5}
					onChange={e => setDuration(Number(e.currentTarget.value))}/>
				{errors.DurationInMinutes && <span className="text-red-600">{errors.DurationInMinutes}</span>}

				<label htmlFor="Active">
					<input type="checkbox" name="Active" checked={active}
						onChange={e => setActive(e.currentTarget.checked)}/>
					{" "}Actief (te kiezen bij een nieuwe afspraak)
				</label>

				<button type="submit"
					 disabled={isSubmitting}
				className={"bg-indigo-500 hover:font-bold"}>
					Sla behandeling op
				</button>
			</form>
		</>
	)
}
