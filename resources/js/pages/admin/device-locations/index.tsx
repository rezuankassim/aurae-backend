import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/admin/device-locations';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { columns } from './columns';
import { DataTable } from './data-table';

interface UserDevice {
    id: number;
    udid: string;
    os: string | null;
    manufacturer: string | null;
    model: string | null;
}

interface DeviceLocation {
    id: number;
    user_device_id: number;
    latitude: string | null;
    longitude: string | null;
    accuracy: string | null;
    api_endpoint: string | null;
    ip_address: string | null;
    created_at: string;
    user_device: UserDevice;
}

interface PaginatedLocations {
    data: DeviceLocation[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Device {
    id: number;
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
