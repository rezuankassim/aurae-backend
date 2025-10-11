import HeadingSmall from '@/components/heading-small';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import UsersLayout from '@/layouts/users/layout';
import { index } from '@/routes/admin/users';
import { BreadcrumbItem, LoginActivity, User } from '@/types';
import { Head } from '@inertiajs/react';
import dayjs from 'dayjs';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: index().url,
    },
    {
        title: 'Login Activities',
        href: '',
    },
];

export default function UsersLoginActivityIndex({ user, loginActivities }: { user: User; loginActivities: LoginActivity[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="View user login activities" />

            <UsersLayout id_record={user.id}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto">
                    <HeadingSmall title="User login activities" description="View user login activities" />

                    <div className="overflow-hidden rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Login at</TableHead>
                                    <TableHead>IP Address</TableHead>
                                    <TableHead>Logout at</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {loginActivities.map((login) => (
                                    <TableRow>
                                        <TableCell>{dayjs(login.occurred_at).format('DD MMM YYYY, HH:mm')}</TableCell>
                                        <TableCell>{login.ip_address}</TableCell>
                                        <TableCell>{login.logout_at ? dayjs(login.logout_at).format('DD MMM YYYY, HH:mm') : '-'}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </UsersLayout>
        </AppLayout>
    );
}
