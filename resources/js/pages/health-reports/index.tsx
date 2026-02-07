import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

import Heading from '@/components/heading';
import healthReportsRoute from '@/routes/health-reports';
import { columns, HealthReport } from './columns';
import { DataTable } from './data-table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Health Reports',
        href: healthReportsRoute.index().url,
    },
];

export default function HealthReportIndex({ healthReports }: { healthReports: HealthReport[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Health Reports" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Health Reports" description="View your health reports" />
                </div>

                <DataTable columns={columns} data={healthReports} />
            </div>
        </AppLayout>
    );
}
