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

interface Device {
    id: string;
    name: string;
    uuid: string;
    status: number;
}

interface User {
    id: number;
    name: string;
    email: string;
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
