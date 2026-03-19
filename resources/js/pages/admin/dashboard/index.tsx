import Heading from '@/components/heading';
import { TopDevicesChart } from '@/components/top-devices-chart';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/admin/dashboard';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { TabletSmartphone, Users, Wifi } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: index().url,
    },
];

type TopSubscriptionItem = { name: string; count: number };
type ChartFilter = { range: string; dateFrom?: string | null; dateTo?: string | null };

export default function AdminDashboard({
    totalUsers,
    totalDevices,
    onlineDevices,
    topSubscriptions,
    chartFilter,
}: {
    totalUsers: number;
    totalDevices: number;
    onlineDevices: number;
    topSubscriptions: TopSubscriptionItem[];
    chartFilter: ChartFilter;
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="Dashboard" description="Overview of the system" />

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Users</CardTitle>
                            <Users className="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{totalUsers.toLocaleString()}</div>
                            <p className="text-muted-foreground text-xs">Registered users</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Devices</CardTitle>
                            <TabletSmartphone className="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{totalDevices.toLocaleString()}</div>
                            <p className="text-muted-foreground text-xs">All registered devices</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Online Devices</CardTitle>
                            <Wifi className="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{onlineDevices.toLocaleString()}</div>
                            <p className="text-muted-foreground text-xs">Active devices</p>
                        </CardContent>
                    </Card>
                </div>

                <Card className="@container/card">
                    <TopDevicesChart data={topSubscriptions} filter={chartFilter} />
                </Card>
            </div>
        </AppLayout>
    );
}
