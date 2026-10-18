import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    CalendarDays,
    CalendarPlus,
    FolderGit2,
    History,
    LayoutGrid,
    UserRound,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import * as appointments from '@/routes/appointments';
import { edit as editProfile } from '@/routes/profile';
import { history } from '@/routes/treatments';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { auth } = usePage().props;
    const dashboardUrl = dashboard();

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboardUrl,
            icon: LayoutGrid,
        },
        {
            title: 'Agenda',
            href: appointments.index(),
            icon: CalendarDays,
        },
        ...(auth.isCustomer
            ? [
                  {
                      title: 'Afspraak maken',
                      href: appointments.create(),
                      icon: CalendarPlus,
                  },
                  {
                      title: 'Behandelingsgeschiedenis',
                      href: history(),
                      icon: History,
                  },
              ]
            : []),
        {
            title: 'Mijn profiel',
            href: editProfile(),
            icon: UserRound,
        },
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
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
