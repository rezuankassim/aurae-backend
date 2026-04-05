import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { format } from 'date-fns';
import { ExternalLink, MapPin } from 'lucide-react';

interface DeviceLocation {
    id: number;
    latitude: string | null;
    longitude: string | null;
    accuracy: string | null;
    created_at: string;
}

interface Device {
    id: string;
    name: string;
    uuid: string;
    status: number;
    latest_location: DeviceLocation | null;
}

interface Address {
    id: number;
    is_default: boolean;
    type: number;
    name: string;
    phone: string | null;
    line1: string;
    line2: string | null;
    line3: string | null;
    city: string;
    state: string;
    postal_code: string;
    country: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    phone_country_code: string | null;
    addresses: Address[];
}

interface ChangeHistory {
    changed_at: string;
    user_id: number;
    previous_maintenance_requested_at: string | null;
    new_maintenance_requested_at: string | null;
    previous_factory_maintenance_requested_at: string | null;
    new_factory_maintenance_requested_at: string | null;
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
    service_type: string | null;
    is_factory_approved: boolean;
    is_user_approved: boolean;
    created_at: string;
    updated_at: string;
    requested_at_changes_formatted: ChangeHistory[];
}

interface Props {
    maintenance: DeviceMaintenance;
}

export default function AdminDeviceMaintenanceShow({ maintenance }: Props) {
    const { data, setData, put, processing } = useForm({
        status: maintenance.status,
        factory_maintenance_requested_at: maintenance.factory_maintenance_requested_at || '',
        is_factory_approved: maintenance.is_factory_approved,
    });

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Device Maintenances',
            href: '/admin/device-maintenances',
        },
        {
            title: `Maintenance #${maintenance.id}`,
            href: `/admin/device-maintenances/${maintenance.id}`,
        },
    ];

    const getStatusBadge = (status: number) => {
        const statusMap: Record<number, { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' }> = {
            0: { label: 'Pending', variant: 'secondary' },
            1: { label: 'Pending Factory', variant: 'default' },
            2: { label: 'In Progress', variant: 'outline' },
            3: { label: 'Completed', variant: 'default' },
        };

        const config = statusMap[status] || { label: 'Unknown', variant: 'secondary' };
        return <Badge variant={config.variant}>{config.label}</Badge>;
    };

    const handleUpdateStatus = () => {
        put(`/admin/device-maintenances/${maintenance.id}/status`, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Maintenance #${maintenance.id}`} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-start justify-between">
                    <div>
                        <Heading
                            title={`Maintenance Request #${maintenance.id}`}
                            description={`Requested by ${maintenance.user.name} on ${format(new Date(maintenance.created_at), 'MMMM d, yyyy')}`}
                        />
                    </div>
                    {getStatusBadge(maintenance.status)}
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Device Last Known Location</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {maintenance.device?.latest_location?.latitude && maintenance.device?.latest_location?.longitude ? (
                                    <div className="space-y-3">
                                        <div className="flex items-center gap-2">
                                            <MapPin className="h-4 w-4 text-muted-foreground" />
                                            <span className="font-mono text-sm">
                                                {parseFloat(maintenance.device.latest_location.latitude).toFixed(6)},{' '}
                                                {parseFloat(maintenance.device.latest_location.longitude).toFixed(6)}
                                            </span>
                                            {maintenance.device.latest_location.accuracy && (
                                                <span className="text-xs text-muted-foreground">
                                                    ±{parseFloat(maintenance.device.latest_location.accuracy).toFixed(1)}m
                                                </span>
                                            )}
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <p className="text-xs text-muted-foreground">
                                                Last logged:{' '}
                                                {format(new Date(maintenance.device.latest_location.created_at), 'MMM d, yyyy HH:mm')}
                                            </p>
                                            <a
                                                href={`https://www.google.com/maps?q=${maintenance.device.latest_location.latitude},${maintenance.device.latest_location.longitude}`}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="flex items-center gap-1 text-sm text-primary hover:text-primary/80"
                                            >
                                                <ExternalLink className="h-3 w-3" />
                                                View on Map
                                            </a>
                                        </div>
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">No location data available for this device.</p>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Customer Contact & Location</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <Label className="text-muted-foreground">Contact Number</Label>
                                    <p className="font-medium">
                                        {maintenance.user.phone
                                            ? `${maintenance.user.phone_country_code ? '+' + maintenance.user.phone_country_code + ' ' : ''}${maintenance.user.phone}`
                                            : '-'}
                                    </p>
                                </div>

                                {(() => {
                                    const address =
                                        maintenance.user.addresses.find((a) => a.is_default) ?? maintenance.user.addresses[0];

                                    if (!address) {
                                        return (
                                            <div className="border-t pt-4">
                                                <Label className="text-muted-foreground">Address</Label>
                                                <p className="text-sm text-muted-foreground">No address on file</p>
                                            </div>
                                        );
                                    }

                                    const lines = [
                                        address.line1,
                                        address.line2,
                                        address.line3,
                                        [address.city, address.state, address.postal_code].filter(Boolean).join(', '),
                                        address.country,
                                    ].filter(Boolean);

                                    return (
                                        <div className="border-t pt-4">
                                            <Label className="text-muted-foreground">
                                                Address{address.is_default ? ' (Default)' : ''}
                                            </Label>
                                            <div className="mt-1 space-y-0.5">
                                                {lines.map((line, i) => (
                                                    <p key={i} className="font-medium">
                                                        {line}
                                                    </p>
                                                ))}
                                            </div>
                                        </div>
                                    );
                                })()}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Request Details</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <Label className="text-muted-foreground">User</Label>
                                        <p className="font-medium">{maintenance.user.name}</p>
                                        <p className="text-sm text-muted-foreground">{maintenance.user.email}</p>
                                    </div>
                                    <div>
                                        <Label className="text-muted-foreground">Device</Label>
                                        <p className="font-medium">{maintenance.device?.name || '-'}</p>
                                        <p className="text-sm text-muted-foreground">{maintenance.device?.uuid || '-'}</p>
                                    </div>
                                </div>

                                <div className="space-y-2 border-t pt-4">
                                    <div>
                                        <Label className="text-muted-foreground">Service Type</Label>
                                        <p className="font-medium">{maintenance.service_type || '-'}</p>
                                    </div>

                                    <div>
                                        <Label className="text-muted-foreground">User Requested Date & Time</Label>
                                        <p className="font-medium">{format(new Date(maintenance.maintenance_requested_at), 'MMMM d, yyyy HH:mm')}</p>
                                    </div>

                                    {maintenance.factory_maintenance_requested_at && (
                                        <div>
                                            <Label className="text-muted-foreground">Factory Proposed Date & Time</Label>
                                            <p className="font-medium">
                                                {format(new Date(maintenance.factory_maintenance_requested_at), 'MMMM d, yyyy HH:mm')}
                                            </p>
                                        </div>
                                    )}
                                </div>

                                <div className="grid grid-cols-2 gap-4 border-t pt-4">
                                    <div>
                                        <Label className="text-muted-foreground">Factory Approved</Label>
                                        <p className="font-medium">{maintenance.is_factory_approved ? 'Yes' : 'No'}</p>
                                    </div>
                                    <div>
                                        <Label className="text-muted-foreground">User Approved</Label>
                                        <p className="font-medium">{maintenance.is_user_approved ? 'Yes' : 'No'}</p>
                                    </div>
                                </div>

                                <div className="border-t pt-4">
                                    <Label className="text-muted-foreground">Last Updated</Label>
                                    <p className="font-medium">{format(new Date(maintenance.updated_at), 'MMMM d, yyyy HH:mm')}</p>
                                </div>
                            </CardContent>
                        </Card>

                        {maintenance.requested_at_changes_formatted && maintenance.requested_at_changes_formatted.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Change History</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        {maintenance.requested_at_changes_formatted.map((change, index) => (
                                            <div key={index} className="rounded-lg border p-4">
                                                <p className="text-sm text-muted-foreground">
                                                    {format(new Date(change.changed_at), 'MMMM d, yyyy HH:mm')}
                                                </p>
                                                {change.previous_maintenance_requested_at && change.new_maintenance_requested_at && (
                                                    <div className="mt-2">
                                                        <p className="text-sm">
                                                            <span className="text-muted-foreground">User requested: </span>
                                                            <span className="line-through">
                                                                {format(new Date(change.previous_maintenance_requested_at), 'MMM d, yyyy HH:mm')}
                                                            </span>
                                                            {' → '}
                                                            <span className="font-medium">
                                                                {format(new Date(change.new_maintenance_requested_at), 'MMM d, yyyy HH:mm')}
                                                            </span>
                                                        </p>
                                                    </div>
                                                )}
                                                {change.new_factory_maintenance_requested_at && (
                                                    <div className="mt-2">
                                                        <p className="text-sm">
                                                            <span className="text-muted-foreground">Factory proposed: </span>
                                                            <span className="font-medium">
                                                                {format(new Date(change.new_factory_maintenance_requested_at), 'MMM d, yyyy HH:mm')}
                                                            </span>
                                                        </p>
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    <div className="lg:col-span-1">
                        <Card>
                            <CardHeader>
                                <CardTitle>Update Status</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label>Status</Label>
                                    <Select value={data.status.toString()} onValueChange={(value) => setData('status', parseInt(value))}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="0">Pending</SelectItem>
                                            <SelectItem value="1">Pending Factory</SelectItem>
                                            <SelectItem value="2">In Progress</SelectItem>
                                            <SelectItem value="3">Completed</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label>Factory Proposed Date & Time</Label>
                                    <Input
                                        type="datetime-local"
                                        value={data.factory_maintenance_requested_at}
                                        onChange={(e) => setData('factory_maintenance_requested_at', e.target.value)}
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        Proposing a new time will set status to "Pending" for user approval
                                    </p>
                                </div>

                                <div className="flex items-center justify-between space-x-2">
                                    <Label htmlFor="factory-approved">Factory Approved</Label>
                                    <Switch
                                        id="factory-approved"
                                        checked={data.is_factory_approved}
                                        onCheckedChange={(checked) => setData('is_factory_approved', checked)}
                                    />
                                </div>

                                <Button className="w-full" onClick={handleUpdateStatus} disabled={processing}>
                                    Update Status
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
