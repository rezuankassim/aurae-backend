import AppLayout from '@/layouts/app-layout';
import { Product, ProductOption, ProductOptionValue, type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';

import ProductVariantController from '@/actions/App/Http/Controllers/Admin/ProductVariantController';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Sortable, SortableContent, SortableItem, SortableItemHandle } from '@/components/ui/sortable';
import ProductsLayout from '@/layouts/products/layout';
import { index } from '@/routes/admin/products';
import { configure } from '@/routes/admin/products/variants';
import { GripVertical } from 'lucide-react';
import { useState } from 'react';
import VariantsValueConfigure from './variants-value-configure';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: index().url,
    },
];

export default function ProductVariantsConfigure({
    product,
    options,
    withVariants,
}: {
    product: Product;
    options: ProductOption[];
    withVariants: boolean;
}) {
    const [optionOrder, setOptionOrder] = useState(options);

    const deleteOrder = (id: number) => {
        setOptionOrder((prev) => prev.filter((option) => option.id !== id));
    };

    const addOption = () => {
        const newId = optionOrder.length > 0 ? Math.max(...optionOrder.map((val) => val.id)) + 1 : 1;
        const newValue = {
            id: newId,
            name: {
                en: '',
            },
            values: [] as ProductOptionValue[],
        } as ProductOption;
        setOptionOrder((prev) => [...prev, newValue]);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Product Variant" />

            <ProductsLayout id_record={product.id} with_variants={withVariants}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto">
                    <div className="flex items-center justify-between">
                        <HeadingSmall title="Product Variant" description="Manage product's variants, create new" />
                    </div>

                    <Form {...ProductVariantController.store.form(product.id)}>
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between">
                                <CardTitle>Product Options</CardTitle>

                                <Button type="button" onClick={addOption}>
                                    Add options
                                </Button>
                            </CardHeader>

                            <CardContent>
                                <Sortable value={optionOrder} onValueChange={setOptionOrder} getItemValue={(item) => item.id}>
                                    <SortableContent asChild>
                                        <div className="grid gap-2">
                                            {optionOrder.map((option) => (
                                                <>
                                                    <SortableItem key={option.id} value={option.id} asChild>
                                                        <div className="flex items-start gap-2">
                                                            <SortableItemHandle asChild>
                                                                <Button variant="ghost" size="icon" className="size-8">
                                                                    <GripVertical className="h-4 w-4" />
                                                                </Button>
                                                            </SortableItemHandle>

                                                            <div className="grid gap-2">
                                                                <Label htmlFor={`${option.id}-name`}>Name</Label>

                                                                <Input
                                                                    id={`${option.id}-name`}
                                                                    name={`${option.id}-name`}
                                                                    placeholder="Name"
                                                                    defaultValue={option.name.en}
                                                                />

                                                                <Button
                                                                    type="button"
                                                                    variant="destructive"
                                                                    onClick={() => deleteOrder(option.id)}
                                                                    className="my-2 w-fit"
                                                                    size="sm"
                                                                >
                                                                    Delete option
                                                                </Button>
                                                            </div>

                                                            <VariantsValueConfigure option={option.id} value={option.values} />
                                                        </div>
                                                    </SortableItem>
                                                </>
                                            ))}
                                        </div>
                                    </SortableContent>
                                </Sortable>
                            </CardContent>
                        </Card>

                        <div className="mt-4 flex items-center gap-2">
                            <Button type="submit">Save options</Button>

                            <Button variant="secondary" asChild>
                                <Link href={configure(product.id)}>Cancel</Link>
                            </Button>
                        </div>
                    </Form>
                </div>
            </ProductsLayout>
        </AppLayout>
    );
}
