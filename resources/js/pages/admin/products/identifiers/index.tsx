import AppLayout from '@/layouts/app-layout';
import { Product, TaxClass, type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';

import ProductIdentifierController from '@/actions/App/Http/Controllers/Admin/ProductIdentifierController';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Field, FieldError, FieldLabel, FieldLegend, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import ProductsLayout from '@/layouts/products/layout';
import { edit, index } from '@/routes/admin/products';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: index().url,
    },
];

export default function ProductIdentifierIndex({ product, taxClasses }: { product: Product; taxClasses: TaxClass[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Product Identifiers" />

            <ProductsLayout id_record={product.id}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto">
                    <div className="flex items-center justify-between">
                        <HeadingSmall title="Product Identifers" description="Manage product's SKU and other information" />
                    </div>

                    <Form
                        {...ProductIdentifierController.store.form(product.id)}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <Card className="mt-0">
                                    <CardContent className="space-y-6">
                                        <FieldSet className="grid gap-6">
                                            <FieldLegend className="sr-only">Product Identifiers</FieldLegend>
                                            <Field>
                                                <FieldLabel htmlFor="sku">SKU</FieldLabel>
                                                <Input id="sku" name="sku" placeholder="SKU" defaultValue={product.variants[0]?.sku || ''} />

                                                {errors.sku ? <FieldError>{errors.sku}</FieldError> : null}
                                            </Field>

                                            <Field>
                                                <FieldLabel htmlFor="gtin">Global Trade Item Number (GTIN)</FieldLabel>
                                                <Input id="gtin" name="gtin" placeholder="GTIN" defaultValue={product.variants[0]?.gtin || ''} />

                                                {errors.gtin ? <FieldError>{errors.gtin}</FieldError> : null}
                                            </Field>

                                            <Field>
                                                <FieldLabel htmlFor="mpn">Manufacturer Part Number (MPN)</FieldLabel>
                                                <Input id="mpn" name="mpn" placeholder="MPN" defaultValue={product.variants[0]?.mpn || ''} />

                                                {errors.mpn ? <FieldError>{errors.mpn}</FieldError> : null}
                                            </Field>

                                            <Field>
                                                <FieldLabel htmlFor="ean">UPC/EAN</FieldLabel>
                                                <Input id="ean" name="ean" placeholder="UPC/EAN" defaultValue={product.variants[0]?.ean || ''} />

                                                {errors.ean ? <FieldError>{errors.ean}</FieldError> : null}
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
