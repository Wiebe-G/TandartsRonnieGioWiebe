export interface Appointment {
    appointment_id: number;
    customer_id: number;
    dentist_id: number;
    assistant_id: number;
    date: Date;
    starttime: Date;
    endtime: Date;
    status: string;
    note: string;
	customer: User;
}

export interface Role {
    role_id: number;
    name: string;
}

export interface User {
    id: number;
    role_id: number;
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

}
