import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/admin/subscription';
import { columns } from './columns';
import { DataTable } from './data-table';

export interface Subscription {
    id: number;
    icon: string | null;
    icon_url?: string | null;
    title: string;
    pricing_title: string;
    description: string | null;
    price: string;
    is_active: boolean;
    senangpay_recurring_id: string | null;
    created_at: string;
    updated_at: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Subscriptions',
        href: index().url,
    },
];

export default function Subscriptions({ subscriptions }: { subscriptions: Subscription[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Subscriptions" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Subscriptions" description="Manage subscription plans for device usage limits" />

                    <Button className="mb-6" asChild>
                        <Link href={create().url}>Create subscription</Link>
                    </Button>
                </div>

                <DataTable columns={columns} data={subscriptions} />
            </div>
        </AppLayout>
    );
}
