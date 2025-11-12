import AppLayout from '@/layouts/app-layout';
import { Faq, type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/admin/faqs';
import { columns } from './columns';
import { DataTable } from './data-table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Frequently Asked Questions',
        href: index().url,
    },
];

export default function faqsIndex({ faqs }: { faqs: Faq[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Frequently Asked Questions" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Frequently Asked Questions" description="Manage frequently asked questions of the system" />

                    <Button className="mb-6" asChild>
                        <Link href={create().url}>Create FAQ</Link>
                    </Button>
                </div>

                <DataTable columns={columns} data={faqs} />
            </div>
        </AppLayout>
    );
}
