import {Head, router, usePage} from '@inertiajs/react'
import {PageProps} from "@inertiajs/core";
import {User} from "@/types/Interfaces";
import * as Dropdown from '@radix-ui/react-dropdown-menu';
import {ChevronDownIcon} from "lucide-react";
import React, {useState} from "react";

interface Props extends PageProps {
	// Customers blijft een object hoe hard ik ook Laravel probeer te vertellen dat het een array moet zijn
	Customers: User[];
}

export default function Create({}) {
	const {Customers} = usePage<Props>().props;

	const [selectedUser, setSelectedUser] = useState<User>();
	const [statefulDate, setStatefulDate] = useState(new Date());

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
		// check voor of datum in het verleden is
		router.post(
			`/appointments/create`,
			{
				Customer_id: selectedUser?.id ?? 0,
				Dentist_id: 1,
				Assistant_id: 1,
				Date: statefulDate,
				/* nu even type op routine, maar dit moet naar een dropdown met alle treatments
				 die medewerkers hebben aangemaakt, en dan die id
				 en die dan linken naar de treatments tabel
				*/
				Type: 'Routine',
			}
		)
	}

	return (
		<>
			<Head title="Afspraak maken"/>
			{/*	Dropdown met alle klanten, datum en tijd, soort behandeling*/}
			<form action="" className="flex flex-col place-items-center border-2 border-indigo-500 h-full py-4 w-full gap-y-8">
				<div className = "min-w-[50vw] justify-items-center">
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
				<div className = "text-center">
					<label htmlFor="date">Kies een datum: </label>
					<br/>
					<input type="datetime-local" id="date" name="date" onChange={handleUpdateDate} />
				</div>
				<div className = "">
					Soort behandeling
				</div>
				<div className = "bg-indigo-500 flex justify-center place-items-center p-8 rounded-2xl hover:font-bold"
				onClick={handleCreateAppointment}>
					Bevestig afspraak
				</div>
			</form>
		</>
	)
}
