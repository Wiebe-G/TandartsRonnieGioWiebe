export interface Appointment {
	appointment_id: number;
	customer_id: number;
	dentist_id: number;
	assistant_id: number | null;
	date: Date;
	starttime: Date;
	endtime: Date;
	status: string;
	note: string;
	customer: User;
	treatments: Treatment[];
	dentist: User;
	assistant: User | null;
}

interface Role {
	role_id: number;
	name: string;
}

export interface User {
	user: User;
	id: number;
	role_id: number;
	role?: Role;
	firstname: string;
	lastname: string;
	email: string;
	phonenumber: string;
	birthday: Date;
	status: string;
	address: string;
}

export interface Treatment {
	treatment_id: number;
	name: string;
	description: string;
	price: string;
	duration: number;
	active: boolean | number;
}
