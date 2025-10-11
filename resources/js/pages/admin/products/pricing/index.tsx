import AppLayout from '@/layouts/app-layout';
import { PriceV, Product, TaxClass, type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';

import ProductPricingController from '@/actions/App/Http/Controllers/Admin/ProductPricingController';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

export default function ProductPricingIndex({
    product,
    taxClasses,
    withVariants,
}: {
    product: Product;
    taxClasses: TaxClass[];
    withVariants: boolean;
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Product Pricing" />

            <ProductsLayout id_record={product.id} with_variants={withVariants}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto">
                    <div className="flex items-center justify-between">
                        <HeadingSmall title="Product Pricing" description="Manage product's pricing and other information" />
                    </div>

                    <Form
                        {...ProductPricingController.store.form(product.id)}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <Card className="mt-0">
                                    <CardContent className="space-y-6">
                                        <FieldSet className="grid grid-cols-2 gap-2">
                                            <FieldLegend className="sr-only">Tax Class</FieldLegend>
                                            <Field>
                                                <FieldLabel htmlFor="tax_class">Tax Class</FieldLabel>
                                                <Select name="tax_class" defaultValue={product.variants[0]?.tax_class?.id?.toString() || ''}>
                                                    <SelectTrigger id="tax_class" className="w-full">
                                                        <SelectValue placeholder="Select tax class" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {taxClasses.map((taxClass) => (
                                                            <SelectItem key={taxClass.id} value={taxClass.id.toString()}>
                                                                {taxClass.name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>

                                                {errors.tax_class ? <FieldError>{errors.tax_class}</FieldError> : null}
                                            </Field>

                                            <Field>
                                                <FieldLabel htmlFor="tax_ref">Tax Reference</FieldLabel>
                                                <Input
                                                    id="tax_ref"
                                                    name="tax_ref"
                                                    placeholder="Tax reference"
                                                    defaultValue={product.variants[0]?.tax_ref || ''}
                                                />
                                                <FieldDescription>Optional, for integration with 3rd party systems.</FieldDescription>
                                                {errors.tax_ref ? <FieldError>{errors.tax_ref}</FieldError> : null}
                                            </Field>
                                        </FieldSet>
                                    </CardContent>
                                </Card>

                                <Card className="mt-0">
                                    <CardHeader>
                                        <CardTitle>Prices</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-6">
                                        <FieldSet className="grid grid-cols-2 gap-2">
                                            <FieldLegend className="sr-only">Pricing</FieldLegend>
                                            <Field>
                                                <FieldLabel htmlFor="price">Price</FieldLabel>
                                                <Input
                                                    type="number"
                                                    id="price"
                                                    name="price"
                                                    placeholder="Price"
                                                    defaultValue={(product.prices?.at(0)?.price as PriceV).value / 100 || ''}
                                                    step="0.01"
                                                />
                                                <FieldDescription>The purchase price, before discounts.</FieldDescription>
                                                {errors.price ? <FieldError>{errors.price}</FieldError> : null}
                                            </Field>
                                            <Field>
                                                <FieldLabel htmlFor="comparison_price">Comparison Price</FieldLabel>
                                                <Input
                                                    type="number"
                                                    id="comparison_price"
                                                    name="comparison_price"
                                                    placeholder="Comparison Price"
                                                    defaultValue={(product.prices?.at(0)?.compare_price as PriceV).value / 100 || ''}
                                                    step="0.01"
                                                />
                                                <FieldDescription>
                                                    The original price or RRP, for comparison with its purchase price.{' '}
                                                </FieldDescription>
                                                {errors.comparison_price ? <FieldError>{errors.comparison_price}</FieldError> : null}
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
