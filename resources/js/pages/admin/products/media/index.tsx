import AppLayout from '@/layouts/app-layout';
import { Media, Product, type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

import HeadingSmall from '@/components/heading-small';
import ProductsLayout from '@/layouts/products/layout';
import { index } from '@/routes/admin/products';
import { columns } from './columns';
import { DataTable } from './data-table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: index().url,
    },
];

export default function ProductMediaIndex({ product, images }: { product: Product; images: Media[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Product Media" />

            <ProductsLayout id_record={product.id}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto">
                    <div className="flex items-center justify-between">
                        <HeadingSmall title="Product Media" description="Manage product's media, upload new or reorder" />
                    </div>

                    <DataTable columns={columns} data={images} />
                </div>
            </ProductsLayout>
        </AppLayout>
    );
}
