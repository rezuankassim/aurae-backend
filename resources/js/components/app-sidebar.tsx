import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as indexCollectionGroups } from '@/routes/admin/collection-groups';
import { index as indexFAQs } from '@/routes/admin/faqs';
import { index as indexFeedbacks } from '@/routes/admin/feedbacks';
import { index as indexKnowledge } from '@/routes/admin/knowledge';
import { index as indexNews } from '@/routes/admin/news';
import { index as indexProducts } from '@/routes/admin/products';
import { edit as editSocialMedia } from '@/routes/admin/social-media';
import { index as indexTherapies } from '@/routes/admin/therapies';
import { index as indexUsers } from '@/routes/admin/users';
import { index as indexCustomTherapies } from '@/routes/custom-therapies';
import { index as indexDeviceMaintenance } from '@/routes/device-maintenance';
import { index } from '@/routes/devices';
import { index as indexHealthReports } from '@/routes/health-reports';
import { index as indexNewsCustomer } from '@/routes/news';
import { index as indexOrderHistory } from '@/routes/order-history';
import { index as indexUsageHistory } from '@/routes/usage-history';
import { SharedData, type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    BookHeart,
    BookOpenText,
    Construction,
    FileBox,
    FileClock,
    LayoutGrid,
    MessageCircleQuestion,
    Newspaper,
    ShoppingBag,
    TabletSmartphone,
    UsersIcon,
    Waypoints,
} from 'lucide-react';
import AppLogo from './app-logo';
import { NavManagement } from './nav-management';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Personal Health',
        icon: FileBox,
        children: [
            {
                title: 'Health Reports',
                href: indexHealthReports(),
            },
            {
                title: 'Usage History',
                href: indexUsageHistory(),
            },
        ],
    },
    {
        title: 'Custom Therapies',
        icon: BookHeart,
        href: indexCustomTherapies(),
    },
    {
        title: 'Device Maintenances',
        href: indexDeviceMaintenance(),
        icon: Construction,
    },
    {
        title: 'Devices',
        href: index(),
        icon: TabletSmartphone,
    },
    {
        title: 'Order History',
        href: indexOrderHistory(),
        icon: FileClock,
    },
    {
        title: 'News',
        href: indexNewsCustomer(),
        icon: Newspaper,
    },
];

const managementNavItems: NavItem[] = [
    {
        title: 'Users',
        href: indexUsers(),
        icon: UsersIcon,
    },
    {
        title: 'Therapies',
        href: indexTherapies(),
        icon: BookHeart,
    },
    {
        title: 'News',
        href: indexNews(),
        icon: Newspaper,
    },
    {
        title: 'Shop',
        icon: ShoppingBag,
        children: [
            {
                title: 'Products',
                href: indexProducts(),
            },
            {
                title: 'Collection Groups',
                href: indexCollectionGroups(),
            },
        ],
    },
    {
        title: 'Social Media',
        href: editSocialMedia(),
        icon: Waypoints,
    },
    {
        title: 'Knowledge Center Management',
        href: indexKnowledge(),
        icon: BookOpenText,
    },
    {
        title: 'FAQs',
        href: indexFAQs(),
        icon: MessageCircleQuestion,
    },
    {
        title: 'Feedbacks',
        href: indexFeedbacks(),
        icon: MessageCircleQuestion,
    },
];

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                {auth.user.is_admin ? <NavManagement items={managementNavItems} /> : null}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
