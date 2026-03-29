import Heading from '@/components/heading';
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
        title: 'Order History',
        href: '/order-history',
    },
];

export default function OrderHistory({ orders }: Props) {
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
            'awaiting-payment': { label: 'Payment Pending', variant: 'secondary' },
            'payment-received': { label: 'Payment Received', variant: 'default' },
            dispatched: { label: 'Dispatched', variant: 'outline' },
            delivered: { label: 'Delivered', variant: 'default' },
        };

        const config = statusMap[status] || { label: status, variant: 'secondary' };
        return <Badge variant={config.variant}>{config.label}</Badge>;
    };

    const hasSubscriptionItems = (order: Order) => {
        return order.lines.some((line) => line.purchasable?.product?.product_type?.is_subscription);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Order History" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading title="Order History" description="View and track your orders" />

                {orders.length === 0 ? (
                    <Card>
                        <CardContent className="flex min-h-[400px] flex-col items-center justify-center gap-4">
                            <div className="text-center">
                                <h2 className="text-2xl font-semibold">No orders yet</h2>
                                <p className="text-muted-foreground">You haven't placed any orders yet.</p>
                            </div>
                            <Link href="/products">
                                <Button>Start Shopping</Button>
                            </Link>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle>Your Orders</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Order #</TableHead>
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
                                                <Link href={`/order-history/${order.id}`}>
                                                    <Button variant="outline" size="sm">
                                                        View Details
                                                    </Button>
                                                </Link>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
