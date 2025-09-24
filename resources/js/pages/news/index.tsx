import AppLayout from '@/layouts/app-layout';
import { News as TNews, type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

import Heading from '@/components/heading';
import news from '@/routes/customers/news-promotions';
import { columns } from './columns';
import { DataTable } from './data-table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'News',
        href: news.index().url,
    },
];

export default function News({ newsContent }: { newsContent: TNews[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="News" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="News" description="View news and promotions from the system" />
                </div>

                <DataTable columns={columns} data={newsContent} />
            </div>
        </AppLayout>
    );
}
