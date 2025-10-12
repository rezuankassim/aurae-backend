import AppLayout from '@/layouts/app-layout';
import { Knowledge, type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/admin/knowledge';
import { columns } from './columns';
import { DataTable } from './data-table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Knowledge Center Management',
        href: index().url,
    },
];

export default function knowledge({ knowledge }: { knowledge: Knowledge[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Knowledge Center Management" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Knowledge Center Management" description="Manage knowledge of the system" />

                    <Button className="mb-6" asChild>
                        <Link href={create().url}>Create knowledge</Link>
                    </Button>
                </div>

                <DataTable columns={columns} data={knowledge} />
            </div>
        </AppLayout>
    );
}
