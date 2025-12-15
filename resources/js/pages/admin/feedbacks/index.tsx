import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/admin/feedbacks';
import { Feedback, type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { columns } from './columns';
import { DataTable } from './data-table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Feedbacks',
        href: index().url,
    },
];

export default function FeedbacksIndex({ feedbacks }: { feedbacks: Feedback[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Feedbacks" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Feedbacks" description="View feedbacks submitted from users" />
                </div>

                <DataTable columns={columns} data={feedbacks} />
            </div>
        </AppLayout>
    );
}
