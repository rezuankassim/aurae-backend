import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Order } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { format } from 'date-fns';

interface Props {
    orders: Order[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Orders',
        href: '/admin/orders',
    },
];

export default function AdminOrdersIndex({ orders }: Props) {
    const formatPrice = (price: any) => {
        if (typeof price === 'number') {
            return `$${(price / 100).toFixed(2)}`;
        }
        if (price?.value) {
            return `$${(price.value / 100).toFixed(2)}`;
        }
        return '$0.00';
    };

    const getStatusBadge = (status: string) => {
        const statusMap: Record<string, { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' }> = {
            'awaiting-payment': { label: 'Awaiting Payment', variant: 'secondary' },
            'payment-offline': { label: 'Payment Offline', variant: 'default' },
            'payment-received': { label: 'Payment Received', variant: 'default' },
            dispatched: { label: 'Dispatched', variant: 'outline' },
        };

        const config = statusMap[status] || { label: status, variant: 'secondary' };
        return <Badge variant={config.variant}>{config.label}</Badge>;
    };

    const hasSubscriptionItems = (order: Order) => {
        return order.lines.some((line) => line.purchasable?.product?.product_type?.is_subscription);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Orders" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div>
                    <h1 className="text-3xl font-bold">Orders</h1>
                    <p className="text-muted-foreground">Manage customer orders</p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>All Orders ({orders.length})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {orders.length === 0 ? (
                            <div className="flex min-h-[200px] items-center justify-center">
                                <p className="text-muted-foreground">No orders found.</p>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Order #</TableHead>
                                        <TableHead>Customer</TableHead>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Items</TableHead>
                                        <TableHead className="text-right">Total</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {orders.map((order) => (
                                        <TableRow key={order.id}>
                                            <TableCell className="font-medium">{order.reference}</TableCell>
                                            <TableCell>{order.user?.name || 'Guest'}</TableCell>
                                            <TableCell>{format(new Date(order.created_at), 'MMM d, yyyy')}</TableCell>
                                            <TableCell>
                                                <div className="flex flex-col gap-1">
                                                    {getStatusBadge(order.status)}
                                                    {hasSubscriptionItems(order) && (
                                                        <Badge variant="secondary" className="w-fit text-xs">
                                                            Subscription
                                                        </Badge>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>{order.lines.length}</TableCell>
                                            <TableCell className="text-right font-semibold">{formatPrice(order.total)}</TableCell>
                                            <TableCell className="text-right">
                                                <Link href={`/admin/orders/${order.id}`}>
                                                    <Button variant="outline" size="sm">
                                                        View Details
                                                    </Button>
                                                </Link>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
