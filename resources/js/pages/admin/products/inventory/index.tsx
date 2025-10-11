import AppLayout from '@/layouts/app-layout';
import { Product, type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';

import ProductInventoryController from '@/actions/App/Http/Controllers/Admin/ProductInventoryController';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Field, FieldDescription, FieldError, FieldLabel, FieldLegend, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import ProductsLayout from '@/layouts/products/layout';
import { edit, index } from '@/routes/admin/products';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: index().url,
    },
];

export default function ProductInventoryIndex({ product }: { product: Product }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Inventory" />

            <ProductsLayout id_record={product.id}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto">
                    <div className="flex items-center justify-between">
                        <HeadingSmall title="Inventory" description="Manage product's inventory" />
                    </div>

                    <Form
                        {...ProductInventoryController.store.form(product.id)}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <Card className="mt-0">
                                    <CardContent className="space-y-6">
                                        <FieldSet className="grid grid-cols-3 gap-6">
                                            <FieldLegend className="sr-only">Product Identifiers</FieldLegend>
                                            <Field>
                                                <FieldLabel htmlFor="stock">In Stock</FieldLabel>
                                                <Input
                                                    type="number"
                                                    id="stock"
                                                    name="stock"
                                                    placeholder="Stock"
                                                    defaultValue={product.variants[0]?.stock || ''}
                                                />

                                                {errors.stock ? <FieldError>{errors.stock}</FieldError> : null}
                                            </Field>

                                            <Field>
                                                <FieldLabel htmlFor="backorder">On Backorder</FieldLabel>
                                                <Input
                                                    type="number"
                                                    id="backorder"
                                                    name="backorder"
                                                    placeholder="Backorder"
                                                    defaultValue={product.variants[0]?.backorder || ''}
                                                />

                                                {errors.backorder ? <FieldError>{errors.backorder}</FieldError> : null}
                                            </Field>

                                            <Field>
                                                <FieldLabel htmlFor="purchaseable">Purchasability</FieldLabel>
                                                <Select name="purchasable" defaultValue={product.variants[0]?.purchasable}>
                                                    <SelectTrigger id="purchaseable" className="w-full">
                                                        <SelectValue placeholder="Select purchasability" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="always">Always</SelectItem>
                                                        <SelectItem value="in_stock">In Stock</SelectItem>
                                                        <SelectItem value="in_stock_or_on_backorder">In Stock or On Backorder</SelectItem>
                                                    </SelectContent>
                                                </Select>

                                                {errors.purchasable ? <FieldError>{errors.purchasable}</FieldError> : null}
                                            </Field>

                                            <Field>
                                                <FieldLabel htmlFor="unit_quantity">Unit Quantity</FieldLabel>
                                                <Input
                                                    type="number"
                                                    id="unit_quantity"
                                                    name="unit_quantity"
                                                    placeholder="Unit Quantity"
                                                    defaultValue={product.variants[0]?.unit_quantity || ''}
                                                />
                                                <FieldDescription>How many individual items make up 1 unit.</FieldDescription>
                                                {errors.unit_quantity ? <FieldError>{errors.unit_quantity}</FieldError> : null}
                                            </Field>

                                            <Field>
                                                <FieldLabel htmlFor="quantity_increment">Quantity Increment</FieldLabel>
                                                <Input
                                                    type="number"
                                                    id="quantity_increment"
                                                    name="quantity_increment"
                                                    placeholder="Quantity Increment"
                                                    defaultValue={product.variants[0]?.quantity_increment || ''}
                                                />
                                                <FieldDescription>
                                                    The product variant must be purchased in multiples of this quantity.
                                                </FieldDescription>
                                                {errors.quantity_increment ? <FieldError>{errors.quantity_increment}</FieldError> : null}
                                            </Field>

                                            <Field>
                                                <FieldLabel htmlFor="min_quantity">Minimum Quantity</FieldLabel>
                                                <Input
                                                    type="number"
                                                    id="min_quantity"
                                                    name="min_quantity"
                                                    placeholder="Minimum Quantity"
                                                    defaultValue={product.variants[0]?.min_quantity || ''}
                                                />
                                                <FieldDescription>
                                                    The minimum quantity of a product variant that can be bought in a single purchase.
                                                </FieldDescription>
                                                {errors.min_quantity ? <FieldError>{errors.min_quantity}</FieldError> : null}
                                            </Field>
                                        </FieldSet>
                                    </CardContent>
                                </Card>

                                <div className="flex gap-2">
                                    <Button type="submit" disabled={processing}>
                                        Submit
                                    </Button>

                                    <Button type="button" variant="outline" asChild>
                                        <Link href={edit(product.id).url}>Cancel</Link>
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            </ProductsLayout>
        </AppLayout>
    );
}
