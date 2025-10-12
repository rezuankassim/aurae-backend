import AppLayout from '@/layouts/app-layout';
import { CollectionGroup, type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/react';

import CollectionGroupController from '@/actions/App/Http/Controllers/Admin/CollectionGroupController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { index } from '@/routes/admin/collection-groups';
import { useState } from 'react';
import { columns } from './columns';
import { DataTable } from './data-table';

import { slugify } from '@/lib/utils';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Collection Groups',
        href: index().url,
    },
];

export default function CollectionGroupsIndex({ groups }: { groups: CollectionGroup[] }) {
    const [open, setOpen] = useState(false);
    const [handle, setHandle] = useState('');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Collection Groups" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Collection Groups" description="Manage collection groups, create and manage children" />

                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button>Create collection group</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <Form
                                {...CollectionGroupController.store.form()}
                                options={{
                                    preserveScroll: true,
                                }}
                                resetOnSuccess
                                onSuccess={() => setOpen(false)}
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <DialogHeader>
                                            <DialogTitle>Create collection group</DialogTitle>
                                        </DialogHeader>
                                        <div className="my-6 space-y-6">
                                            <Field>
                                                <FieldLabel htmlFor="name">Name</FieldLabel>
                                                <Input
                                                    id="name"
                                                    name="name"
                                                    placeholder="Name"
                                                    onChange={(e) => setHandle(slugify(e.target.value))}
                                                />

                                                {errors.name ? <FieldError>{errors.name}</FieldError> : null}
                                            </Field>

                                            <Field>
                                                <FieldLabel htmlFor="handle">Handle</FieldLabel>
                                                <Input id="handle" name="handle" placeholder="Handle" defaultValue={handle} />

                                                {errors.handle ? <FieldError>{errors.handle}</FieldError> : null}
                                            </Field>
                                        </div>
                                        <DialogFooter>
                                            <DialogClose asChild>
                                                <Button variant="outline">Cancel</Button>
                                            </DialogClose>
                                            <Button type="submit" disabled={processing}>
                                                Create
                                            </Button>
                                        </DialogFooter>
                                    </>
                                )}
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>

                <DataTable columns={columns} data={groups} />
            </div>
        </AppLayout>
    );
}
