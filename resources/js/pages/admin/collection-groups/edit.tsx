import CollectionGroupCollectionController from '@/actions/App/Http/Controllers/Admin/CollectionGroupCollectionController';
import CollectionGroupController from '@/actions/App/Http/Controllers/Admin/CollectionGroupController';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogClose, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Field, FieldError, FieldLabel, FieldLegend, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { ItemGroup } from '@/components/ui/item';
import AppLayout from '@/layouts/app-layout';
import { slugify } from '@/lib/utils';
import { index } from '@/routes/admin/collection-groups';
import { BreadcrumbItem, CollectionGroup } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { Collection } from './collection';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Collection Groups',
        href: index().url,
    },
    {
        title: 'Manage collection group',
        href: '',
    },
];

export default function CollectionGroupEdit({ group }: { group: CollectionGroup }) {
    const [handle, setHandle] = useState(group.handle);
    const [open, setOpen] = useState(false);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Manage collection group" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <HeadingSmall title="Manage collection group" description="Manage collection group information and children" />

                <Form
                    {...CollectionGroupController.update.form(group.id)}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <Card className="mt-0">
                                <CardContent className="space-y-6">
                                    <FieldSet className="grid grid-cols-2 gap-6">
                                        <FieldLegend className="sr-only">Collection Group</FieldLegend>
                                        <Field>
                                            <FieldLabel htmlFor="name">Name</FieldLabel>
                                            <Input
                                                id="name"
                                                name="name"
                                                placeholder="Name"
                                                onChange={(e) => setHandle(slugify(e.target.value))}
                                                defaultValue={group.name}
                                            />

                                            {errors.name ? <FieldError>{errors.name}</FieldError> : null}
                                        </Field>

                                        <Field>
                                            <FieldLabel htmlFor="handle">Handle</FieldLabel>
                                            <Input
                                                id="handle"
                                                name="handle"
                                                placeholder="Handle"
                                                onChange={(e) => setHandle(slugify(e.target.value))}
                                                defaultValue={handle}
                                            />

                                            {errors.handle ? <FieldError>{errors.handle}</FieldError> : null}
                                        </Field>
                                    </FieldSet>
                                </CardContent>
                            </Card>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Submit
                                </Button>

                                <Button type="button" variant="outline" asChild>
                                    <Link href={index().url}>Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <div className="mt-6">
                    <div className="flex items-center justify-end">
                        <Dialog open={open} onOpenChange={setOpen}>
                            <DialogTrigger asChild>
                                <Button>Create collection</Button>
                            </DialogTrigger>
                            <DialogContent>
                                <Form
                                    {...CollectionGroupCollectionController.store.form(group.id)}
                                    options={{
                                        preserveScroll: true,
                                    }}
                                    className="space-y-6"
                                    onSuccess={() => setOpen(false)}
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <DialogHeader>
                                                <DialogTitle>Create collection</DialogTitle>
                                            </DialogHeader>

                                            <div className="my-6 space-y-6">
                                                <FieldSet className="grid gap-6">
                                                    <FieldLegend className="sr-only">Collection</FieldLegend>
                                                    <Field>
                                                        <FieldLabel htmlFor="name">Name</FieldLabel>
                                                        <Input id="name" name="name" placeholder="Name" />

                                                        {errors.name ? <FieldError>{errors.name}</FieldError> : null}
                                                    </Field>
                                                </FieldSet>
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

                    <div className="mt-4">
                        <ItemGroup>
                            <div className="grid gap-2">
                                <Collection collections={group.collections?.filter((collection) => !collection.parent_id) || []} />
                            </div>
                        </ItemGroup>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
