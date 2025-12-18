import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Product } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ShoppingCart } from 'lucide-react';
import { useState } from 'react';

interface Props {
    product: Product;
    relatedProducts: Product[];
}

export default function ProductShow({ product, relatedProducts }: Props) {
    const [selectedVariantId, setSelectedVariantId] = useState<number>(product.variants && product.variants.length > 0 ? product.variants[0].id : 0);
    const [quantity, setQuantity] = useState(1);

    const { data, setData, post, processing } = useForm({
        variant_id: selectedVariantId,
        quantity: 1,
    });

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Products',
            href: '/products',
        },
        {
            title: product.attribute_data?.name?.en || 'Product',
            href: `/products/${product.id}`,
        },
    ];

    const formatPrice = (price: any) => {
        if (typeof price === 'number') {
            return `$${(price / 100).toFixed(2)}`;
        }
        if (price?.value) {
            return `$${(price.value / 100).toFixed(2)}`;
        }
        return 'N/A';
    };

    const selectedVariant = product.variants?.find((v) => v.id === selectedVariantId);

    const getVariantPrice = () => {
        if (selectedVariant?.base_prices && selectedVariant.base_prices.length > 0) {
            return formatPrice(selectedVariant.base_prices[0].price);
        }
        return 'N/A';
    };

    const getVariantLabel = (variant: any) => {
        if (variant.values && variant.values.length > 0) {
            return variant.values.map((v: any) => v.name?.en || v.name).join(' / ');
        }
        return `${variant.sku || 'Default Variant'}`;
    };

    const handleAddToCart = (e: React.FormEvent) => {
        e.preventDefault();
        post('/cart/add', {
            preserveScroll: true,
            onSuccess: () => {
                // Optionally redirect to cart or show success message
            },
        });
    };

    const handleVariantChange = (value: string) => {
        const variantId = parseInt(value);
        setSelectedVariantId(variantId);
        setData('variant_id', variantId);
    };

    const getProductImage = (product: Product) => {
        return product.thumbnail?.original_url || '/placeholder-product.png';
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={product.attribute_data?.name?.en || 'Product'} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="grid gap-6 md:grid-cols-2">
                    {/* Product Images */}
                    <div className="space-y-4">
                        <div className="aspect-square overflow-hidden rounded-lg bg-muted">
                            <img
                                src={product.media && product.media.length > 0 ? product.media[0].original_url : getProductImage(product)}
                                alt={product.attribute_data?.name?.en || 'Product'}
                                className="h-full w-full object-cover"
                            />
                        </div>
                        {product.media && product.media.length > 1 && (
                            <div className="grid grid-cols-4 gap-2">
                                {product.media.slice(1, 5).map((media: any) => (
                                    <div key={media.id} className="aspect-square overflow-hidden rounded-md bg-muted">
                                        <img src={media.original_url} alt="" className="h-full w-full object-cover" />
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Product Info */}
                    <div className="space-y-6">
                        <div>
                            <div className="flex items-start justify-between gap-4">
                                <h1 className="text-3xl font-bold">{product.attribute_data?.name?.en || 'Unnamed Product'}</h1>
                                {product.product_type?.is_subscription && (
                                    <Badge variant="secondary" className="text-sm">
                                        Subscription
                                    </Badge>
                                )}
                            </div>
                            <p className="mt-2 text-2xl font-semibold">{getVariantPrice()}</p>
                            {product.product_type?.is_subscription && <p className="mt-1 text-sm text-muted-foreground">Billed monthly</p>}
                        </div>

                        {product.attribute_data?.description?.en && (
                            <div className="prose prose-sm max-w-none" dangerouslySetInnerHTML={{ __html: product.attribute_data.description.en }} />
                        )}

                        <form onSubmit={handleAddToCart} className="space-y-4">
                            {product.variants && product.variants.length > 1 && (
                                <div className="space-y-2">
                                    <Label>Select Option</Label>
                                    <Select value={selectedVariantId.toString()} onValueChange={handleVariantChange}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {product.variants.map((variant) => (
                                                <SelectItem key={variant.id} value={variant.id.toString()}>
                                                    {getVariantLabel(variant)}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}

                            <div className="space-y-2">
                                <Label>Quantity</Label>
                                <Select
                                    value={quantity.toString()}
                                    onValueChange={(value) => {
                                        const qty = parseInt(value);
                                        setQuantity(qty);
                                        setData('quantity', qty);
                                    }}
                                >
                                    <SelectTrigger>
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
                            </div>

                            <Button type="submit" size="lg" className="w-full" disabled={processing}>
                                <ShoppingCart className="mr-2 h-5 w-5" />
                                Add to Cart
                            </Button>
                        </form>

                        {selectedVariant && (
                            <div className="rounded-lg border p-4">
                                <h3 className="mb-2 font-semibold">Product Details</h3>
                                <dl className="space-y-1 text-sm">
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">SKU:</dt>
                                        <dd>{selectedVariant.sku || 'N/A'}</dd>
                                    </div>
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">Stock:</dt>
                                        <dd>{selectedVariant.stock > 0 ? `${selectedVariant.stock} available` : 'Out of stock'}</dd>
                                    </div>
                                </dl>
                            </div>
                        )}
                    </div>
                </div>

                {/* Related Products */}
                {relatedProducts.length > 0 && (
                    <div className="mt-12 space-y-4">
                        <h2 className="text-2xl font-bold">Related Products</h2>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {relatedProducts.map((relatedProduct) => (
                                <Card key={relatedProduct.id} className="overflow-hidden">
                                    <div className="aspect-square overflow-hidden bg-muted">
                                        <img
                                            src={getProductImage(relatedProduct)}
                                            alt={relatedProduct.attribute_data?.name?.en || 'Product'}
                                            className="h-full w-full object-cover"
                                        />
                                    </div>
                                    <CardHeader>
                                        <CardTitle className="line-clamp-2 text-base">
                                            {relatedProduct.attribute_data?.name?.en || 'Unnamed Product'}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <Link href={`/products/${relatedProduct.id}`}>
                                            <Button variant="outline" size="sm" className="w-full">
                                                View
                                            </Button>
                                        </Link>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
