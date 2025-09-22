import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

import { index } from '@/routes/order-history';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Order History',
        href: index().url,
    },
];

export default function OrderHistory() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Order History" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"></div>
        </AppLayout>
    );
}
