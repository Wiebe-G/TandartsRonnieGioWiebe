export type Role = {
	role_id: number;
	name: string;
	users_count?: number;
};

export type ManagedUser = {
	id: number;
	role_id: number;
	employee_id: number | null;
	firstname: string;
	lastname: string;
	name: string;
	email: string;
	phonenumber: string | null;
	birthday: string | null;
	status: 'active' | 'inactive';
	created_at: string;
	role?: Role;
	employee?: ManagedUser | null;
	customers_count?: number;
};

export type Dentist = Pick<ManagedUser, 'id' | 'firstname' | 'lastname'>;

export type AdminAppointment = {
	appointment_id: number;
	date: string;
	starttime: string;
	endtime: string;
	status: string;
	customer?: ManagedUser;
	dentist?: ManagedUser;
	treatments: { treatment_id: number; name: string }[];
};

export type PaginationLink = {
	url: string | null;
	label: string;
	active: boolean;
};

export type Paginated<T> = {
	data: T[];
	links: PaginationLink[];
	from: number | null;
	to: number | null;
	total: number;
};

export type PracticeStatistics = {
	satisfaction: {
		average: number | null;
		count: number;
		positivePercentage: number | null;
	};
	rooms: {
		total: number;
		inUse: number;
		list: {
			room_id: number;
			name: string;
			appointment: {
				customer: string;
				dentist: string;
				endtime: string;
			} | null;
		}[];
	};
	customers: {
		presentNow: number;
		expectedLater: number;
		today: number;
	};
	employees: {
		atWork: number;
		busyNow: number;
		total: number;
		list: {
			id: number;
			name: string;
			role: string | null;
			busy: boolean;
		}[];
	};
	dentistFeedback: {
		id: number;
		name: string;
		count: number;
		average: number | null;
	}[];
	latestFeedback: {
		feedback_id: number;
		rating: number;
		comment: string | null;
		created_at: string;
		customer?: Pick<ManagedUser, 'id' | 'name'>;
		dentist?: Pick<ManagedUser, 'id' | 'name'>;
	}[];
};

export type CalendarEmployee = Pick<
	ManagedUser,
	'id' | 'role_id' | 'firstname' | 'lastname' | 'name' | 'status'
> & {
	role?: Role;
};

type PersonSummary = Pick<
	ManagedUser,
	'id' | 'firstname' | 'lastname' | 'name'
>;

export type CalendarAppointment = {
	appointment_id: number;
	date: string;
	starttime: string;
	endtime: string;
	status: string;
	note: string | null;
	dentist_id: number;
	assistant_id: number | null;
	room_id: number | null;
	customer: PersonSummary | null;
	dentist: PersonSummary | null;
	assistant: PersonSummary | null;
	room: { room_id: number; name: string } | null;
	treatments: { treatment_id: number; name: string }[];
};

export type CalendarOptions = {
	dentists: CalendarEmployee[];
	staff: CalendarEmployee[];
	rooms: { room_id: number; name: string }[];
	statuses: string[];
};
