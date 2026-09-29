// import Layout from '@/layouts/app-layout'
import {Head, usePage} from '@inertiajs/react'
import {PageProps} from "@inertiajs/core";
import {Appointment} from "@/types/Interfaces";

interface Props extends PageProps {
    Appointments: Appointment[];
}

export default function appointments({}) {
    const { Appointments } = usePage<Props>().props;

    console.log(`Er zijn een totaal van ${Appointments.length} afspraken`);
    return (
        <>
            <Head title="Afspraken"/>

        </>
    )
}
