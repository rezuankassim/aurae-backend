import AppLayout from '@/layouts/app-layout';
import { UsageHistory, type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

import Heading from '@/components/heading';
import usageHistory from '@/routes/usage-history';
import dayjs from 'dayjs';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Usage History',
        href: usageHistory.index().url,
    },
    {
        title: 'Show Usage History',
        href: '#',
    },
];

export default function UsageHistoryShow({ usageHistory }: { usageHistory: UsageHistory }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Show Usage History" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Show Usage History" description="Check your previous usage details" />
                </div>

                <div className="space-y-6">
                    <div className="grid gap-2">
                        <h2 className="text-sm font-medium">Created at</h2>

                        <p className="text-sm text-muted-foreground">{dayjs(usageHistory.created_at).format('DD MMM YYYY, HH:mm')}</p>
                    </div>

                    <div className="grid gap-2">
                        <h2 className="text-sm font-medium">Details</h2>

                        <pre className="w-full rounded-lg bg-muted p-4 text-sm">
                            <code>{JSON.stringify(usageHistory.content, null, 2)}</code>
                        </pre>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
