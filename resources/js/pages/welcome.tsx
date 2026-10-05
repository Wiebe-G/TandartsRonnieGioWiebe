import { Head, Link, usePage } from '@inertiajs/react';
import { dashboard, login } from '@/routes';
import { register } from '@/routes';

export default function Welcome() {
    const { auth } = usePage().props;
    const dashboardUrl = dashboard();


    
    return (
        <>
            <Head title="Welcome" />
            <div className="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#f3f3f0] lg:justify-center lg:p-8 dark:bg-[#3d0f0f] 
            fixed top-0 left-0 right-0
            
            "style={{
                        backgroundImage: "url('/storage/Tandartspraktijk.jpg')",
                        backgroundSize: '60% 100%',
                        backgroundPosition: 'center',
                    }}>
                <header className="mb-6 w-full max-w--83.75 text-sm not-has-[nav]:hidden lg:max-w-4xl">
                    <nav className="flex items-center justify-end gap-4 fixed top-0 left-0 right-0 border-2">
                        {auth.user ? (
                            <Link
                                href={dashboardUrl}
                                className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>  
                                <Link
                                    href={login()}
                                    className="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                                >
                                    Inloggen
                                </Link>
                                <Link
                                    href={register()}
                                    className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                                >
                                    Registeren
                                </Link>
                            </>
                        )}
                    </nav>
                </header>
                <h1
                    className="text-left     text-2xl font-bold text-[#1b1b18] dark:text-[#5758b3]"
                    
                >
                    Welkom bij ons geweldige Tandartspraktijk! We zijn verheugd om u te verwelkomen op onze website.<br />
                     Hier kunt u gemakkelijk een afspraak maken, meer te weten komen over onze diensten en ons team van deskundige tandartsen ontmoeten. <br />
                     Uw glimlach is onze prioriteit, en we kijken ernaar uit om u te helpen bij het bereiken van een gezonde en stralende lach!
                </h1>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </>
    );
}
