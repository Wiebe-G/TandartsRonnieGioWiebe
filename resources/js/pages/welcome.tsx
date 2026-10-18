import { Head, Link, usePage } from '@inertiajs/react';
import { CalendarPlus, Clock, MapPin } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { dashboard, login, register } from '@/routes';
import { create as bookAppointment } from '@/routes/appointments';

type Props = {
	openingHours: { open: string; close: string };
};

// Monday first; getDay() numbers Sunday as 0
const days = [
	{ name: 'Maandag', day: 1, open: true },
	{ name: 'Dinsdag', day: 2, open: true },
	{ name: 'Woensdag', day: 3, open: true },
	{ name: 'Donderdag', day: 4, open: true },
	{ name: 'Vrijdag', day: 5, open: true },
	{ name: 'Zaterdag', day: 6, open: false },
	{ name: 'Zondag', day: 0, open: false },
];

export default function Welcome({ openingHours }: Props) {
	const { auth } = usePage().props;

	// Only known in the browser, so the server-rendered page never disagrees with it
	const [today, setToday] = useState<number | null>(null);
	useEffect(() => setToday(new Date().getDay()), []);

	// Logged-in customers book right away; visitors first make an account
	const bookingHref = auth.user
		? auth.isCustomer
			? bookAppointment()
			: dashboard()
		: register();

	return (
		<>
			<Head title="Welkom" />
			<div className="bg-background text-foreground flex min-h-screen flex-col">
				<header className="bg-background/90 sticky top-0 z-10 border-b backdrop-blur">
					<nav className="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-3">
						<span className="font-semibold">
							Tandheelkundig Centrum Hengelo
						</span>
						<div className="flex items-center gap-2">
							{auth.user ? (
								<Button asChild size="sm">
									<Link href={dashboard()}>Dashboard</Link>
								</Button>
							) : (
								<>
									<Button asChild variant="ghost" size="sm">
										<Link href={login()}>Inloggen</Link>
									</Button>
									<Button asChild size="sm">
										<Link href={register()}>
											Registreren
										</Link>
									</Button>
								</>
							)}
						</div>
					</nav>
				</header>

				<main className="flex-1">
					{/* Photo from public/images/; without it the gradient alone is shown */}
					<section
						className="relative isolate bg-cover bg-center"
						style={{
							backgroundImage:
								"url('/images/tandartspraktijk.jpg')",
						}}
					>
						<div className="absolute inset-0 -z-10 bg-linear-to-br from-sky-900/90 via-sky-800/80 to-teal-700/80" />
						<div className="mx-auto max-w-5xl px-4 py-20 text-white md:py-28">
							<h1 className="max-w-2xl text-3xl font-bold tracking-tight md:text-5xl">
								Welkom bij onze tandartspraktijk
							</h1>
							<p className="mt-4 max-w-2xl text-lg text-white/90">
								Hier maakt u gemakkelijk online een afspraak,
								leest u meer over onze behandelingen en maakt u
								kennis met ons team. Uw glimlach is onze
								prioriteit: we helpen u graag aan een gezond en
								stralend gebit.
							</p>
							<div className="mt-8 flex flex-wrap gap-3">
								<Button
									asChild
									size="lg"
									className="bg-white text-sky-900 hover:bg-white/90"
								>
									<Link href={bookingHref}>
										<CalendarPlus />
										Afspraak maken
									</Link>
								</Button>
								{!auth.user && (
									<Button
										asChild
										size="lg"
										variant="outline"
										className="border-white/60 bg-transparent text-white hover:bg-white/10 hover:text-white"
									>
										<Link href={login()}>
											Ik heb al een account
										</Link>
									</Button>
								)}
							</div>
						</div>
					</section>

					<section className="mx-auto grid max-w-5xl gap-6 px-4 py-12 md:grid-cols-2">
						<div className="rounded-xl border p-6">
							<h2 className="flex items-center gap-2 text-xl font-semibold">
								<Clock className="size-5" />
								Openingstijden
							</h2>
							<dl className="mt-4 divide-y text-sm">
								{days.map(({ name, day, open }) => (
									<div
										key={name}
										className={cn(
											'flex justify-between py-2',
											today === day &&
												'-mx-2 rounded-md bg-sky-50 px-2 font-semibold dark:bg-sky-950',
										)}
									>
										<dt>
											{name}
											{today === day && (
												<span className="text-muted-foreground ml-2 font-normal">
													(vandaag)
												</span>
											)}
										</dt>
										<dd
											className={cn(
												!open &&
													'text-muted-foreground',
											)}
										>
											{open
												? `${openingHours.open} – ${openingHours.close}`
												: 'Gesloten'}
										</dd>
									</div>
								))}
							</dl>
							<p className="text-muted-foreground mt-4 text-sm">
								Afspraken maken kan alleen op werkdagen tussen{' '}
								{openingHours.open} en {openingHours.close}.
							</p>
						</div>

						<div className="rounded-xl border p-6">
							<h2 className="flex items-center gap-2 text-xl font-semibold">
								<MapPin className="size-5" />
								Zo werkt het
							</h2>
							<ol className="text-muted-foreground mt-4 list-decimal space-y-3 pl-5 text-sm">
								<li>
									<span className="text-foreground font-medium">
										Maak een account aan
									</span>{' '}
									en kies direct uw vaste tandarts.
								</li>
								<li>
									<span className="text-foreground font-medium">
										Plan online een afspraak
									</span>{' '}
									op een moment dat u uitkomt. U ontvangt een
									bevestiging per e-mail.
								</li>
								<li>
									<span className="text-foreground font-medium">
										Kunt u toch niet?
									</span>{' '}
									Annuleer dan uiterlijk 48 uur van tevoren
									via uw agenda.
								</li>
							</ol>
						</div>
					</section>
				</main>

				<footer className="text-muted-foreground border-t py-6 text-center text-sm">
					Tandheelkundig Centrum Hengelo
				</footer>
			</div>
		</>
	);
}
