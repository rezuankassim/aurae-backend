import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/admin/device-maintenances';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { columns } from './columns';
import { DataTable } from './data-table';

interface Device {
    id: string;
    name: string;
    uuid: string;
}

interface User {
    id: number;
    name: string;
    email: string;
}

interface DeviceMaintenance {
    id: number;
    status: number;
    user_id: number;
    device_id: string;
    device: Device;
    user: User;
    maintenance_requested_at: string;
    factory_maintenance_requested_at: string | null;
    is_factory_approved: boolean;
    is_user_approved: boolean;
    created_at: string;
}

interface PaginatedMaintenances {
    data: DeviceMaintenance[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    maintenances: PaginatedMaintenances;
    filters: {
        status: string;
        search: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Device Maintenances',
        href: index().url,
    },
];

export default function AdminDeviceMaintenancesIndex({ maintenances, filters }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Device Maintenances" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Device Maintenances" description="Manage device maintenance requests" />
                </div>

                <DataTable columns={columns} data={maintenances.data} filters={filters} />
            </div>
        </AppLayout>
    );
}
