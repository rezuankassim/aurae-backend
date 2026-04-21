import Heading from '@/components/heading';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { create, index } from '@/routes/admin/user-subscriptions';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { columns } from './columns';
import { DataTable } from './data-table';

export interface UserSubscription {
    id: number;
    user_id: number;
    subscription_id: number;
    starts_at: string | null;
    ends_at: string | null;
    status: 'pending' | 'active' | 'expired' | 'cancelled';
    payment_method: string | null;
    payment_status: 'pending' | 'completed' | 'failed';
    transaction_id: string | null;
    paid_at: string | null;
    is_recurring: boolean;
    next_billing_at: string | null;
    cancelled_at: string | null;
    created_at: string;
    updated_at: string;
    user: {
        id: number;
        name: string;
        email: string;
    };
    subscription: {
        id: number;
        title: string;
        price: string;
    };
}

interface Props {
    userSubscriptions: {
        data: UserSubscription[];
        links: any;
        meta: any;
    };
    filters: {
        search?: string;
        status?: string;
        payment_status?: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'User Subscriptions',
        href: index().url,
    },
];

export default function UserSubscriptions({ userSubscriptions, filters }: Props) {
    const { flash } = usePage().props as any;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="User Subscriptions" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="User Subscriptions" description="View and manage user subscriptions" />
                    <Button asChild>
                        <Link href={create().url}>
                            <Plus className="mr-2 h-4 w-4" />
                            Create B2B Subscription
                        </Link>
                    </Button>
                </div>

                {flash?.success && (
                    <Alert>
                        <AlertTitle>Success</AlertTitle>
                        <AlertDescription>{flash.success}</AlertDescription>
                    </Alert>
                )}

                <DataTable columns={columns} data={userSubscriptions.data} filters={filters} />
            </div>
        </AppLayout>
    );
}
