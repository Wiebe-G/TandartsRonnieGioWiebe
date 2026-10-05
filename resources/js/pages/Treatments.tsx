import {Head, Link} from '@inertiajs/react'
import {PlaceholderPattern} from "@/components/ui/placeholder-pattern";

export default function Treatments({}) {
    return (
        <>
            <Head title="Behandelingen" />
			<div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
				<div className="grid auto-rows-min gap-4 md:grid-cols-3">
					<Link href="/treatments/create" className="LayoutGrid">
						Nieuwe behandeling
					</Link>
					<div className="LayoutGrid">
						Lijst van alle behandelingen
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
