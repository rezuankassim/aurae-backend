import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type CollectionGroup, type Product } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface Props {
    collectionGroups: CollectionGroup[];
    products: Product[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: '/products',
    },
];

export default function ProductsIndex({ collectionGroups, products }: Props) {
    const formatPrice = (price: any) => {
        if (typeof price === 'number') {
            return `$${(price / 100).toFixed(2)}`;
        }
        if (price?.value) {
            return `$${(price.value / 100).toFixed(2)}`;
        }
        return 'N/A';
    };

    const getProductPrice = (product: Product) => {
        if (product.variants && product.variants.length > 0) {
            const variant = product.variants[0];
            if (variant.base_prices && variant.base_prices.length > 0) {
                return formatPrice(variant.base_prices[0].price);
            }
        }
        return 'N/A';
    };

    const getProductImage = (product: Product) => {
        console.log(product);
        return product.thumbnail?.original_url || '/placeholder-product.png';
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Products" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="space-y-8">
                    <Heading title="Products" description="Browse and explore our products" />

                    {collectionGroups.map((group) => (
                        <div key={group.id} className="space-y-4">
                            <div>
                                <h2 className="text-2xl font-semibold">{group.name}</h2>
                            </div>

                            {group.collections && group.collections.length > 0 ? (
                                group.collections.map((collection: any) => (
                                    <div key={collection.id} className="space-y-3">
                                        {collection.attribute_data?.name?.en && (
                                            <h3 className="text-xl font-medium">{collection.attribute_data.name.en}</h3>
                                        )}

                                        {collection.products && collection.products.length > 0 ? (
                                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                                {collection.products.map((product: Product) => (
                                                    <Card key={product.id} className="overflow-hidden">
                                                        <div className="aspect-square overflow-hidden bg-muted">
                                                            <img
                                                                src={getProductImage(product)}
                                                                alt={product.attribute_data?.name?.en || 'Product'}
                                                                className="h-full w-full object-cover"
                                                            />
                                                        </div>
                                                        <CardHeader>
                                                            <div className="flex items-start justify-between gap-2">
                                                                <CardTitle className="line-clamp-2 text-base">
                                                                    {product.attribute_data?.name?.en || 'Unnamed Product'}
                                                                </CardTitle>
                                                                {product.product_type?.is_subscription && (
                                                                    <Badge variant="secondary">Subscription</Badge>
                                                                )}
                                                            </div>
                                                            <CardDescription className="text-lg font-semibold">
                                                                {getProductPrice(product)}
                                                            </CardDescription>
                                                        </CardHeader>
                                                        <CardContent>
                                                            <Link href={`/products/${product.id}`}>
                                                                <Button className="w-full">View Details</Button>
                                                            </Link>
                                                        </CardContent>
                                                    </Card>
                                                ))}
                                            </div>
                                        ) : (
                                            <p className="text-muted-foreground">No products in this collection.</p>
                                        )}
                                    </div>
                                ))
                            ) : (
                                <p className="text-muted-foreground">No collections in this group.</p>
                            )}
                        </div>
                    ))}

                    {products.length === 0 && collectionGroups.length === 0 && (
                        <Card>
                            <CardContent className="flex min-h-[200px] items-center justify-center">
                                <p className="text-muted-foreground">No products available at the moment.</p>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
