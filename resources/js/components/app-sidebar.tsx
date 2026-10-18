import {Link, usePage} from '@inertiajs/react';
import {PageProps} from '@inertiajs/core';
import {BookOpen, CalendarClock, CalendarDays, ClipboardList, FolderGit2, LayoutGrid, Stethoscope, UserCog, Users} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import {NavFooter} from '@/components/nav-footer';
import {NavMain} from '@/components/nav-main';
import {NavUser} from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import {dashboard} from '@/routes';
import admin from '@/routes/admin';
import type {NavItem} from '@/types';
import type {User} from '@/types/Interfaces';

interface Props extends PageProps {
    auth: User;
}

export function AppSidebar() {
    const {auth} = usePage().props;
    // Admins use their own versions of the dashboard, appointment and treatment pages
    const dashboardUrl = auth.isAdmin ? admin.dashboard() : dashboard();

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboardUrl,
            icon: LayoutGrid,
        },
        ...(auth.isAdmin
                ? [
                    {
                        title: 'Afspraken',
                        href: admin.appointments.index(),
                        icon: CalendarClock
                    },
                    {
                        title: 'Behandelingen',
                        href: admin.treatments.index(),
                        icon: ClipboardList
                    }
                ] : []
        ),
        ...(auth.user.role_id != 1 && !auth.isAdmin
                ? [
                    {
                        title: 'Afspraken',
                        href: '/appointments',
                        icon: LayoutGrid
                    },
					{
						title: 'Behandelingen',
						href: '/treatments',
						icon: LayoutGrid
					}
                ] : []
        ),
        ...(auth.isAdmin
                ? [
                    {
                        title: 'Gebruikersbeheer',
                        href: admin.users(),
                        icon: UserCog
                    },
                    {
                        title: 'Klanten',
                        href: admin.customers.index(),
                        icon: Users
                    },
                    {
                        title: 'Medewerkers',
                        href: admin.employees.index(),
                        icon: Stethoscope
                    },
                    {
                        title: 'Agenda',
                        href: admin.calendar.index(),
                        icon: CalendarDays
                    }
                ] : []
        )
    ];

    const footerNavItems: NavItem[] = [
        {
            title: 'Repository',
            href: 'https://github.com/laravel/react-starter-kit',
            icon: FolderGit2,
        },
        {
            title: 'Documentation',
            href: 'https://laravel.com/docs/starter-kits#react',
            icon: BookOpen,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboardUrl} prefetch>
                                <AppLogo/>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems}/>
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto"/>
                <NavUser/>
            </SidebarFooter>
        </Sidebar>
    );
}
