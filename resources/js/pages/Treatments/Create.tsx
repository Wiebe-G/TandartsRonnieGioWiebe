import { Head, router, usePage } from '@inertiajs/react';
import React, { useState } from 'react';

export default function Create() {
	const { errors } = usePage().props;

	const [name, setName] = useState('');
	const [description, setDescription] = useState('');
	const [price, setPrice] = useState(0);
	const [duration, setDuration] = useState(30);
	const [isSubmitting, setIsSubmitting] = useState(false);

	const handleCreateTreatment = (e: React.FormEvent) => {
		// Anders verstuurt de browser het formulier zelf en herlaadt de pagina
		e.preventDefault();
		router.post(
			'/treatments/create',
			{
				Name: name,
				Description: description,
				Price: price,
				DurationInMinutes: duration,
			},
			{
				onStart: () => setIsSubmitting(true),
				onFinish: () => setIsSubmitting(false),
				onSuccess: () => {
					console.log('Aangemaakt');
				},
				onError: () => {
					console.error('Oh shit not good');
				},
			},
		);
	};

	return (
		<>
			<Head title={'Behandeling aanmaken'} />
			<form
				action=""
				className="flex flex-col"
				onSubmit={handleCreateTreatment}
			>
				<label htmlFor="Name">Naam van de behandeling:</label>
				<input
					type="text"
					name="Name"
					value={name}
					onChange={(e) => setName(e.currentTarget.value)}
				/>
				{errors.Name && (
					<span className="text-red-600">{errors.Name}</span>
				)}

				<label htmlFor="Description">
					Beschrijving van de behandeling:
				</label>
				<input
					type="text"
					name="Description"
					value={description}
					onChange={(e) => setDescription(e.currentTarget.value)}
				/>
				{errors.Description && (
					<span className="text-red-600">{errors.Description}</span>
				)}

				<label htmlFor="Price">Prijs van de behandeling:</label>
				<input
					type="number"
					name="Price"
					value={price}
					min={0}
					step="0.01"
					onChange={(e) => setPrice(Number(e.currentTarget.value))}
				/>
				{errors.Price && (
					<span className="text-red-600">{errors.Price}</span>
				)}

				<label htmlFor="Duration">
					Lengte van de behandeling: (in minuten)
				</label>
				<input
					type="number"
					name="Duration"
					value={duration}
					min={5}
					onChange={(e) => setDuration(Number(e.currentTarget.value))}
				/>
				{errors.DurationInMinutes && (
					<span className="text-red-600">
						{errors.DurationInMinutes}
					</span>
				)}

				<button
					type="submit"
					disabled={isSubmitting}
					className={'bg-indigo-500 hover:font-bold'}
				>
					Maak behandeling aan
				</button>
			</form>
		</>
	);
}
