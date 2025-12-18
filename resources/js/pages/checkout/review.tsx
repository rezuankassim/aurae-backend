import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { type BreadcrumbItem, type Cart } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface Props {
    cart: Cart;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cart',
        href: '/cart',
    },
    {
        title: 'Checkout',
        href: '/checkout',
    },
    {
        title: 'Review',
        href: '/checkout/review',
    },
];

export default function CheckoutReview({ cart }: Props) {
    const { post, processing } = useForm({});

    const formatPrice = (price: any) => {
        if (typeof price === 'number') {
            return `$${(price / 100).toFixed(2)}`;
        }
        if (price?.value) {
            return `$${(price.value / 100).toFixed(2)}`;
        }
        return '$0.00';
    };

    const getProductImage = (line: any) => {
        return line.purchasable?.product?.thumbnail?.url || '/placeholder-product.png';
    };

    const getProductName = (line: any) => {
        return line.purchasable?.product?.attribute_data?.name?.en || 'Product';
    };

    const handlePlaceOrder = () => {
        post('/checkout/complete');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Review Order" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div>
                    <h1 className="text-3xl font-bold">Review Your Order</h1>
                    <p className="text-muted-foreground">Please review your order before placing it</p>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        {/* Order Items */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Order Items</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {cart.lines.map((line) => (
                                    <div key={line.id} className="flex gap-4">
                                        <div className="h-20 w-20 flex-shrink-0 overflow-hidden rounded-md bg-muted">
                                            <img
                                                src={getProductImage(line)}
                                                alt={getProductName(line)}
                                                className="h-full w-full object-cover"
                                            />
                                        </div>
                                        <div className="flex flex-1 justify-between">
                                            <div>
                                                <h3 className="font-semibold">{getProductName(line)}</h3>
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
                            </CardContent>
                        </Card>

                        {/* Shipping Address */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Shipping Address</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {cart.shippingAddress ? (
                                    <div className="text-sm">
                                        <p className="font-semibold">
                                            {cart.shippingAddress.first_name} {cart.shippingAddress.last_name}
                                        </p>
                                        <p>{cart.shippingAddress.line_one}</p>
                                        {cart.shippingAddress.line_two && <p>{cart.shippingAddress.line_two}</p>}
                                        <p>
                                            {cart.shippingAddress.city}
                                            {cart.shippingAddress.state && `, ${cart.shippingAddress.state}`}{' '}
                                            {cart.shippingAddress.postcode}
                                        </p>
                                        <p>{cart.shippingAddress.country?.name}</p>
                                        <p className="mt-2">{cart.shippingAddress.contact_email}</p>
                                        <p>{cart.shippingAddress.contact_phone}</p>
                                    </div>
                                ) : (
                                    <p className="text-muted-foreground">No shipping address</p>
                                )}
                            </CardContent>
                        </Card>

                        {/* Billing Address */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Billing Address</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {cart.billingAddress ? (
                                    <div className="text-sm">
                                        <p className="font-semibold">
                                            {cart.billingAddress.first_name} {cart.billingAddress.last_name}
                                        </p>
                                        <p>{cart.billingAddress.line_one}</p>
                                        {cart.billingAddress.line_two && <p>{cart.billingAddress.line_two}</p>}
                                        <p>
                                            {cart.billingAddress.city}
                                            {cart.billingAddress.state && `, ${cart.billingAddress.state}`}{' '}
                                            {cart.billingAddress.postcode}
                                        </p>
                                        <p>{cart.billingAddress.country?.name}</p>
                                    </div>
                                ) : (
                                    <p className="text-muted-foreground">No billing address</p>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Order Summary */}
                    <div className="lg:col-span-1">
                        <Card className="sticky top-4">
                            <CardHeader>
                                <CardTitle>Order Summary</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Subtotal</span>
                                    <span>{formatPrice(cart.sub_total)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Tax</span>
                                    <span>{formatPrice(cart.tax_total)}</span>
                                </div>
                                {cart.discount_total && typeof cart.discount_total === 'object' && cart.discount_total.value > 0 && (
                                    <div className="flex justify-between text-green-600">
                                        <span>Discount</span>
                                        <span>-{formatPrice(cart.discount_total)}</span>
                                    </div>
                                )}
                                <div className="border-t pt-2">
                                    <div className="flex justify-between text-lg font-semibold">
                                        <span>Total</span>
                                        <span>{formatPrice(cart.total)}</span>
                                    </div>
                                </div>
                            </CardContent>
                            <CardFooter className="flex-col gap-2">
                                <Button size="lg" className="w-full" onClick={handlePlaceOrder} disabled={processing}>
                                    Place Order
                                </Button>
                                <Link href="/checkout" className="w-full">
                                    <Button variant="outline" size="sm" className="w-full">
                                        Edit Address
                                    </Button>
                                </Link>
                            </CardFooter>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
