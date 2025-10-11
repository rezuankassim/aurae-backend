import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import UsersLayout from '@/layouts/users/layout';
import { edit, index } from '@/routes/admin/users';
import { BreadcrumbItem, User } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: index().url,
    },
    {
        title: 'Manage user information',
        href: '',
    },
];

export default function UsersShow({ user }: { user: User }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Manage user information" />

            <UsersLayout id_record={user.id}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto">
                    <div className="flex items-center justify-between">
                        <HeadingSmall title="User information" description="Manage user infromation" />

                        <Button asChild>
                            <Link href={edit(user.id)}>Edit user</Link>
                        </Button>
                    </div>

                    <div className="space-y-6">
                        <Card className="mt-0">
                            <CardContent className="space-y-6">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>

                                    <p>{user.name}</p>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email</Label>

                                    <p>{user.email}</p>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="type">Type</Label>

                                    <p>{user.is_admin ? <Badge variant="destructive">Admin</Badge> : <Badge>Customer</Badge>}</p>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="phone">Phone</Label>

                                    <p>{user.phone ? user.phone : '-'}</p>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="status">Status</Label>

                                    <p>{user.status ? <Badge>Active</Badge> : <Badge variant="destructive">Inactive</Badge>}</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </UsersLayout>
        </AppLayout>
    );
}
