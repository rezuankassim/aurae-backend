import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { SharedData, type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

export default function Dashboard() {
    const { auth } = usePage<SharedData>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="px-2 py-4">
                    <h1 className="text-2xl font-semibold">Welcome back, {auth.user.name} 👋</h1>
                    <p className="text-muted-foreground text-sm">Here&apos;s what&apos;s happening with your account today.</p>
                </div>
                {}
            </div>
        </AppLayout>
    );
}
