import Layout from '@/layouts/app-layout'
import {Head, usePage} from '@inertiajs/react'
import {PageProps} from "@inertiajs/core";
import {User} from "@/types/Interfaces";
import * as Dropdown from '@radix-ui/react-dropdown-menu';
import {ChevronDownIcon} from "lucide-react";
import {useState} from "react";

interface Props extends PageProps {
	// Customers blijft een object hoe hard ik ook Laravel probeer te vertellen dat het een array moet zijn
	Customers: User[];
}

export default function Create({}) {
	const {Customers} = usePage<Props>().props;

	console.log(Customers);

	const [selectedUser, setSelectedUser] = useState<User>();

	const handleCategoryChange = (id: number): void => {
		setSelectedUser(Customers.find((cat) => cat.id === id));
	}

	return (
		<>
			<Head title="Afspraak maken"/>
			{/*	Dropdown met alle klanten, datum en tijd, soort behandeling*/}
			<form action="" className="flex flex-col place-items-center border-2 border-indigo-500 h-full py-4">
				<div className = "pb-8">
					<Dropdown.Root>
						<Dropdown.Trigger asChild>
							<button type={'button'}
							        className="flex items-center rounded bg-blue-500 px-4 py-2 text-white" value={0}>
								{selectedUser ? selectedUser.firstname : 'Selecteer een klant'}
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
										{`${cat.firstname}`}
									</Dropdown.Item>
								))}
								<Dropdown.Separator className="my-1 h-px bg-gray-200"/>
							</Dropdown.Content>
						</Dropdown.Portal>
					</Dropdown.Root>
				</div>
				<div className = "pb-8">
					Datum en tijd selecteren
				</div>
				<div>
					Soort behandeling
				</div>
			</form>
		</>
	)
}
