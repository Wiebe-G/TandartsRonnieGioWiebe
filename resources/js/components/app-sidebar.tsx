import { Link, usePage } from '@inertiajs/react';
import {
	BookOpen,
	CalendarClock,
	CalendarDays,
	CalendarPlus,
	ClipboardList,
	ReceiptText,
	FolderGit2,
	History,
	LayoutGrid,
	MessageSquare,
	Stethoscope,
	UserCog,
	UserRound,
	Users,
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
import admin from '@/routes/admin';
import * as appointments from '@/routes/appointments';
import { feedback as customerFeedback } from '@/routes/customer';
import * as customerInvoices from '@/routes/customer/invoices';
import { edit as editProfile } from '@/routes/profile';
import { history } from '@/routes/treatments';
import type { NavItem } from '@/types';

export function AppSidebar() {
	const { auth } = usePage().props;
	// Admins use their own versions of the dashboard, appointment and treatment pages
	const dashboardUrl = auth.isAdmin ? admin.dashboard() : dashboard();

	const mainNavItems: NavItem[] = [
		{
			title: 'Dashboard',
			href: dashboardUrl,
			icon: LayoutGrid,
		},
		// Customers: their own agenda, booking and history
		...(auth.isCustomer
			? [
					{
						title: 'Agenda',
						href: appointments.index(),
						icon: CalendarDays,
					},
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
					{
						title: 'Mijn facturen',
						href: customerInvoices.index(),
						icon: ReceiptText,
					},
					{
						title: 'Mijn feedback',
						href: customerFeedback(),
						icon: MessageSquare,
					},
				]
			: []),
		// All staff (admins included) share the same appointment and treatment pages
		...(!auth.isCustomer
			? [
					{
						title: 'Afspraken',
						href: admin.appointments.index(),
						icon: CalendarClock,
					},
					{
						title: 'Behandelingen',
						href: admin.treatments.index(),
						icon: ClipboardList,
					},
				]
			: []),
		// Sending invoices: assistants and admins only
		...(auth.canPlanAndInvoice
			? [
					{
						title: 'Facturen',
						href: admin.invoices.index(),
						icon: ReceiptText,
					},
				]
			: []),
		...(auth.isAdmin
			? [
					{
						title: 'Gebruikersbeheer',
						href: admin.users(),
						icon: UserCog,
					},
					{
						title: 'Klanten',
						href: admin.customers.index(),
						icon: Users,
					},
					{
						title: 'Medewerkers',
						href: admin.employees.index(),
						icon: Stethoscope,
					},
					{
						title: 'Agenda',
						href: admin.calendar.index(),
						icon: CalendarDays,
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
