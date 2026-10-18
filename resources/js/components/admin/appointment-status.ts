const statusLabels: Record<string, string> = {
	scheduled: 'Gepland',
	completed: 'Afgerond',
	cancelled: 'Geannuleerd',
	no_show: 'Niet verschenen',
};

export function statusLabel(status: string): string {
	return statusLabels[status] ?? status;
}

export function statusClass(status: string): string {
	switch (status) {
		case 'completed':
			return 'border-l-emerald-500';
		case 'cancelled':
		case 'no_show':
			return 'border-l-red-500 opacity-60';
		default:
			return 'border-l-blue-500';
	}
}
