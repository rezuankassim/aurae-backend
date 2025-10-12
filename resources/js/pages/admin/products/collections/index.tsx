import AppLayout from '@/layouts/app-layout';
import { Collection, Product, type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/react';

import ProductCollectionController from '@/actions/App/Http/Controllers/Admin/ProductCollectionController';
import { Combobox } from '@/components/combobox';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Field, FieldError, FieldLabel, FieldLegend, FieldSet } from '@/components/ui/field';
import ProductsLayout from '@/layouts/products/layout';
import { index } from '@/routes/admin/products';
import { useState } from 'react';
import { columns } from './columns';
import { DataTable } from './data-table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: index().url,
    },
];

export default function ProductCollectionsIndex({
    product,
    collections,
    withVariants,
}: {
    product: Product;
    collections: Collection[];
    withVariants: boolean;
}) {
    const [open, setOpen] = useState(false);
    const [selectedCollection, setSelectedCollection] = useState<number | null>(null);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Product Collection" />

            <ProductsLayout id_record={product.id} with_variants={withVariants}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto">
                    <div className="flex items-center justify-between">
                        <HeadingSmall title="Product Collection" description="Assign product to a collection or multiple collection" />
                        <div className="flex items-center gap-2">
                            <Dialog open={open} onOpenChange={setOpen}>
                                <DialogTrigger asChild>
                                    <Button>Attach to collection</Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <Form
                                        {...ProductCollectionController.store.form(product.id)}
                                        options={{
                                            preserveScroll: true,
                                        }}
                                        resetOnSuccess
                                        transform={(data) => ({ ...data, collection_id: selectedCollection })}
                                        onSuccess={() => setOpen(false)}
                                    >
                                        {({ processing, errors }) => (
                                            <>
                                                <DialogHeader>
                                                    <DialogTitle>Attach to collection</DialogTitle>
                                                </DialogHeader>
                                                <div className="my-6 space-y-6">
                                                    <FieldSet className="grid gap-6">
                                                        <FieldLegend className="sr-only">Collection</FieldLegend>
                                                        <Field>
                                                            <FieldLabel htmlFor="name">Name</FieldLabel>
                                                            <Combobox
                                                                options={collections.map((collection) => ({
                                                                    label: collection.attribute_data.name.en,
                                                                    value: collection.id,
                                                                }))}
                                                                value={selectedCollection}
                                                                onValueChange={(val) => setSelectedCollection(val as number | null)}
                                                            />

                                                            {errors.name ? <FieldError>{errors.name}</FieldError> : null}
                                                        </Field>
                                                    </FieldSet>
                                                </div>
                                                <DialogFooter>
                                                    <DialogClose asChild>
                                                        <Button variant="outline">Cancel</Button>
                                                    </DialogClose>
                                                    <Button type="submit" disabled={processing}>
                                                        Create
                                                    </Button>
                                                </DialogFooter>
                                            </>
                                        )}
                                    </Form>
                                </DialogContent>
                            </Dialog>
                        </div>
                    </div>

                    <DataTable columns={columns} data={product.collections || []} />
                </div>
            </ProductsLayout>
        </AppLayout>
    );
}
