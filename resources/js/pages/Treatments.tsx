import {Head, Link, usePage} from '@inertiajs/react'
import {PlaceholderPattern} from "@/components/ui/placeholder-pattern";
import {Edit, PlusSquareIcon} from "lucide-react";
import {PageProps} from "@inertiajs/core";
import {Treatment} from "@/types/Interfaces";

interface Props extends PageProps {
	Treatments: Treatment[];
}

export default function Treatments({}) {
	const { Treatments } = usePage<Props>().props;

	console.log(Treatments);

    return (
        <>
            <Head title="Behandelingen" />
			<div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
				<div className="grid auto-rows-min gap-4 md:grid-cols-3">
					<Link href="/treatments/create" className="LayoutGrid place-items-center h-full">
						<h1>Nieuwe behandeling</h1>
						<PlusSquareIcon/>
					</Link>
					<div className="LayoutGrid">
						<span className="text-center">Alle behandelingen:</span>
						{Treatments.map((treatment: Treatment) => (
							<div className="border-2 border-red-500 flex w-full px-2" key={treatment.treatment_id}>
								<span>{treatment.name}</span>
								<span className="ml-auto"><Edit /></span>
							</div>
						))}
					</div>
					<div className="LayoutGrid">
						Nog iets
					</div>
				</div>
				<div className="border-sidebar-border/70 dark:border-sidebar-border relative min-h-screen flex-1 overflow-hidden rounded-xl border md:min-h-min">
					<PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
				</div>
			</div>
        </>
    )
}

Treatments.layout = {
	breadcrumbs: [
		{
			title: "Behandelingen",
			link: "/treatments",
		}
	]
}
