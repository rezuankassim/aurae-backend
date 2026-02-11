import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { edit, index, show, unbind } from '@/routes/admin/machines';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { format } from 'date-fns';
import { ImageIcon, Link2Off, Pencil } from 'lucide-react';

interface Machine {
    id: string;
    serial_number: string;
    name: string;
    status: number;
    thumbnail_url: string | null;
    detail_image_url: string | null;
    last_used_at: string | null;
    last_logged_in_at: string | null;
    created_at: string;
    user?: {
        id: number;
        name: string;
        email: string;
        phone?: string | null;
    } | null;
    device?: {
        id: string;
        name: string;
        uuid: string;
    } | null;
    user_subscription?: {
        id: number;
        status: string;
        starts_at: string | null;
        ends_at: string | null;
        subscription: {
            id: number;
            title: string;
            price: string;
        };
    } | null;
}

interface Props {
    machine: Machine;
}

export default function MachineShow({ machine }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Machines',
            href: index().url,
        },
        {
            title: machine.serial_number,
            href: show(machine).url,
        },
    ];

    const handleUnbind = () => {
        if (confirm('Are you sure you want to unbind this machine?')) {
            router.post(unbind(machine).url);
        }
    };

    const getStatusBadge = (status: string) => {
        const statusMap: Record<string, { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' }> = {
            pending: { label: 'Pending', variant: 'secondary' },
            active: { label: 'Active', variant: 'default' },
            expired: { label: 'Expired', variant: 'outline' },
            cancelled: { label: 'Cancelled', variant: 'destructive' },
        };

        const config = statusMap[status] || { label: status, variant: 'secondary' };
        return <Badge variant={config.variant}>{config.label}</Badge>;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Machine - ${machine.serial_number}`} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl px-4 py-6">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <Heading title={machine.serial_number} description={machine.name} />
                    <div className="flex items-center gap-2">
                        <Badge variant={machine.status === 1 ? 'default' : 'secondary'}>{machine.status === 1 ? 'Active' : 'Inactive'}</Badge>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={edit(machine).url}>
                                <Pencil className="mr-2 h-4 w-4" />
                                Edit
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Machine Details */}
                    <div className="space-y-6 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Machine Details</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <Label className="text-muted-foreground">Serial Number</Label>
                                        <p className="mt-1 font-mono font-medium">{machine.serial_number}</p>
                                    </div>
                                    <div>
                                        <Label className="text-muted-foreground">Name</Label>
                                        <p className="mt-1 font-medium">{machine.name}</p>
                                    </div>
                                    <div>
                                        <Label className="text-muted-foreground">Status</Label>
                                        <div className="mt-1">
                                            <Badge variant={machine.status === 1 ? 'default' : 'secondary'}>
                                                {machine.status === 1 ? 'Active' : 'Inactive'}
                                            </Badge>
                                        </div>
                                    </div>
                                    <div>
                                        <Label className="text-muted-foreground">Created At</Label>
                                        <p className="mt-1 font-medium">{format(new Date(machine.created_at), 'MMMM d, yyyy')}</p>
                                    </div>
                                    {machine.last_logged_in_at && (
                                        <div>
                                            <Label className="text-muted-foreground">Last Logged In</Label>
                                            <p className="mt-1 font-medium">{format(new Date(machine.last_logged_in_at), 'MMMM d, yyyy HH:mm')}</p>
                                        </div>
                                    )}
                                    {machine.last_used_at && (
                                        <div>
                                            <Label className="text-muted-foreground">Last Used</Label>
                                            <p className="mt-1 font-medium">{format(new Date(machine.last_used_at), 'MMMM d, yyyy HH:mm')}</p>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Images */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Machine Images</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-6 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label className="text-muted-foreground">Thumbnail</Label>
                                        {machine.thumbnail_url ? (
                                            <div className="overflow-hidden rounded-lg border">
                                                <img src={machine.thumbnail_url} alt="Thumbnail" className="h-48 w-full object-cover" />
                                            </div>
                                        ) : (
                                            <div className="flex h-48 items-center justify-center rounded-lg border bg-muted">
                                                <div className="text-center text-muted-foreground">
                                                    <ImageIcon className="mx-auto h-8 w-8" />
                                                    <p className="mt-2 text-sm">No thumbnail</p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                    <div className="space-y-2">
                                        <Label className="text-muted-foreground">Detail Image</Label>
                                        {machine.detail_image_url ? (
                                            <div className="overflow-hidden rounded-lg border">
                                                <img src={machine.detail_image_url} alt="Detail" className="h-48 w-full object-cover" />
                                            </div>
                                        ) : (
                                            <div className="flex h-48 items-center justify-center rounded-lg border bg-muted">
                                                <div className="text-center text-muted-foreground">
                                                    <ImageIcon className="mx-auto h-8 w-8" />
                                                    <p className="mt-2 text-sm">No detail image</p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* User Information */}
                        {machine.user && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Bound User</CardTitle>
                                    <CardDescription>User this machine is bound to</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <Label className="text-muted-foreground">Name</Label>
                                            <p className="mt-1 font-medium">{machine.user.name}</p>
                                        </div>
                                        <div>
                                            <Label className="text-muted-foreground">Email</Label>
                                            <p className="mt-1 font-medium">{machine.user.email}</p>
                                        </div>
                                        {machine.user.phone && (
                                            <div>
                                                <Label className="text-muted-foreground">Phone</Label>
                                                <p className="mt-1 font-medium">{machine.user.phone}</p>
                                            </div>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Device Card */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Linked Tablet</CardTitle>
                                <CardDescription>Device connected to this machine</CardDescription>
                            </CardHeader>
                            <CardContent>
                                {machine.device ? (
                                    <div className="space-y-2">
                                        <div>
                                            <Label className="text-muted-foreground">Name</Label>
                                            <p className="mt-1 font-medium">{machine.device.name}</p>
                                        </div>
                                        <div>
                                            <Label className="text-muted-foreground">UUID</Label>
                                            <p className="mt-1 font-mono text-sm">{machine.device.uuid}</p>
                                        </div>
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">No tablet linked</p>
                                )}
                            </CardContent>
                        </Card>

                        {/* Subscription Card */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Subscription</CardTitle>
                                <CardDescription>Associated subscription</CardDescription>
                            </CardHeader>
                            <CardContent>
                                {machine.user_subscription ? (
                                    <div className="space-y-2">
                                        <div>
                                            <Label className="text-muted-foreground">Plan</Label>
                                            <p className="mt-1 font-medium">{machine.user_subscription.subscription.title}</p>
                                        </div>
                                        <div>
                                            <Label className="text-muted-foreground">Price</Label>
                                            <p className="mt-1 font-medium">RM {machine.user_subscription.subscription.price}/month</p>
                                        </div>
                                        <div>
                                            <Label className="text-muted-foreground">Status</Label>
                                            <div className="mt-1">{getStatusBadge(machine.user_subscription.status)}</div>
                                        </div>
                                        {machine.user_subscription.ends_at && (
                                            <div>
                                                <Label className="text-muted-foreground">Expires</Label>
                                                <p className="mt-1 font-medium">
                                                    {format(new Date(machine.user_subscription.ends_at), 'MMMM d, yyyy')}
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">No subscription linked</p>
                                )}
                            </CardContent>
                        </Card>

                        {/* Actions */}
                        {machine.user && (
                            <Card className="border-red-200 dark:border-red-900">
                                <CardHeader>
                                    <CardTitle className="text-red-600 dark:text-red-400">Unbind Machine</CardTitle>
                                    <CardDescription>Remove the binding between this machine and the user</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <Button variant="destructive" className="w-full" onClick={handleUnbind}>
                                        <Link2Off className="mr-2 h-4 w-4" />
                                        Unbind from User
                                    </Button>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
