import AppLayout from '@/layouts/app-layout';
import { Product, ProductType, type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/react';

import ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { InputGroup, InputGroupAddon, InputGroupInput } from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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

export default function ProductsIndex({
    products,
    draftCount,
    productType,
}: {
    products: Product[];
    draftCount: number;
    productType: ProductType[];
}) {
    const [open, setOpen] = useState(false);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Products" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Products" description="Manage system's products, create new or publish" />

                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button>Create product</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <Form
                                {...ProductController.store.form()}
                                options={{
                                    preserveScroll: true,
                                }}
                                resetOnSuccess
                                onSuccess={() => setOpen(false)}
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <DialogHeader>
                                            <DialogTitle>Create product</DialogTitle>
                                        </DialogHeader>
                                        <div className="my-6 space-y-6">
                                            <div className="grid gap-2">
                                                <Label htmlFor="name">Name</Label>

                                                <Input id="name" name="name" placeholder="Name" />

                                                <InputError message={errors.name} />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="sku">SKU</Label>

                                                <Input id="sku" name="sku" placeholder="SKU" />

                                                <InputError message={errors.sku} />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="product_type">Product Type</Label>
                                                <Select name="type">
                                                    <SelectTrigger id="product_type" name="product_type">
                                                        <SelectValue placeholder="Select product type" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {productType.map((type) => (
                                                            <SelectItem key={type.id} value={String(type.id)}>
                                                                {type.name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="base_price">Base Price</Label>

                                                <InputGroup>
                                                    <InputGroupAddon>RM</InputGroupAddon>
                                                    <InputGroupInput type="number" id="base_price" name="base_price" placeholder="Price" />
                                                </InputGroup>
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

                <DataTable columns={columns} data={products} draftCount={draftCount} />
            </div>
        </AppLayout>
    );
}
