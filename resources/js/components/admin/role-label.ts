const roleLabels: Record<string, string> = {
	customer: 'Klant',
	dentist: 'Tandarts',
	dental_hygienist: 'Mondhygiënist',
	assistant: 'Assistent',
	practice_manager: 'Praktijkmanager',
	admin: 'Beheerder',
};

export function roleLabel(name?: string): string {
	return name ? (roleLabels[name] ?? name) : '-';
}

export function statusLabel(status: string): string {
	return status === 'active' ? 'Actief' : 'Inactief';
}

export function formatDate(value: string | null): string {
	return value ? new Date(value).toLocaleDateString('nl-NL') : '-';
}
