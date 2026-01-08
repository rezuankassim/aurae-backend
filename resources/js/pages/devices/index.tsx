import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { columns } from './columns';
import { DataTable } from './data-table';

interface Device {
    id: string;
    name: string;
    uuid: string;
    status: number;
    thumbnail: string;
    device_plan: string;
    started_at: string | null;
    should_end_at: string | null;
    last_used_at: string | null;
    created_at: string;
}

interface Props {
    devices: Device[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Devices',
        href: '/devices',
    },
];

export default function DevicesIndex({ devices }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Devices" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="My Devices" description="View and manage your registered devices" />

                <DataTable columns={columns} data={devices} />
            </div>
        </AppLayout>
    );
}
