import AppLayout from '@/layouts/app-layout';
import { HealthReport, type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import healthReports, { create } from '@/routes/health-reports';
import { columns } from './columns';
import { DataTable } from './data-table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Health Reports',
        href: healthReports.index().url,
    },
];

export default function HealthReportIndex({ healthReports }: { healthReports: HealthReport[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Health Reports" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Health Reports" description="View your previous uploaded health reports or upload new one" />

                    <Button className="mb-6" asChild>
                        <Link href={create().url}>Upload new report</Link>
                    </Button>
                </div>

                <DataTable columns={columns} data={healthReports} />
            </div>
        </AppLayout>
    );
}
