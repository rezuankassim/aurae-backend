import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { create, index } from '@/routes/admin/health-reports';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

import { columns } from './columns';
import { DataTable } from './data-table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Health Reports',
        href: index().url,
    },
];

interface HealthReport {
    id: string;
    full_body_file: string | null;
    full_body_file_name: string | null;
    full_body_file_url: string | null;
    meridian_file: string | null;
    meridian_file_name: string | null;
    meridian_file_url: string | null;
    multidimensional_file: string | null;
    multidimensional_file_name: string | null;
    multidimensional_file_url: string | null;
    user: {
        id: number;
        name: string;
        email: string;
    };
    created_at: string;
    updated_at: string;
}

export default function HealthReportsIndex({ healthReports }: { healthReports: HealthReport[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Health Reports" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Health Reports" description="Manage health reports for users" />

                    <Button asChild>
                        <Link href={create().url}>Upload Report</Link>
                    </Button>
                </div>

                <DataTable columns={columns} data={healthReports} />
            </div>
        </AppLayout>
    );
}
