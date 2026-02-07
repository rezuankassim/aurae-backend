import AppLayout from '@/layouts/app-layout';
import { MarketplaceBanner, type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/admin/marketplace-banners';
import { columns } from './columns';
import { DataTable } from './data-table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Marketplace Banners',
        href: index().url,
    },
];

export default function MarketplaceBanners({ banners }: { banners: MarketplaceBanner[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Marketplace Banners" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Marketplace Banners" description="Manage banners displayed on marketplace page" />

                    <Button className="mb-6" asChild>
                        <Link href={create().url}>Create Banner</Link>
                    </Button>
                </div>

                <DataTable columns={columns} data={banners} />
            </div>
        </AppLayout>
    );
}
