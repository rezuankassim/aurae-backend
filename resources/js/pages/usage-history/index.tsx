import AppLayout from '@/layouts/app-layout';
import { UsageHistory, type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

import Heading from '@/components/heading';
import usageHistory from '@/routes/usage-history';
import { columns } from './columns';
import { DataTable } from './data-table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Usage History',
        href: usageHistory.index().url,
    },
];

export default function UsageHistoryIndex({ usage_histories }: { usage_histories: UsageHistory[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Usage History" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Usage History" description="View your previous usage" />
                </div>

                <DataTable columns={columns} data={usage_histories} />
            </div>
        </AppLayout>
    );
}
