import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Order } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { CheckCircle } from 'lucide-react';

interface Props {
    order: Order;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Order Confirmation',
        href: '#',
    },
];

export default function CheckoutSuccess({ order }: Props) {
    const formatPrice = (price: any) => {
        if (typeof price === 'number') {
            return `$${(price / 100).toFixed(2)}`;
        }
        if (price?.value) {
            return `$${(price.value / 100).toFixed(2)}`;
        }
        return '$0.00';
    };

    const hasSubscriptionItems = order.lines.some((line) => line.purchasable?.product?.product_type?.is_subscription);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Order Confirmed" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardContent className="flex flex-col items-center gap-4 pt-8 text-center">
                        <CheckCircle className="h-16 w-16 text-green-600" />
                        <div>
                            <h1 className="text-3xl font-bold">Order Confirmed!</h1>
                            <p className="mt-2 text-muted-foreground">Thank you for your purchase. Your order has been placed successfully.</p>
                        </div>
                        <div className="rounded-lg bg-muted px-6 py-3">
                            <p className="text-sm text-muted-foreground">Order Number</p>
                            <p className="text-xl font-semibold">{order.reference}</p>
                        </div>
                    </CardContent>
                </Card>

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
                            <CardTitle>Order Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {order.lines.map((line) => (
                                <div key={line.id} className="flex justify-between">
                                    <div>
                                        <p className="font-medium">{line.purchasable?.product?.attribute_data?.name?.en || 'Product'}</p>
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
                                <div className="flex justify-between border-t pt-2 font-semibold">
                                    <span>Total</span>
                                    <span>{formatPrice(order.total)}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Shipping Information</CardTitle>
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
                                        {order.shippingAddress.state && `, ${order.shippingAddress.state}`} {order.shippingAddress.postcode}
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
                </div>

                <div className="flex justify-center gap-4">
                    <Link href="/order-history">
                        <Button variant="outline">View Order History</Button>
                    </Link>
                    <Link href="/products">
                        <Button>Continue Shopping</Button>
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
