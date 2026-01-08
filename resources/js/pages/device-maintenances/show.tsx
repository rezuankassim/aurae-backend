import AppLayout from '@/layouts/app-layout';
import { DeviceMaintenance, type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

import Heading from '@/components/heading';
import { index } from '@/routes/device-maintenance';

import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import dayjs from 'dayjs';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Device Maintenances',
        href: index().url,
    },
    {
        title: 'Show maintenance',
        href: '#',
    },
];

export default function DeviceMaintenanceShow({ deviceMaintenance }: { deviceMaintenance: DeviceMaintenance }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit maintenance" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="Edit maintenance" description="Edit current maintenance to fit your schedule" />

                <div className="space-y-6">
                    <div className="grid gap-2">
                        <h2 className="text-sm font-medium">Status</h2>

                        {deviceMaintenance.status === 0 ? <Badge>Pending</Badge> : null}
                        {deviceMaintenance.status === 1 ? <Badge className="bg-cyan-200 text-cyan-900">Pending Factory Approval</Badge> : null}
                        {deviceMaintenance.status === 2 ? <Badge className="bg-amber-200 text-amber-900">In Progress</Badge> : null}
                        {deviceMaintenance.status === 3 ? <Badge className="bg-green-200 text-green-900">Completed</Badge> : null}
                    </div>

                    <div className="grid grid-flow-col gap-2">
                        <div className="grid gap-2">
                            <h2 className="text-sm font-medium">Factory schedule date</h2>

                            <span>
                                {deviceMaintenance.factory_maintenance_requested_at
                                    ? dayjs(deviceMaintenance.factory_maintenance_requested_at).format('DD MMM YYYY')
                                    : '-'}
                            </span>
                        </div>

                        <div className="grid gap-2">
                            <h2 className="text-sm font-medium">Factory schedule time</h2>

                            <span>
                                {deviceMaintenance.factory_maintenance_requested_at
                                    ? dayjs(deviceMaintenance.factory_maintenance_requested_at).format('hh:mm:ss a')
                                    : '-'}
                            </span>
                        </div>
                    </div>

                    {deviceMaintenance.device && (
                        <>
                            <h2 className="mb-4 text-sm font-medium">Device Information</h2>
                            <div className="flex items-start gap-4">
                                <div className="h-20 w-20 flex-shrink-0 overflow-hidden rounded-lg border bg-muted">
                                    <img
                                        src={`/${deviceMaintenance.device.thumbnail}`}
                                        alt={deviceMaintenance.device.name}
                                        className="h-full w-full object-cover"
                                    />
                                </div>
                                <div className="grid flex-1 grid-flow-col grid-cols-3 space-y-2">
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">Device Name</p>
                                        <p className="font-semibold">{deviceMaintenance.device.name}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">Device UUID</p>
                                        <p className="font-mono text-sm">{deviceMaintenance.device.uuid}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">Device Plan</p>
                                        <p className="font-semibold">{deviceMaintenance.device.device_plan}</p>
                                    </div>
                                </div>
                            </div>
                        </>
                    )}

                    <div className="grid grid-flow-col gap-2">
                        <div className="grid gap-2">
                            <h2 className="text-sm font-medium">Maintenance schedule date</h2>

                            <span>
                                {deviceMaintenance.maintenance_requested_at
                                    ? dayjs(deviceMaintenance.maintenance_requested_at).format('DD MMM YYYY')
                                    : '-'}
                            </span>
                        </div>

                        <div className="grid gap-2">
                            <h2 className="text-sm font-medium">Maintenance schedule time</h2>

                            <span>
                                {deviceMaintenance.maintenance_requested_at
                                    ? dayjs(deviceMaintenance.maintenance_requested_at).format('hh:mm:ss a')
                                    : '-'}
                            </span>
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <h2 className="text-sm font-medium">Maintenance date changes</h2>

                        <div className="grid gap-2">
                            {deviceMaintenance.requested_at_changes_formatted.map((item) => (
                                <Card>
                                    <CardContent className="grid grid-cols-2 grid-rows-2 gap-2">
                                        <p className="text-sm font-medium">Changed at</p>

                                        <span>{dayjs(item.changed_at).format('DD MMM YYYY, HH:mm')}</span>

                                        <p className="text-sm font-medium">Previous maintenance date time</p>

                                        <span>{dayjs(item.previous_maintenance_requested_at).format('DD MMM YYYY, HH:mm')}</span>

                                        <p className="text-sm font-medium">New maintenance date time</p>

                                        <span>{dayjs(item.new_maintenance_requested_at).format('DD MMM YYYY, HH:mm')}</span>

                                        <p className="text-sm font-medium">Previous factory date time</p>

                                        <span>{dayjs(item.previous_factory_maintenance_requested_at).format('DD MMM YYYY, HH:mm')}</span>

                                        <p className="text-sm font-medium">New factory date time</p>

                                        <span>
                                            {item.new_factory_maintenance_requested_at
                                                ? dayjs(item.new_factory_maintenance_requested_at).format('DD MMM YYYY, HH:mm')
                                                : '-'}
                                        </span>

                                        <p className="text-sm font-medium">User</p>

                                        <span>
                                            {item.user.name} ({item.user.email})
                                        </span>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
