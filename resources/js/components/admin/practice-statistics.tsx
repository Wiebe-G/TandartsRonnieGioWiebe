import {
	DoorOpen,
	Smile,
	Stethoscope,
	ThumbsDown,
	ThumbsUp,
	Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import {
	Card,
	CardContent,
	CardDescription,
	CardHeader,
	CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';
import type { PracticeStatistics as Statistics } from '@/types/admin';
import { formatDate, roleLabel } from './role-label';

function StatCard({
	title,
	icon: Icon,
	value,
	detail,
	children,
}: {
	title: string;
	icon: LucideIcon;
	value: ReactNode;
	detail: ReactNode;
	children?: ReactNode;
}) {
	return (
		<Card className="gap-3">
			<CardHeader className="flex flex-row items-center justify-between">
				<CardDescription>{title}</CardDescription>
				<Icon className="text-muted-foreground size-4" />
			</CardHeader>
			<CardContent className="space-y-3">
				<div>
					<p className="text-3xl font-semibold tabular-nums">
						{value}
					</p>
					<p className="text-muted-foreground text-xs">{detail}</p>
				</div>
				{children}
			</CardContent>
		</Card>
	);
}

/** Share of satisfied customers as a bar: green part satisfied, the rest not. */
function SatisfactionBar({ percentage }: { percentage: number }) {
	return (
		<span
			className="bg-muted relative inline-block h-1.5 w-24 overflow-hidden rounded-full"
			role="img"
			aria-label={`${percentage}% tevreden`}
		>
			<span
				className={cn(
					'absolute inset-y-0 left-0 rounded-full',
					percentage >= 75
						? 'bg-emerald-500'
						: percentage >= 50
							? 'bg-amber-400'
							: 'bg-red-500',
				)}
				style={{ width: `${percentage}%` }}
			/>
		</span>
	);
}

export default function PracticeStatistics({
	statistics,
}: {
	statistics: Statistics;
}) {
	const {
		satisfaction,
		rooms,
		customers,
		employees,
		dentistFeedback,
		latestFeedback,
	} = statistics;

	return (
		<section className="flex flex-col gap-4">
			<h2 className="text-lg font-semibold">Praktijkstatistieken</h2>

			<div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
				<StatCard
					title="Klanttevredenheid"
					icon={Smile}
					value={
						satisfaction.percentage !== null
							? `${satisfaction.percentage}%`
							: '-'
					}
					detail={
						satisfaction.count > 0
							? `tevreden, ${satisfaction.satisfied} van ${satisfaction.count} beoordelingen`
							: 'Nog geen beoordelingen'
					}
				>
					{satisfaction.percentage !== null && (
						<SatisfactionBar percentage={satisfaction.percentage} />
					)}
				</StatCard>

				<StatCard
					title="Kamers in gebruik"
					icon={DoorOpen}
					value={`${rooms.inUse} / ${rooms.total}`}
					detail={
						rooms.total === 0
							? 'Nog geen kamers aangemaakt'
							: 'Op dit moment'
					}
				>
					<ul className="space-y-1 text-sm">
						{rooms.list.map((room) => (
							<li
								key={room.room_id}
								className="flex items-center justify-between gap-2"
							>
								<span className="truncate">{room.name}</span>
								{room.appointment ? (
									<Badge
										title={`${room.appointment.customer} bij ${room.appointment.dentist}`}
									>
										Bezet tot{' '}
										{room.appointment.endtime.slice(0, 5)}
									</Badge>
								) : (
									<Badge variant="secondary">Vrij</Badge>
								)}
							</li>
						))}
					</ul>
				</StatCard>

				<StatCard
					title="Klanten in de praktijk"
					icon={Users}
					value={customers.presentNow}
					detail="Nu in behandeling"
				>
					<dl className="grid grid-cols-[1fr_auto] gap-y-1 text-sm">
						<dt className="text-muted-foreground">
							Verwacht later vandaag
						</dt>
						<dd className="tabular-nums">
							{customers.expectedLater}
						</dd>
						<dt className="text-muted-foreground">
							Totaal vandaag
						</dt>
						<dd className="tabular-nums">{customers.today}</dd>
					</dl>
				</StatCard>

				<StatCard
					title="Medewerkers aan het werk"
					icon={Stethoscope}
					value={`${employees.atWork} / ${employees.total}`}
					detail={`${employees.busyNow} nu met een klant bezig`}
				>
					<ul className="space-y-1 text-sm">
						{employees.list.map((employee) => (
							<li
								key={employee.id}
								className="flex items-center justify-between gap-2"
							>
								<span className="truncate">
									{employee.name}
									<span className="text-muted-foreground">
										{' '}
										·{' '}
										{roleLabel(employee.role ?? undefined)}
									</span>
								</span>
								<span
									className={cn(
										'size-2 shrink-0 rounded-full',
										employee.busy
											? 'bg-amber-500'
											: 'bg-emerald-500',
									)}
									title={
										employee.busy ? 'Bezig' : 'Beschikbaar'
									}
								/>
							</li>
						))}
					</ul>
				</StatCard>
			</div>

			<div className="grid gap-4 lg:grid-cols-2">
				<Card>
					<CardHeader>
						<CardTitle>Feedback per tandarts</CardTitle>
					</CardHeader>
					<CardContent>
						{dentistFeedback.length === 0 ? (
							<p className="text-muted-foreground text-sm">
								Nog geen tandartsen.
							</p>
						) : (
							<ul className="divide-y text-sm">
								{dentistFeedback.map((dentist) => (
									<li
										key={dentist.id}
										className="flex items-center justify-between gap-2 py-2"
									>
										<span className="truncate">
											{dentist.name}
										</span>
										{dentist.percentage !== null ? (
											<span className="flex items-center gap-2">
												<SatisfactionBar
													percentage={dentist.percentage}
												/>
												<span className="tabular-nums">
													{dentist.percentage}%
												</span>
												<span className="text-muted-foreground">
													({dentist.count})
												</span>
											</span>
										) : (
											<span className="text-muted-foreground">
												Geen feedback
											</span>
										)}
									</li>
								))}
							</ul>
						)}
					</CardContent>
				</Card>

				<Card>
					<CardHeader>
						<CardTitle>Recente feedback</CardTitle>
					</CardHeader>
					<CardContent>
						{latestFeedback.length === 0 ? (
							<p className="text-muted-foreground text-sm">
								Nog geen feedback ontvangen.
							</p>
						) : (
							<ul className="divide-y text-sm">
								{latestFeedback.map((feedback) => (
									<li
										key={feedback.feedback_id}
										className="space-y-1 py-2"
									>
										<div className="flex flex-wrap items-center justify-between gap-2">
											{feedback.satisfied ? (
												<span className="flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
													<ThumbsUp className="size-3.5" />
													Tevreden
												</span>
											) : (
												<span className="flex items-center gap-1 text-red-600 dark:text-red-400">
													<ThumbsDown className="size-3.5" />
													Niet tevreden
												</span>
											)}
											<span className="text-muted-foreground text-xs">
												{feedback.customer?.name} over{' '}
												{feedback.dentist?.name},{' '}
												{formatDate(
													feedback.created_at,
												)}
											</span>
										</div>
										{feedback.comment && (
											<p>{feedback.comment}</p>
										)}
									</li>
								))}
							</ul>
						)}
					</CardContent>
				</Card>
			</div>
		</section>
	);
}
