import type { Slot } from '@/lib/appointment-slots';
import { cn } from '@/lib/utils';

type Props = {
	slots: Slot[];
	value: string | null;
	onChange: (start: string) => void;
};

/** Grid of half-hour buttons; taken times are greyed out with the reason as tooltip. */
export default function SlotPicker({ slots, value, onChange }: Props) {
	if (!slots.some((slot) => slot.free)) {
		return (
			<p className="text-muted-foreground rounded-md border border-dashed p-4 text-center text-sm">
				Geen vrije tijden op deze dag. Kies een andere dag, tandarts,
				assistent of kamer.
			</p>
		);
	}

	return (
		<div className="grid grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-6">
			{slots.map((slot) => {
				const selected = value === slot.start;

				return (
					<button
						key={slot.start}
						type="button"
						disabled={!slot.free}
						title={
							slot.free
								? `${slot.start} - ${slot.end}`
								: slot.reason
						}
						onClick={() => onChange(slot.start)}
						aria-pressed={selected}
						className={cn(
							'rounded-md border px-2 py-2 text-sm tabular-nums transition-colors',
							slot.free &&
								!selected &&
								'hover:border-primary hover:bg-accent',
							selected &&
								'border-primary bg-primary text-primary-foreground',
							!slot.free &&
								'text-muted-foreground/50 cursor-not-allowed border-dashed line-through',
						)}
					>
						{slot.start}
					</button>
				);
			})}
		</div>
	);
}
