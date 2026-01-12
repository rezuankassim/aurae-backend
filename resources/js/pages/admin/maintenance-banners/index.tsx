import AppLayout from '@/layouts/app-layout';
import { MaintenanceBanner, type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/admin/maintenance-banners';
import { columns } from './columns';
import { DataTable } from './data-table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Maintenance Banners',
        href: index().url,
    },
];

export default function MaintenanceBanners({ banners }: { banners: MaintenanceBanner[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Maintenance Banners" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Maintenance Banners" description="Manage banners displayed on device maintenance page" />

                    <Button className="mb-6" asChild>
                        <Link href={create().url}>Create Banner</Link>
                    </Button>
                </div>

                <DataTable columns={columns} data={banners} />
            </div>
        </AppLayout>
    );
}
