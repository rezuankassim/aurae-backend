import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/custom-therapies';
import { Therapy, type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { columns } from './columns';
import { DataTable } from './data-table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Custom Therapies',
        href: index().url,
    },
];

export default function CustomTherapiesIndex({ customTherapies }: { customTherapies: Therapy[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Custom Therapies" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Custom Therapies" description="Manage your custom therapies created from the mobile app" />
                </div>

                <DataTable columns={columns} data={customTherapies} />
            </div>
        </AppLayout>
    );
}
