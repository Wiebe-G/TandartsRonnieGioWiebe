import Layout from '@/layouts/app-layout'
import { Head } from '@inertiajs/react'

export default function Create({}) {
    return (
        <>
            <Head title="Afspraak maken"/>

        </>
    )
}

Create.layout = {
	breadcrumbs: [
		{
			title: "Afspraken",
			link: "/appointments",
		},
		{
			title: "Afspraak maken",
			link: "/appointments/create",
		}
	]
}
