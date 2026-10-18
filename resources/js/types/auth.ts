export type User = {
	id: number;
	role_id: number;
	employee_id: number | null;
	firstname: string;
	lastname: string;
	name: string;
	email: string;
	phonenumber: string | null;
	birthday: string | null;
	status: string;
	address: string | null;
	avatar?: string;
	email_verified_at: string | null;
	two_factor_enabled?: boolean;
	created_at: string;
	updated_at: string;
	[key: string]: unknown;
};

export type Auth = {
	user: User;
	isAdmin: boolean;
	isCustomer: boolean;
	/** True while an admin looks around via "Bekijk als klant". */
	impersonating: boolean;
};

export type TwoFactorSetupData = {
	svg: string;
	url: string;
};

export type TwoFactorSecretKey = {
	secretKey: string;
};
