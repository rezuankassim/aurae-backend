import AppLayout from '@/layouts/app-layout';
import { Media, Product, type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/react';

import ProductMediaController from '@/actions/App/Http/Controllers/Admin/ProductMediaController';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Sortable, SortableContent, SortableItem, SortableItemHandle } from '@/components/ui/sortable';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import ProductsLayout from '@/layouts/products/layout';
import { index } from '@/routes/admin/products';
import { CheckCircle2, CircleX, GripVertical } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: index().url,
    },
];

export default function ProductMediaReorder({ product, images, withVariants }: { product: Product; images: Media[]; withVariants: boolean }) {
    const [imagesOrder, setImagesOrder] = useState(images);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Product Media" />

            <ProductsLayout id_record={product.id} with_variants={withVariants}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto">
                    <div className="flex items-center justify-between">
                        <HeadingSmall title="Product Media" description="Manage product's media, upload new or reorder" />

                        <div className="flex items-center gap-2">
                            <Form
                                {...ProductMediaController.saveReorder.form(product.id)}
                                options={{
                                    preserveScroll: true,
                                }}
                                transform={(data) => ({
                                    ...data,
                                    order: imagesOrder.map((image) => image.id),
                                })}
                            >
                                <Button type="submit">Save order</Button>
                            </Form>
                        </div>
                    </div>

                    <Sortable value={imagesOrder} onValueChange={setImagesOrder} getItemValue={(item) => item.id}>
                        <div className="overflow-hidden rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-[50px]" />
                                        <TableHead>File</TableHead>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Primary</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <SortableContent asChild>
                                    <TableBody>
                                        {imagesOrder.map((image) => (
                                            <SortableItem key={image.id} value={image.id} asChild>
                                                <TableRow>
                                                    <TableCell className="w-[50px] cursor-move">
                                                        <SortableItemHandle asChild>
                                                            <Button variant="ghost" size="icon" className="size-8">
                                                                <GripVertical className="h-4 w-4" />
                                                            </Button>
                                                        </SortableItemHandle>
                                                    </TableCell>
                                                    <TableCell>{image.file_name}</TableCell>
                                                    <TableCell>{image.custom_properties.name as string}</TableCell>
                                                    <TableCell>
                                                        {(image.custom_properties.primary as boolean) ? (
                                                            <CheckCircle2 className="size-4 text-green-400" />
                                                        ) : (
                                                            <CircleX className="size-4 text-red-400" />
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            </SortableItem>
                                        ))}
                                    </TableBody>
                                </SortableContent>
                            </Table>
                        </div>
                    </Sortable>
                </div>
            </ProductsLayout>
        </AppLayout>
    );
}
