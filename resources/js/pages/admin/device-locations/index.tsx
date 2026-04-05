import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/admin/device-locations';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { columns } from './columns';
import { DataTable } from './data-table';

interface IoTDevice {
    id: string;
    name: string;
    uuid: string;
}

interface DeviceLocation {
    id: number;
    device_id: string | null;
    latitude: string | null;
    longitude: string | null;
    accuracy: string | null;
    api_endpoint: string | null;
    ip_address: string | null;
    created_at: string;
    device: IoTDevice | null;
}

interface PaginatedLocations {
    data: DeviceLocation[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Device {
    id: string;
    label: string;
}

interface Props {
    locations: PaginatedLocations;
    devices: Device[];
    filters: {
        device_id: string;
        from: string;
        to: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Device Locations',
        href: index().url,
    },
];

export default function AdminDeviceLocationsIndex({ locations, devices, filters }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Device Locations" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Device Locations" description="Track GPS locations of devices when they call APIs" />
                </div>

                <DataTable columns={columns} data={locations.data} devices={devices} filters={filters} />
            </div>
        </AppLayout>
    );
}
