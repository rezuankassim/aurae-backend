import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { type BreadcrumbItem, type Order } from '@/types';
import { Head } from '@inertiajs/react';
import { format } from 'date-fns';

interface Props {
    order: Order;
}

export default function OrderShow({ order }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Order History',
            href: '/order-history',
        },
        {
            title: `Order #${order.reference}`,
            href: `/order-history/${order.id}`,
        },
    ];

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
            'dispatched': { label: 'Dispatched', variant: 'outline' },
        };

        const config = statusMap[status] || { label: status, variant: 'secondary' };
        return <Badge variant={config.variant}>{config.label}</Badge>;
    };

    const hasSubscriptionItems = order.lines.some((line) => line.purchasable?.product?.product_type?.is_subscription);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Order #${order.reference}`} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-3xl font-bold">Order #{order.reference}</h1>
                        <p className="text-muted-foreground">Placed on {format(new Date(order.created_at), 'MMMM d, yyyy')}</p>
                    </div>
                    {getStatusBadge(order.status)}
                </div>

                {hasSubscriptionItems && (
                    <Card className="border-blue-200 bg-blue-50">
                        <CardContent className="pt-6">
                            <div className="flex gap-3">
                                <Badge variant="secondary">Subscription</Badge>
                                <p className="text-sm text-blue-900">
                                    This order contains subscription items. You will be charged monthly for these products.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                )}

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Order Items</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {order.lines.map((line) => (
                                <div key={line.id} className="flex gap-4">
                                    <div className="h-20 w-20 flex-shrink-0 overflow-hidden rounded-md bg-muted">
                                        <img
                                            src={line.purchasable?.product?.thumbnail?.url || '/placeholder-product.png'}
                                            alt={line.purchasable?.product?.attribute_data?.name?.en || 'Product'}
                                            className="h-full w-full object-cover"
                                        />
                                    </div>
                                    <div className="flex flex-1 justify-between">
                                        <div>
                                            <h3 className="font-semibold">
                                                {line.purchasable?.product?.attribute_data?.name?.en || 'Product'}
                                            </h3>
                                            <p className="text-sm text-muted-foreground">Quantity: {line.quantity}</p>
                                            {line.purchasable?.product?.product_type?.is_subscription && (
                                                <Badge variant="secondary" className="mt-1">
                                                    Subscription
                                                </Badge>
                                            )}
                                        </div>
                                        <div className="text-right">
                                            <p className="font-semibold">{formatPrice(line.total)}</p>
                                            {line.purchasable?.product?.product_type?.is_subscription && (
                                                <p className="text-xs text-muted-foreground">/month</p>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}

                            <div className="space-y-1 border-t pt-4">
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Subtotal</span>
                                    <span>{formatPrice(order.sub_total)}</span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Tax</span>
                                    <span>{formatPrice(order.tax_total)}</span>
                                </div>
                                {order.discount_total && typeof order.discount_total === 'object' && order.discount_total.value > 0 && (
                                    <div className="flex justify-between text-sm text-green-600">
                                        <span>Discount</span>
                                        <span>-{formatPrice(order.discount_total)}</span>
                                    </div>
                                )}
                                <div className="flex justify-between border-t pt-2 font-semibold">
                                    <span>Total</span>
                                    <span>{formatPrice(order.total)}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Shipping Address</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {order.shippingAddress ? (
                                    <div className="text-sm">
                                        <p className="font-semibold">
                                            {order.shippingAddress.first_name} {order.shippingAddress.last_name}
                                        </p>
                                        <p>{order.shippingAddress.line_one}</p>
                                        {order.shippingAddress.line_two && <p>{order.shippingAddress.line_two}</p>}
                                        <p>
                                            {order.shippingAddress.city}
                                            {order.shippingAddress.state && `, ${order.shippingAddress.state}`}{' '}
                                            {order.shippingAddress.postcode}
                                        </p>
                                        <p>{order.shippingAddress.country?.name}</p>
                                        <div className="mt-3">
                                            <p>{order.shippingAddress.contact_email}</p>
                                            <p>{order.shippingAddress.contact_phone}</p>
                                        </div>
                                    </div>
                                ) : (
                                    <p className="text-muted-foreground">No shipping address</p>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Billing Address</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {order.billingAddress ? (
                                    <div className="text-sm">
                                        <p className="font-semibold">
                                            {order.billingAddress.first_name} {order.billingAddress.last_name}
                                        </p>
                                        <p>{order.billingAddress.line_one}</p>
                                        {order.billingAddress.line_two && <p>{order.billingAddress.line_two}</p>}
                                        <p>
                                            {order.billingAddress.city}
                                            {order.billingAddress.state && `, ${order.billingAddress.state}`}{' '}
                                            {order.billingAddress.postcode}
                                        </p>
                                        <p>{order.billingAddress.country?.name}</p>
                                    </div>
                                ) : (
                                    <p className="text-muted-foreground">No billing address</p>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
