import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Cart } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ShoppingBag, Trash2 } from 'lucide-react';

interface Props {
    cart: Cart | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cart',
        href: '/cart',
    },
];

export default function CartIndex({ cart }: Props) {
    const formatPrice = (price: any) => {
        if (typeof price === 'number') {
            return `$${(price / 100).toFixed(2)}`;
        }
        if (price?.value) {
            return `$${(price.value / 100).toFixed(2)}`;
        }
        return '$0.00';
    };

    const handleUpdateQuantity = (lineId: number, quantity: number) => {
        router.put(
            `/cart/lines/${lineId}`,
            { quantity },
            {
                preserveScroll: true,
            },
        );
    };

    const handleRemoveLine = (lineId: number) => {
        router.delete(`/cart/lines/${lineId}`, {
            preserveScroll: true,
        });
    };

    const getProductImage = (line: any) => {
        return line.purchasable?.product?.thumbnail?.url || '/placeholder-product.png';
    };

    const getProductName = (line: any) => {
        return line.purchasable?.product?.attribute_data?.name?.en || 'Product';
    };

    const getVariantLabel = (line: any) => {
        if (line.purchasable?.values && line.purchasable.values.length > 0) {
            return line.purchasable.values.map((v: any) => v.name?.en || v.name).join(' / ');
        }
        return null;
    };

    if (!cart || !cart.lines || cart.lines.length === 0) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Shopping Cart" />
                <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                    <Card>
                        <CardContent className="flex min-h-[400px] flex-col items-center justify-center gap-4">
                            <ShoppingBag className="h-16 w-16 text-muted-foreground" />
                            <div className="text-center">
                                <h2 className="text-2xl font-semibold">Your cart is empty</h2>
                                <p className="text-muted-foreground">Add some products to get started!</p>
                            </div>
                            <Link href="/products">
                                <Button>Browse Products</Button>
                            </Link>
                        </CardContent>
                    </Card>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Shopping Cart" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div>
                    <h1 className="text-3xl font-bold">Shopping Cart</h1>
                    <p className="text-muted-foreground">{cart.lines.length} item(s) in your cart</p>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Cart Items */}
                    <div className="space-y-4 lg:col-span-2">
                        {cart.lines.map((line) => (
                            <Card key={line.id}>
                                <CardContent className="p-4">
                                    <div className="flex gap-4">
                                        <div className="h-24 w-24 flex-shrink-0 overflow-hidden rounded-md bg-muted">
                                            <img src={getProductImage(line)} alt={getProductName(line)} className="h-full w-full object-cover" />
                                        </div>

                                        <div className="flex flex-1 flex-col justify-between">
                                            <div>
                                                <div className="flex items-start justify-between gap-4">
                                                    <div>
                                                        <h3 className="font-semibold">{getProductName(line)}</h3>
                                                        {getVariantLabel(line) && (
                                                            <p className="text-sm text-muted-foreground">{getVariantLabel(line)}</p>
                                                        )}
                                                        {line.purchasable?.product?.product_type?.is_subscription && (
                                                            <Badge variant="secondary" className="mt-1">
                                                                Subscription
                                                            </Badge>
                                                        )}
                                                    </div>
                                                    <Button variant="ghost" size="icon" onClick={() => handleRemoveLine(line.id)}>
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </div>

                                            <div className="flex items-center justify-between gap-4">
                                                <Select
                                                    value={line.quantity.toString()}
                                                    onValueChange={(value) => handleUpdateQuantity(line.id, parseInt(value))}
                                                >
                                                    <SelectTrigger className="w-20">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {[1, 2, 3, 4, 5, 6, 7, 8, 9, 10].map((num) => (
                                                            <SelectItem key={num} value={num.toString()}>
                                                                {num}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>

                                                <div className="text-right">
                                                    <p className="font-semibold">{formatPrice(line.total)}</p>
                                                    {line.purchasable?.product?.product_type?.is_subscription && (
                                                        <p className="text-xs text-muted-foreground">/month</p>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
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
                            <CardFooter>
                                <Link href="/checkout" className="w-full">
                                    <Button size="lg" className="w-full">
                                        Proceed to Checkout
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
