import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

import { index } from '@/routes/devices';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Devices',
        href: index().url,
    },
];

export default function Device() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Devices" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"></div>
        </AppLayout>
    );
}
