import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/admin/notifications';
import { AdminNotification, type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { columns } from './columns';
import { DataTable } from './data-table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Notifications',
        href: index().url,
    },
];

export default function AdminNotificationsIndex({ notifications }: { notifications: AdminNotification[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notifications" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Notifications" description="View all admin notifications" />
                </div>

                <DataTable columns={columns} data={notifications} />
            </div>
        </AppLayout>
    );
}
