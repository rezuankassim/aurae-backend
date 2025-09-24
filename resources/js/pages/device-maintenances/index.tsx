import AppLayout from '@/layouts/app-layout';
import { DeviceMaintenance, type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import deviceMaintenance, { create } from '@/routes/device-maintenance';
import { columns } from './columns';
import { DataTable } from './data-table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Device Maintenances',
        href: deviceMaintenance.index().url,
    },
];

export default function DeviceMaintenanceIndex({ deviceMaintenances }: { deviceMaintenances: DeviceMaintenance[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Device Maintenances" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Device Maintenances" description="View your device maintenances" />

                    <Button className="mb-6" asChild>
                        <Link href={create().url}>Create new maintenance</Link>
                    </Button>
                </div>

                <DataTable columns={columns} data={deviceMaintenances} />
            </div>
        </AppLayout>
    );
}
