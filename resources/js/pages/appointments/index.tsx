import { Head, Link, router } from '@inertiajs/react';
import { CalendarPlus, ChevronLeft, ChevronRight } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { cancel, create, index } from '@/routes/appointments';

type Appointment = {
    id: number;
    date: string;
    starttime: string;
    endtime: string;
    status: string;
    note: string | null;
    customer: string;
    dentist: string;
    treatments: string[];
    canCancel: boolean;
    cancelTooLate: boolean;
};

type Props = {
    month: string;
    isCustomer: boolean;
    cancelHours: number;
    appointments: Appointment[];
};

const weekdays = ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'];

const statusLabels: Record<
    string,
    {
        label: string;
        variant: 'default' | 'secondary' | 'destructive' | 'outline';
    }
> = {
    scheduled: { label: 'Gepland', variant: 'default' },
    completed: { label: 'Afgerond', variant: 'secondary' },
    cancelled: { label: 'Geannuleerd', variant: 'destructive' },
};

function toDateString(date: Date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

function shiftMonth(month: string, by: number) {
    const [year, monthIndex] = month.split('-').map(Number);
    const date = new Date(year, monthIndex - 1 + by, 1);

    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
}

/** All days shown in the month grid, starting on the Monday on or before the 1st. */
function calendarDays(month: string) {
    const [year, monthIndex] = month.split('-').map(Number);
    const first = new Date(year, monthIndex - 1, 1);
    const start = new Date(first);
    start.setDate(first.getDate() - ((first.getDay() + 6) % 7));

    const days: { date: string; day: number; inMonth: boolean }[] = [];

    for (
        const cursor = new Date(start);
        days.length < 42;
        cursor.setDate(cursor.getDate() + 1)
    ) {
        days.push({
            date: toDateString(cursor),
            day: cursor.getDate(),
            inMonth: cursor.getMonth() === monthIndex - 1,
        });
    }

    // Drop a trailing week that falls entirely in the next month.
    return days.slice(35).every((day) => !day.inMonth)
        ? days.slice(0, 35)
        : days;
}

function formatDate(date: string) {
    return new Date(`${date}T00:00:00`).toLocaleDateString('nl-NL', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    });
}

export default function Agenda({
    month,
    isCustomer,
    cancelHours,
    appointments,
}: Props) {
    const [selected, setSelected] = useState<string | null>(null);
    const today = toDateString(new Date());

    const byDate = appointments.reduce<Record<string, Appointment[]>>(
        (groups, appointment) => {
            (groups[appointment.date] ??= []).push(appointment);

            return groups;
        },
        {},
    );

    const [year, monthIndex] = month.split('-').map(Number);
    const monthLabel = new Date(year, monthIndex - 1, 1).toLocaleDateString(
        'nl-NL',
        { month: 'long', year: 'numeric' },
    );
    const listed = selected ? (byDate[selected] ?? []) : appointments;

    function cancelAppointment(appointment: Appointment) {
        if (
            window.confirm(
                `Weet u zeker dat u de afspraak van ${formatDate(appointment.date)} om ${appointment.starttime} wilt annuleren?`,
            )
        ) {
            router.patch(
                cancel.url(appointment.id),
                {},
                { preserveScroll: true },
            );
        }
    }

    return (
        <>
            <Head title="Agenda" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">Agenda</h1>
                        <p className="text-muted-foreground">
                            {isCustomer
                                ? 'Al uw afspraken op een rij.'
                                : 'Alle afspraken van de praktijk.'}
                        </p>
                    </div>
                    {isCustomer && (
                        <Button asChild>
                            <Link href={create()}>
                                <CalendarPlus />
                                Afspraak maken
                            </Link>
                        </Button>
                    )}
                </div>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle className="text-lg capitalize">
                            {monthLabel}
                        </CardTitle>
                        <div className="flex gap-1">
                            <Button variant="outline" size="icon" asChild>
                                <Link
                                    href={index({
                                        query: { maand: shiftMonth(month, -1) },
                                    })}
                                    aria-label="Vorige maand"
                                    onClick={() => setSelected(null)}
                                >
                                    <ChevronLeft />
                                </Link>
                            </Button>
                            <Button variant="outline" size="sm" asChild>
                                <Link
                                    href={index()}
                                    onClick={() => setSelected(null)}
                                >
                                    Vandaag
                                </Link>
                            </Button>
                            <Button variant="outline" size="icon" asChild>
                                <Link
                                    href={index({
                                        query: { maand: shiftMonth(month, 1) },
                                    })}
                                    aria-label="Volgende maand"
                                    onClick={() => setSelected(null)}
                                >
                                    <ChevronRight />
                                </Link>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="bg-border grid grid-cols-7 gap-px overflow-hidden rounded-lg border text-sm">
                            {weekdays.map((weekday) => (
                                <div
                                    key={weekday}
                                    className="bg-muted text-muted-foreground px-2 py-1 text-center text-xs font-medium uppercase"
                                >
                                    {weekday}
                                </div>
                            ))}
                            {calendarDays(month).map(
                                ({ date, day, inMonth }) => {
                                    const dayAppointments = (
                                        byDate[date] ?? []
                                    ).filter(
                                        (appointment) =>
                                            appointment.status !== 'cancelled',
                                    );

                                    return (
                                        <button
                                            key={date}
                                            type="button"
                                            onClick={() =>
                                                setSelected(
                                                    selected === date
                                                        ? null
                                                        : date,
                                                )
                                            }
                                            className={cn(
                                                'bg-background hover:bg-accent flex min-h-16 flex-col items-start gap-1 p-1.5 text-left transition-colors sm:min-h-24',
                                                !inMonth &&
                                                    'bg-muted/40 text-muted-foreground',
                                                selected === date &&
                                                    'ring-primary ring-2 ring-inset',
                                            )}
                                        >
                                            <span
                                                className={cn(
                                                    'flex size-6 items-center justify-center rounded-full text-xs',
                                                    date === today &&
                                                        'bg-primary text-primary-foreground font-semibold',
                                                )}
                                            >
                                                {day}
                                            </span>
                                            {dayAppointments
                                                .slice(0, 2)
                                                .map((appointment) => (
                                                    <span
                                                        key={appointment.id}
                                                        className="bg-primary/10 text-primary hidden w-full truncate rounded px-1 text-xs sm:block"
                                                    >
                                                        {appointment.starttime}{' '}
                                                        {appointment
                                                            .treatments[0] ??
                                                            'Afspraak'}
                                                    </span>
                                                ))}
                                            {dayAppointments.length > 0 && (
                                                <span
                                                    className="bg-primary size-1.5 rounded-full sm:hidden"
                                                    aria-label={`${dayAppointments.length} afspraken`}
                                                />
                                            )}
                                            {dayAppointments.length > 2 && (
                                                <span className="text-muted-foreground hidden text-xs sm:block">
                                                    +
                                                    {dayAppointments.length - 2}{' '}
                                                    meer
                                                </span>
                                            )}
                                        </button>
                                    );
                                },
                            )}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle className="text-base first-letter:uppercase">
                            {selected
                                ? formatDate(selected)
                                : `Afspraken in ${monthLabel}`}
                        </CardTitle>
                        {selected && (
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => setSelected(null)}
                            >
                                Hele maand tonen
                            </Button>
                        )}
                    </CardHeader>
                    <CardContent>
                        {listed.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                Geen afspraken.
                            </p>
                        ) : (
                            <ul className="divide-y">
                                {listed.map((appointment) => {
                                    const status = statusLabels[
                                        appointment.status
                                    ] ?? {
                                        label: appointment.status,
                                        variant: 'outline' as const,
                                    };

                                    return (
                                        <li
                                            key={appointment.id}
                                            className="flex flex-wrap items-start justify-between gap-3 py-3"
                                        >
                                            <div>
                                                <p className="font-medium">
                                                    {appointment.treatments
                                                        .length > 0
                                                        ? appointment.treatments.join(
                                                              ', ',
                                                          )
                                                        : 'Afspraak'}
                                                </p>
                                                <p className="text-muted-foreground text-sm first-letter:uppercase">
                                                    {formatDate(
                                                        appointment.date,
                                                    )}
                                                    , {appointment.starttime} –{' '}
                                                    {appointment.endtime}
                                                </p>
                                                <p className="text-muted-foreground text-sm">
                                                    {isCustomer
                                                        ? appointment.dentist
                                                        : `${appointment.customer} · ${appointment.dentist}`}
                                                </p>
                                                {appointment.note && (
                                                    <p className="mt-1 text-sm italic">
                                                        {appointment.note}
                                                    </p>
                                                )}
                                                {appointment.cancelTooLate && (
                                                    <p className="text-muted-foreground mt-1 text-xs">
                                                        Online annuleren kan tot{' '}
                                                        {cancelHours} uur van
                                                        tevoren. Bel de praktijk
                                                        als u niet kunt komen.
                                                    </p>
                                                )}
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <Badge variant={status.variant}>
                                                    {status.label}
                                                </Badge>
                                                {appointment.canCancel && (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            cancelAppointment(
                                                                appointment,
                                                            )
                                                        }
                                                    >
                                                        Annuleren
                                                    </Button>
                                                )}
                                            </div>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Agenda.layout = {
    breadcrumbs: [{ title: 'Agenda', href: index() }],
};
