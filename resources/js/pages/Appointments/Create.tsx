import {Head, router, usePage} from '@inertiajs/react'
import {PageProps} from "@inertiajs/core";
import {Treatment, User} from "@/types/Interfaces";
import * as Dropdown from '@radix-ui/react-dropdown-menu';
import {ChevronDownIcon} from "lucide-react";
import React, {useState} from "react";

interface Props extends PageProps {
	// Customers blijft een object hoe hard ik ook Laravel probeer te vertellen dat het een array moet zijn
	Customers: User[];
	Treatments: Treatment[];
	Users: User[];
}

export default function Create({}) {
	const { Customers, Treatments, Users, errors } = usePage<Props>().props;

	const [selectedUser, setSelectedUser] = useState<User>();
	const [selectedTreatmentId, setSelectedTreatmentId] = useState<number>();
	const [statefulDate, setStatefulDate] = useState<Date>();
	const [note, setNote] = useState("");
	const [selectedDentist, setSelectedDentist] = useState(0);
	const [selectedAssistant, setSelectedAssistant] = useState(0);

	let Dentists: User[] = [];
	let Assistants: User[] = [];

	// Op rolnaam filteren, de role_id's kunnen per database verschillen
	Users.map((user) => {
		if(user.role?.name == 'dentist')
		{
			Dentists = [... Dentists, user]
		}
		if(user.role?.name == 'assistant')
		{
			Assistants = [... Assistants, user]
		}
	})

	const handleCategoryChange = (id: number): void => {
		setSelectedUser(Customers.find((cat) => cat.id === id));
	}

	const handleUpdateDate = (e: React.ChangeEvent<HTMLInputElement>): void => {
		setStatefulDate(new Date(e.currentTarget.value));
	}

	const handleCreateAppointment = async() => {
		if(!selectedUser) {
			alert("Geen klant geselecteerd");
			return;
		}
		if(selectedUser && selectedUser.id == 0) {
			alert("Geen klant geselecteerd");
			return;
		}
		if(!selectedDentist) {
			alert("Geen tandarts geselecteerd");
			return;
		}
		if(!selectedTreatmentId) {
			alert("Geen behandeling geselecteerd");
			return;
		}
		if(!statefulDate || isNaN(statefulDate.getTime())) {
			alert("Geen datum gekozen");
			return;
		}
		// check voor of datum in het verleden is
		if(statefulDate < new Date()) {
			alert("De datum ligt in het verleden");
			return;
		}
		router.post(
			`/appointments/create`,
			{
				Customer_id: selectedUser.id,
				Dentist_id: selectedDentist,
				Assistant_id: selectedAssistant || null,
				Date: statefulDate,
				Type: selectedTreatmentId,
				Note: note
			}
		)
	}

	const returnSelectedTreatment = (id: number) => {
		return Treatments.find((tr) => tr.treatment_id === id);
	}

	const returnSelectedDentist = (id: number) => {
		return Dentists.find((tr) => tr.id === id);
	}

	const returnSelectedAssistant = (id: number) => {
		return Assistants.find((tr) => tr.id === id);
	}

	return (
		<>
			<Head title="Afspraak maken"/>
			{/*	Dropdown met alle klanten, datum en tijd, soort behandeling*/}
			<form action="" className="flex flex-col place-items-center border-2 border-indigo-500 h-full py-4 w-full gap-y-8">
				{/* Foutmeldingen van de server, anders lijkt het alsof er niets gebeurt */}
				{Object.values(errors).map((message) => (
					<span key={message} className="text-red-600">{message}</span>
				))}
				<div className = "">
					<Dropdown.Root>
						<Dropdown.Trigger asChild>
							<button type={'button'}
							        className="flex items-center rounded bg-blue-500 px-4 py-2 text-white" value={0}>
								{selectedUser ?  `${selectedUser.firstname} ${selectedUser.lastname}` : 'Selecteer een klant'}
								<ChevronDownIcon className="ml-2 size-4"/>
							</button>
						</Dropdown.Trigger>

						<Dropdown.Portal>
							<Dropdown.Content
								className="flex flex-col items-center z-50 min-w-55 rounded-md border bg-white p-1 shadow-lg dark:bg-slate-800">
								<Dropdown.Item className={'DropdownDarkButtonCenteredItem'}
									onSelect={() => handleCategoryChange(0)}
								>
									Geen Klant
								</Dropdown.Item>
								{Customers.map((cat: User) => (
									<Dropdown.Item
										className="DropdownDarkButtonCenteredItem"
										key={cat.id}
										onSelect={() => handleCategoryChange(cat.id)}
									>
										{`${cat.firstname} ${cat.lastname}`}
									</Dropdown.Item>
								))}
								<Dropdown.Separator className="my-1 h-px bg-gray-200"/>
							</Dropdown.Content>
						</Dropdown.Portal>
					</Dropdown.Root>
				</div>
				<div>
					<Dropdown.Root>
						<Dropdown.Trigger asChild>
							<button type={'button'}
									className="flex items-center rounded bg-blue-500 px-4 py-2 text-white"
									value={0}>
								{selectedDentist ? `${returnSelectedDentist(selectedDentist)?.firstname}` : "Geen tandarts"}
								<ChevronDownIcon className="ml-2 size-4"/>
							</button>
						</Dropdown.Trigger>

						<Dropdown.Portal>
							<Dropdown.Content
								className="flex flex-col items-center z-50 min-w-55 rounded-md border bg-white p-1 shadow-lg dark:bg-slate-800">
								<Dropdown.Item className={'DropdownDarkButtonCenteredItem'}
								               onSelect={() => setSelectedDentist(0)}
								>
									<span>Geen tandarts</span>
								</Dropdown.Item>
								{Dentists.map((d: User) => (
									<Dropdown.Item
										className="DropdownDarkButtonCenteredItem"
										key={d.id}
										onSelect={() => setSelectedDentist(d.id)}
									>
										{`${d.firstname} ${d.lastname}`}
									</Dropdown.Item>
								))}
								<Dropdown.Separator className="my-1 h-px bg-gray-200"/>
							</Dropdown.Content>
						</Dropdown.Portal>
					</Dropdown.Root>
				</div>
				<div id="choose-assistand">
					<Dropdown.Root>
						<Dropdown.Trigger asChild>
							<button type={'button'}
							        className="flex items-center rounded bg-blue-500 px-4 py-2 text-white"
							        value={0}>
								{selectedAssistant ?
									`${returnSelectedAssistant(selectedAssistant)?.firstname}`
									: "Geen assistent"}
								<ChevronDownIcon className="ml-2 size-4"/>
							</button>
						</Dropdown.Trigger>

						<Dropdown.Portal>
							<Dropdown.Content
								className="flex flex-col items-center z-50 min-w-55 rounded-md border bg-white p-1 shadow-lg dark:bg-slate-800">
								<Dropdown.Item className={'DropdownDarkButtonCenteredItem'}
								               onSelect={() => setSelectedAssistant(0)}
								>
									<span>Geen assistent</span>
								</Dropdown.Item>
								{Assistants.map((as: User) => (
									<Dropdown.Item
										className="DropdownDarkButtonCenteredItem"
										key={as.id}
										onSelect={() => setSelectedAssistant(as.id)}
									>
										{`${as.firstname} ${as.lastname}`}
									</Dropdown.Item>
								))}
								<Dropdown.Separator className="my-1 h-px bg-gray-200"/>
							</Dropdown.Content>
						</Dropdown.Portal>
					</Dropdown.Root>
				</div>
				<div id="choose-date" className = "text-center">
					<label htmlFor="date">Kies een datum: </label>
					<br/>
					<input type="datetime-local" id="date" name="date" step={1800} onChange={handleUpdateDate} />
					<br/>
					<span>Ma t/m vr, 9:00 - 17:00, op het hele of halve uur</span>
				</div>
				<div id="select-treatment" className = "">
					<Dropdown.Root>
						<Dropdown.Trigger asChild>
							<button type={'button'}
							        className="flex items-center rounded bg-blue-500 px-4 py-2 text-white" value={0}>
								{selectedTreatmentId ? `${returnSelectedTreatment(selectedTreatmentId)?.name}` : 'Selecteer een behandeling'}
								<ChevronDownIcon className="ml-2 size-4"/>
							</button>
						</Dropdown.Trigger>

						<Dropdown.Portal>
							<Dropdown.Content
								className="flex flex-col items-center z-50 min-w-55 rounded-md border bg-white p-1 shadow-lg dark:bg-slate-800">
								<Dropdown.Item className={'DropdownDarkButtonCenteredItem'}
								               onSelect={() => setSelectedTreatmentId(0)}
								>
									Geen behandeling
								</Dropdown.Item>
								{Treatments.map((tr: Treatment) => (
									<Dropdown.Item
										className="DropdownDarkButtonCenteredItem"
										key={tr.treatment_id}
										onSelect={() => setSelectedTreatmentId(tr.treatment_id)}
									>
										{`${tr.name}`}
									</Dropdown.Item>
								))}
								<Dropdown.Separator className="my-1 h-px bg-gray-200"/>
							</Dropdown.Content>
						</Dropdown.Portal>
					</Dropdown.Root>
				</div>
				<div className = "flex flex-col text-center">
					<span>Notitie</span>
					<textarea name="note" id="" className="resize-none border-2 border-blue-400"
							  onChange={(e) => setNote(e.currentTarget.value)}>

					</textarea>
				</div>
				<div className = "bg-indigo-500 flex justify-center place-items-center p-8 rounded-2xl hover:font-bold"
				onClick={handleCreateAppointment}>
					Bevestig afspraak
				</div>
			</form>
		</>
	)
}
