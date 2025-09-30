import AppLayout from '@/layouts/app-layout';
import { Media, Product, type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';

import ProductMediaController from '@/actions/App/Http/Controllers/Admin/ProductMediaController';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import ProductsLayout from '@/layouts/products/layout';
import { index } from '@/routes/admin/products';
import { reorder } from '@/routes/admin/products/media';
import { useState } from 'react';
import { columns } from './columns';
import { DataTable } from './data-table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: index().url,
    },
];

export default function ProductMediaIndex({ product, images }: { product: Product; images: Media[] }) {
    const [open, setOpen] = useState(false);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Product Media" />

            <ProductsLayout id_record={product.id}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto">
                    <div className="flex items-center justify-between">
                        <HeadingSmall title="Product Media" description="Manage product's media, upload new or reorder" />

                        <div className="flex items-center gap-2">
                            <Button variant="secondary" asChild>
                                <Link href={reorder({ product: product.id })}>Reorder</Link>
                            </Button>

                            <Dialog open={open} onOpenChange={setOpen}>
                                <DialogTrigger asChild>
                                    <Button>Create media</Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <Form
                                        {...ProductMediaController.store.form(product.id)}
                                        options={{
                                            preserveScroll: true,
                                        }}
                                        resetOnSuccess
                                        onSuccess={() => setOpen(false)}
                                    >
                                        {({ processing, errors }) => (
                                            <>
                                                <DialogHeader>
                                                    <DialogTitle>Create media</DialogTitle>
                                                </DialogHeader>
                                                <div className="my-6 space-y-6">
                                                    <div className="grid gap-2">
                                                        <Label htmlFor="name">Name</Label>

                                                        <Input id="name" name="name" placeholder="Name" />

                                                        <InputError message={errors.name} />
                                                    </div>

                                                    <div className="grid gap-2">
                                                        <Label htmlFor="primary">Primary</Label>

                                                        <Switch id="primary" name="primary" value="1" />

                                                        <InputError message={errors.primary} />
                                                    </div>

                                                    <div className="grid gap-2">
                                                        <Label htmlFor="image">Image</Label>

                                                        <Input type="file" id="image" name="image" placeholder="Image" accept="image/*" />

                                                        <InputError message={errors.name} />
                                                    </div>
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

                    <DataTable columns={columns} data={images} />
                </div>
            </ProductsLayout>
        </AppLayout>
    );
}
