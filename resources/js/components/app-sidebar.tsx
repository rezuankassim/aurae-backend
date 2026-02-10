import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as indexDeviceLocations } from '@/routes/admin/device-locations';
import { index as indexAdminDeviceMaintenances } from '@/routes/admin/device-maintenances';
import { index as indexFAQs } from '@/routes/admin/faqs';
import { index as indexFeedbacks } from '@/routes/admin/feedbacks';
import { index as indexFirebaseTest } from '@/routes/admin/firebase-test';
import { edit as editGeneralSettings } from '@/routes/admin/general-settings';
import { index as indexAdminHealthReports } from '@/routes/admin/health-reports';
import { index as indexKnowledge } from '@/routes/admin/knowledge';
import { edit as editLegalSettings } from '@/routes/admin/legal-settings';
import { index as indexMachines } from '@/routes/admin/machines';
import { index as indexMaintenanceBanners } from '@/routes/admin/maintenance-banners';
import { index as indexMarketplaceBanners } from '@/routes/admin/marketplace-banners';
import { index as indexMusic } from '@/routes/admin/music';
import { index as indexNews } from '@/routes/admin/news';
import { edit as editSocialMedia } from '@/routes/admin/social-media';
import { index as indexSubscriptions } from '@/routes/admin/subscription';
import { index as indexTherapies } from '@/routes/admin/therapies';
import { index as indexUserSubscriptions } from '@/routes/admin/user-subscriptions';
import { index as indexUsers } from '@/routes/admin/users';
import { index as indexWebSocketTest } from '@/routes/admin/websocket-test';
import { index as indexCustomTherapies } from '@/routes/custom-therapies';
import { index as indexDeviceMaintenance } from '@/routes/device-maintenance';
import { index } from '@/routes/devices';
import { index as indexHealthReports } from '@/routes/health-reports';
import { index as indexNewsCustomer } from '@/routes/news';
import { index as indexUsageHistory } from '@/routes/usage-history';
import { SharedData, type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    BellRing,
    BookHeart,
    BookOpenText,
    CogIcon,
    Construction,
    CreditCard,
    FileBox,
    HardDrive,
    ImageIcon,
    LayoutGrid,
    MapPin,
    MessageCircleQuestion,
    Music,
    Newspaper,
    Radio,
    Scale,
    ShoppingCart,
    TabletSmartphone,
    UserCheck,
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
        title: 'News',
        href: indexNewsCustomer(),
        icon: Newspaper,
    },
];

const managementNavItems: NavItem[] = [
    {
        title: 'Ecommerce Platform',
        href: '/lunar',
        icon: ShoppingCart,
        external: true,
    },
    {
        title: 'Users',
        href: indexUsers(),
        icon: UsersIcon,
    },
    {
        title: 'Health Reports',
        href: indexAdminHealthReports(),
        icon: FileBox,
    },
    {
        title: 'Device Maintenances',
        href: indexAdminDeviceMaintenances(),
        icon: Construction,
    },
    {
        title: 'Maintenance Banners',
        href: indexMaintenanceBanners(),
        icon: ImageIcon,
    },
    {
        title: 'Marketplace Banners',
        href: indexMarketplaceBanners(),
        icon: ImageIcon,
    },
    {
        title: 'Device Locations',
        href: indexDeviceLocations(),
        icon: MapPin,
    },
    {
        title: 'Therapies',
        href: indexTherapies(),
        icon: BookHeart,
    },
    {
        title: 'Music',
        href: indexMusic(),
        icon: Music,
    },
    {
        title: 'News',
        href: indexNews(),
        icon: Newspaper,
    },
    {
        title: 'Subscriptions',
        href: indexSubscriptions(),
        icon: CreditCard,
    },
    {
        title: 'User Subscriptions',
        href: indexUserSubscriptions(),
        icon: UserCheck,
    },
    {
        title: 'Machines',
        href: indexMachines(),
        icon: HardDrive,
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
    {
        title: 'General Settings',
        href: editGeneralSettings(),
        icon: CogIcon,
    },
    {
        title: 'Legal Settings',
        href: editLegalSettings(),
        icon: Scale,
    },
    {
        title: 'WebSocket Test',
        href: indexWebSocketTest(),
        icon: Radio,
    },
    {
        title: 'Firebase Test',
        href: indexFirebaseTest(),
        icon: BellRing,
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
