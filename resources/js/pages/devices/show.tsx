import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { format } from 'date-fns';

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
    last_logged_in_at: string | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    device: Device;
}

export default function DeviceShow({ device }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Devices',
            href: '/devices',
        },
        {
            title: device.name,
            href: `/devices/${device.id}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Device - ${device.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-start justify-between">
                    <Heading title={device.name} description={`UUID: ${device.uuid}`} />
                    {device.status === 1 ? (
                        <Badge className="bg-green-100 text-green-800">Active</Badge>
                    ) : (
                        <Badge variant="secondary">Inactive</Badge>
                    )}
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Device Information</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-start gap-6">
                                <div className="h-32 w-32 flex-shrink-0 overflow-hidden rounded-lg border bg-muted">
                                    <img src={`/${device.thumbnail}`} alt={device.name} className="h-full w-full object-cover" />
                                </div>
                                <div className="flex-1 space-y-4">
                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <Label className="text-muted-foreground">Device Name</Label>
                                            <p className="font-semibold">{device.name}</p>
                                        </div>
                                        <div>
                                            <Label className="text-muted-foreground">Device UUID</Label>
                                            <p className="font-mono text-sm">{device.uuid}</p>
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <Label className="text-muted-foreground">Device Plan</Label>
                                            <p className="font-semibold">{device.device_plan}</p>
                                        </div>
                                        <div>
                                            <Label className="text-muted-foreground">Status</Label>
                                            <p>
                                                {device.status === 1 ? (
                                                    <Badge className="bg-green-100 text-green-800">Active</Badge>
                                                ) : (
                                                    <Badge variant="secondary">Inactive</Badge>
                                                )}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Activity</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label className="text-muted-foreground">Last Used</Label>
                                <p className="font-medium">
                                    {device.last_used_at ? format(new Date(device.last_used_at), 'MMM d, yyyy HH:mm') : 'Never'}
                                </p>
                            </div>
                            <div>
                                <Label className="text-muted-foreground">Last Logged In</Label>
                                <p className="font-medium">
                                    {device.last_logged_in_at ? format(new Date(device.last_logged_in_at), 'MMM d, yyyy HH:mm') : 'Never'}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Subscription Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label className="text-muted-foreground">Started At</Label>
                                <p className="font-medium">{device.started_at ? format(new Date(device.started_at), 'MMM d, yyyy') : '-'}</p>
                            </div>
                            <div>
                                <Label className="text-muted-foreground">Should End At</Label>
                                <p className="font-medium">{device.should_end_at ? format(new Date(device.should_end_at), 'MMM d, yyyy') : '-'}</p>
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-4 border-t pt-4">
                            <div>
                                <Label className="text-muted-foreground">Created At</Label>
                                <p className="font-medium">{format(new Date(device.created_at), 'MMM d, yyyy HH:mm')}</p>
                            </div>
                            <div>
                                <Label className="text-muted-foreground">Updated At</Label>
                                <p className="font-medium">{format(new Date(device.updated_at), 'MMM d, yyyy HH:mm')}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
