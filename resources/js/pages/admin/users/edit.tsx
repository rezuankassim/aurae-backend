import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import UsersLayout from '@/layouts/users/layout';
import { index, show } from '@/routes/admin/users';
import { BreadcrumbItem, User } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';

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

export default function UsersEdit({ user }: { user: User }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Manage user information" />

            <UsersLayout id_record={user.id}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto">
                    <HeadingSmall title="User information" description="Manage user infromation" />

                    <Form
                        {...UserController.update.form(user.id)}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <Card className="mt-0">
                                    <CardContent className="space-y-6">
                                        <div className="grid gap-2">
                                            <Label htmlFor="name">Name</Label>

                                            <Input id="name" name="name" defaultValue={user.name} />

                                            <InputError message={errors.name} />
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

                                            <Select name="status" defaultValue={String(user.status)}>
                                                <SelectTrigger id="status">
                                                    <SelectValue placeholder="Select status" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="1">Active</SelectItem>
                                                    <SelectItem value="0">Inactive</SelectItem>
                                                </SelectContent>
                                            </Select>

                                            <InputError message={errors.status} />
                                        </div>
                                    </CardContent>
                                </Card>

                                <div className="flex gap-2">
                                    <Button type="submit" disabled={processing}>
                                        Submit
                                    </Button>

                                    <Button type="button" variant="outline" asChild>
                                        <Link href={show(user.id).url}>Cancel</Link>
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            </UsersLayout>
        </AppLayout>
    );
}
