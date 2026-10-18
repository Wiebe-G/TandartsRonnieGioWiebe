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

/** Same colours as the bar on the appointment cards, as a pill. */
export function statusBadgeClass(status: string): string {
	switch (status) {
		case 'completed':
			return 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400';
		case 'cancelled':
		case 'no_show':
			return 'bg-red-500/15 text-red-700 dark:text-red-400';
		default:
			return 'bg-blue-500/15 text-blue-700 dark:text-blue-400';
	}
}
