import AppLayout from '@/layouts/app-layout';
import { Product, ProductOption, ProductVariant, type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';

import ProductVariantController from '@/actions/App/Http/Controllers/Admin/ProductVariantController';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import ProductsLayout from '@/layouts/products/layout';
import { index } from '@/routes/admin/products';
import { configure } from '@/routes/admin/products/variants';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: index().url,
    },
];

export default function ProductVariantsIndex({
    product,
    options,
    variants,
    withVariants,
}: {
    product: Product;
    options: ProductOption[];
    variants: ProductVariant[];
    withVariants: boolean;
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Product Variant" />

            <ProductsLayout id_record={product.id} with_variants={withVariants}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto">
                    <div className="flex items-center justify-between">
                        <HeadingSmall title="Product Variant" description="Manage product's variants, create new" />
                    </div>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>Product Options</CardTitle>

                            <Button asChild>
                                <Link href={configure(product.id)}>Configure options</Link>
                            </Button>
                        </CardHeader>

                        <CardContent>
                            <div className="overflow-hidden rounded-md">
                                <Table>
                                    <TableHeader className="bg-muted">
                                        <TableRow>
                                            <TableHead>Option</TableHead>
                                            <TableHead>Values</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {options.map((option) => (
                                            <TableRow key={option.id}>
                                                <TableCell>{option.name.en}</TableCell>
                                                <TableCell>{option.values.map((value) => value.name.en).join(', ')}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>

                    <Form
                        {...ProductVariantController.updateAll.form(product.id)}
                        options={{
                            preserveScroll: true,
                        }}
                    >
                        <Card>
                            <CardHeader>
                                <CardTitle>Product Variants</CardTitle>
                            </CardHeader>

                            <CardContent>
                                <div className="overflow-hidden rounded-md">
                                    <Table>
                                        <TableHeader className="bg-muted">
                                            <TableRow>
                                                <TableHead>Option</TableHead>
                                                <TableHead>SKU</TableHead>
                                                <TableHead>Price</TableHead>
                                                <TableHead>Stock</TableHead>
                                                <TableHead></TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {variants.map((variant) => (
                                                <TableRow key={variant.id}>
                                                    <TableCell>
                                                        {variant.values.map((value) => {
                                                            return (
                                                                <p key={value.id}>
                                                                    <span className="font-semibold">{value.option.name.en}: </span>
                                                                    <span>{value.name.en}</span>
                                                                </p>
                                                            );
                                                        })}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Input name={`${variant.id}-sku`} className="w-40" defaultValue={variant.sku || ''} />
                                                    </TableCell>
                                                    <TableCell>
                                                        <Input
                                                            type="number"
                                                            name={`${variant.id}-price`}
                                                            className="w-40"
                                                            defaultValue={
                                                                variant.base_prices?.at(0)
                                                                    ? (variant.base_prices.at(0)!.price.value / 100).toFixed(
                                                                          variant.base_prices.at(0)!.price.currency.decimal_places,
                                                                      )
                                                                    : ''
                                                            }
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        <Input name={`${variant.id}-stock`} className="w-16" defaultValue={variant.stock || ''} />
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        <Button variant="link" size="sm">
                                                            Edit
                                                        </Button>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>

                        <Button className="mt-4" type="submit">
                            Save variants
                        </Button>
                    </Form>
                </div>
            </ProductsLayout>
        </AppLayout>
    );
}
