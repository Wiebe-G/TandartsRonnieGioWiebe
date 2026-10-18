// The same planning rules as app/Support/OpeningHours.php, used to show free time slots.

export const OPEN = '09:00';
export const CLOSE = '17:00';
export const SLOT_MINUTES = 30;

export type BusyAppointment = {
	appointment_id: number;
	dentist_id: number;
	assistant_id: number | null;
	room_id: number | null;
	starttime: string;
	endtime: string;
};

export type Slot = {
	start: string; // HH:MM
	end: string; // HH:MM
	free: boolean;
	reason?: string;
};

function toMinutes(time: string): number {
	const [hours, minutes] = time.split(':').map(Number);

	return hours * 60 + minutes;
}

function toTime(minutes: number): string {
	return `${String(Math.floor(minutes / 60)).padStart(2, '0')}:${String(minutes % 60).padStart(2, '0')}`;
}

/** Treatment length rounded up to whole half hours. */
export function slotLength(durationMinutes: number): number {
	return (
		Math.ceil(Math.max(durationMinutes, 1) / SLOT_MINUTES) * SLOT_MINUTES
	);
}

/** YYYY-MM-DD in local time. */
export function localDate(date: Date = new Date()): string {
	return date.toLocaleDateString('sv-SE');
}

export function isWeekend(date: string): boolean {
	const day = new Date(`${date}T00:00:00`).getDay();

	return day === 0 || day === 6;
}

/**
 * Every half hour between opening and closing, marked free or taken for the chosen people and room.
 */
export function slotsFor({
	date,
	durationMinutes,
	busy,
	dentistId,
	assistantId,
	roomId,
	ignoreAppointmentId,
}: {
	date: string;
	durationMinutes: number;
	busy: BusyAppointment[];
	dentistId?: number | null;
	assistantId?: number | null;
	roomId?: number | null;
	ignoreAppointmentId?: number;
}): Slot[] {
	const length = slotLength(durationMinutes);
	const now = new Date();
	const isToday = date === localDate(now);
	const nowMinutes = now.getHours() * 60 + now.getMinutes();
	const slots: Slot[] = [];

	for (
		let start = toMinutes(OPEN);
		start < toMinutes(CLOSE);
		start += SLOT_MINUTES
	) {
		const end = start + length;
		const slot: Slot = {
			start: toTime(start),
			end: toTime(end),
			free: true,
		};

		const overlapping = busy.filter(
			(other) =>
				other.appointment_id !== ignoreAppointmentId &&
				toMinutes(other.starttime) < end &&
				toMinutes(other.endtime) > start,
		);
		const personBusy = overlapping.some(
			(other) =>
				(dentistId &&
					(other.dentist_id === dentistId ||
						other.assistant_id === dentistId)) ||
				(assistantId &&
					(other.assistant_id === assistantId ||
						other.dentist_id === assistantId)),
		);
		const roomTaken =
			Boolean(roomId) &&
			overlapping.some((other) => other.room_id === roomId);

		if (end > toMinutes(CLOSE)) {
			slot.free = false;
			slot.reason = 'Loopt na 17:00 door';
		} else if (isToday && start <= nowMinutes) {
			slot.free = false;
			slot.reason = 'Al voorbij';
		} else if (personBusy) {
			slot.free = false;
			slot.reason = 'Tandarts of assistent bezet';
		} else if (roomTaken) {
			slot.free = false;
			slot.reason = 'Kamer bezet';
		}

		slots.push(slot);
	}

	return slots;
}
