import AppLayout from '@/layouts/app-layout';
import { News as TNews, type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/admin/news';
import { DataTable } from '../users/data-table';
import { columns } from './columns';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'News',
        href: index().url,
    },
];

export default function News({ newsContent }: { newsContent: TNews[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="News" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="News" description="Manage system's news, create new or publish" />

                    <Button className="mb-6" asChild>
                        <Link href={create().url}>Create news</Link>
                    </Button>
                </div>

                <DataTable columns={columns} data={newsContent} />
            </div>
        </AppLayout>
    );
}
