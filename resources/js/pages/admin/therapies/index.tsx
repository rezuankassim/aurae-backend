import AppLayout from '@/layouts/app-layout';
import { Therapy, type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/admin/therapies';
import { columns } from './columns';
import { DataTable } from './data-table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Therapies',
        href: index().url,
    },
];

export default function TherapiesIndex({ therapies }: { therapies: Therapy[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Therapies" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Therapies" description="Manage therapy of the system and view details" />

                    <Button asChild>
                        <Link href={create().url}>Create therapy</Link>
                    </Button>
                </div>

                <DataTable columns={columns} data={therapies} />
            </div>
        </AppLayout>
    );
}
