import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { cancel, index, show } from '@/routes/admin/user-subscriptions';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { format } from 'date-fns';
import { AlertTriangle, RefreshCcw, XCircle } from 'lucide-react';
import { useState } from 'react';
import type { UserSubscription } from './index';

interface Props {
    userSubscription: UserSubscription & {
        user: {
            id: number;
            name: string;
            email: string;
            phone: string | null;
            machines: Array<{
                id: string;
                serial_number: string;
                model: string | null;
            }>;
        };
    };
}

export default function UserSubscriptionShow({ userSubscription }: Props) {
    const { flash } = usePage().props as any;
    const [isCancelling, setIsCancelling] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'User Subscriptions',
            href: index().url,
        },
        {
            title: `${userSubscription.user.name}'s Subscription`,
            href: show(userSubscription.id).url,
        },
    ];

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

    const getPaymentStatusBadge = (status: string) => {
        const statusMap: Record<string, { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' }> = {
            pending: { label: 'Pending', variant: 'secondary' },
            completed: { label: 'Completed', variant: 'default' },
            failed: { label: 'Failed', variant: 'destructive' },
        };

        const config = statusMap[status] || { label: status, variant: 'secondary' };
        return <Badge variant={config.variant}>{config.label}</Badge>;
    };

    const handleCancel = () => {
        if (!confirm('Are you sure you want to cancel this subscription?')) return;

        setIsCancelling(true);
        router.post(
            cancel(userSubscription.id).url,
            {},
            {
                preserveScroll: true,
                onFinish: () => setIsCancelling(false),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${userSubscription.user.name}'s Subscription`} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                {/* Flash Messages */}
                {flash?.success && (
                    <Alert>
                        <AlertTitle>Success</AlertTitle>
                        <AlertDescription>{flash.success}</AlertDescription>
                    </Alert>
                )}

                {flash?.warning && (
                    <Alert variant="destructive" className="border-orange-500 bg-orange-50 text-orange-900">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertTitle>Action Required</AlertTitle>
                        <AlertDescription>{flash.warning}</AlertDescription>
                    </Alert>
                )}

                {flash?.error && (
                    <Alert variant="destructive">
                        <AlertTitle>Error</AlertTitle>
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                )}

                {/* Header */}
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-3xl font-bold">{userSubscription.user.name}'s Subscription</h1>
                        <p className="text-muted-foreground">
                            {userSubscription.subscription.title} - RM {userSubscription.subscription.price}/month
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        {getStatusBadge(userSubscription.status)}
                        {userSubscription.is_recurring && (
                            <Badge variant="outline" className="flex items-center gap-1">
                                <RefreshCcw className="h-3 w-3" />
                                Recurring
                            </Badge>
                        )}
                    </div>
                </div>

                {/* Recurring Warning */}
                {userSubscription.is_recurring && userSubscription.status === 'active' && (
                    <Alert className="border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-50">
                        <RefreshCcw className="h-4 w-4" />
                        <AlertTitle>Recurring Subscription</AlertTitle>
                        <AlertDescription>
                            This is a recurring subscription. If you cancel it in the system, you must also cancel it manually in the SenangPay
                            dashboard to stop future automatic charges.
                        </AlertDescription>
                    </Alert>
                )}

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Subscription Details */}
                    <div className="space-y-6 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Subscription Details</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <Label className="text-muted-foreground">Status</Label>
                                        <div className="mt-1">{getStatusBadge(userSubscription.status)}</div>
                                    </div>
                                    <div>
                                        <Label className="text-muted-foreground">Payment Status</Label>
                                        <div className="mt-1">{getPaymentStatusBadge(userSubscription.payment_status)}</div>
                                    </div>
                                    <div>
                                        <Label className="text-muted-foreground">Start Date</Label>
                                        <p className="mt-1 font-medium">
                                            {userSubscription.starts_at ? format(new Date(userSubscription.starts_at), 'MMMM d, yyyy') : '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <Label className="text-muted-foreground">End Date</Label>
                                        <p className="mt-1 font-medium">
                                            {userSubscription.ends_at ? format(new Date(userSubscription.ends_at), 'MMMM d, yyyy') : '-'}
                                        </p>
                                    </div>
                                    {userSubscription.is_recurring && (
                                        <div>
                                            <Label className="text-muted-foreground">Next Billing Date</Label>
                                            <p className="mt-1 font-medium">
                                                {userSubscription.next_billing_at
                                                    ? format(new Date(userSubscription.next_billing_at), 'MMMM d, yyyy')
                                                    : '-'}
                                            </p>
                                        </div>
                                    )}
                                    {userSubscription.cancelled_at && (
                                        <div>
                                            <Label className="text-muted-foreground">Cancelled At</Label>
                                            <p className="mt-1 font-medium">{format(new Date(userSubscription.cancelled_at), 'MMMM d, yyyy')}</p>
                                        </div>
                                    )}
                                    <div>
                                        <Label className="text-muted-foreground">Payment Method</Label>
                                        <p className="mt-1 font-medium">{userSubscription.payment_method || '-'}</p>
                                    </div>
                                    <div>
                                        <Label className="text-muted-foreground">Transaction ID</Label>
                                        <p className="mt-1 font-mono text-sm font-medium">{userSubscription.transaction_id || '-'}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>User Information</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <Label className="text-muted-foreground">Name</Label>
                                        <p className="mt-1 font-medium">{userSubscription.user.name}</p>
                                    </div>
                                    <div>
                                        <Label className="text-muted-foreground">Email</Label>
                                        <p className="mt-1 font-medium">{userSubscription.user.email}</p>
                                    </div>
                                    <div>
                                        <Label className="text-muted-foreground">Phone</Label>
                                        <p className="mt-1 font-medium">{userSubscription.user.phone || '-'}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {userSubscription.user.machines && userSubscription.user.machines.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Linked Machines</CardTitle>
                                    <CardDescription>Machines associated with this user</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-2">
                                        {userSubscription.user.machines.map((machine) => (
                                            <div key={machine.id} className="flex items-center justify-between rounded-lg border p-3">
                                                <div>
                                                    <p className="font-mono font-medium">{machine.serial_number}</p>
                                                    {machine.model && <p className="text-sm text-muted-foreground">{machine.model}</p>}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Actions */}
                    <div className="space-y-6">
                        {userSubscription.status !== 'cancelled' && (
                            <Card className="border-red-200 dark:border-red-900">
                                <CardHeader>
                                    <CardTitle className="text-red-600 dark:text-red-50">Cancel Subscription</CardTitle>
                                    <CardDescription>
                                        Cancel this subscription immediately.
                                        {userSubscription.is_recurring && (
                                            <span className="mt-1 block font-semibold text-red-600 dark:text-red-50">
                                                Remember to also cancel in SenangPay dashboard!
                                            </span>
                                        )}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <Button variant="destructive" className="w-full" onClick={handleCancel} disabled={isCancelling}>
                                        <XCircle className="mr-2 h-4 w-4" />
                                        Cancel Subscription
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
