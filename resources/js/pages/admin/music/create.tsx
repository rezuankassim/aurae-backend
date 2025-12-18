import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/react';

import MusicController from '@/actions/App/Http/Controllers/Admin/MusicController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { create, index } from '@/routes/admin/music';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Music',
        href: index().url,
    },
    {
        title: 'Add Music',
        href: create().url,
    },
];

export default function MusicCreate() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add Music" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="Add Music" description="Upload new music file" />

                <Form
                    {...MusicController.store.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    resetOnSuccess
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <Card className="mt-0">
                                <CardContent className="space-y-6">
                                    <div className="grid gap-2">
                                        <Label htmlFor="title">Title</Label>
                                        <Input id="title" name="title" placeholder="Title" />
                                        <InputError message={errors.title} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="thumbnail">Thumbnail Image (Optional)</Label>
                                        <Input type="file" id="thumbnail" name="thumbnail" accept="image/*" />
                                        <InputError message={errors.thumbnail} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="music">Music File</Label>
                                        <Input type="file" id="music" name="music" accept="audio/*" />
                                        <InputError message={errors.music} />
                                    </div>

                                    <div className="flex items-center space-x-2">
                                        <Switch id="is_active" />
                                        <Label htmlFor="is_active">Active</Label>
                                    </div>
                                </CardContent>
                            </Card>

                            <Button type="submit" disabled={processing}>
                                Submit
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
