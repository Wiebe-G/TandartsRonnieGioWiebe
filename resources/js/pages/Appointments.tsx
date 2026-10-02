// import Layout from '@/layouts/app-layout'
import {Head, Link, usePage} from '@inertiajs/react'
import {PageProps} from "@inertiajs/core";
import {Appointment} from "@/types/Interfaces";
import {PlaceholderPattern} from "@/components/ui/placeholder-pattern";

interface Props extends PageProps {
	Appointments: Appointment[];
}

export default function Appointments({}) {
	const {Appointments} = usePage<Props>().props;

	console.log(`Er zijn een totaal van ${Appointments.length} afspraken`);
	return (
		<>
			<Head title="Afspraken"/>
			<div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
				<div className="grid auto-rows-min gap-4 md:grid-cols-3">
					<Link
						className="LayoutGrid"
						href="/appointments/create">
						<h1>
							Nieuwe afspraak
						</h1>
					</Link>
					<div className="LayoutGrid">
						<h1>Aantal afspraken vandaag</h1>
					</div>
					<div className="LayoutGrid">
						<h1>Afgeronde afspraken</h1>
					</div>
				</div>
				<div
					className="border-sidebar-border/70 dark:border-sidebar-border relative min-h-screen flex-1 overflow-hidden rounded-xl border md:min-h-min">
					<PlaceholderPattern
						className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20"/>
				</div>
			</div>
		</>
	)
}

Appointments.layout = {
	breadcrumbs: [
		{
			title: "Afspraken",
			link: "/appointments",
		}
	]
}
