import AppLayout from '@/layouts/app-layout';
import { Music, type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/react';

import MusicController from '@/actions/App/Http/Controllers/Admin/MusicController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { edit, index } from '@/routes/admin/music';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Music',
        href: index().url,
    },
    {
        title: 'Edit Music',
        href: '', // Placeholder, will be set in component or handled by navigation logic if needed but mostly breadcrumbs are static
    },
];

export default function MusicEdit({ music }: { music: Music }) {
    breadcrumbs[1].href = edit(music.id).url;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Music" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="Edit Music" description="Edit music details" />

                <Form
                    {...MusicController.update.form(music.id)}
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
                                        <Label htmlFor="title">Title</Label>
                                        <Input id="title" name="title" placeholder="Title" defaultValue={music.title} />
                                        <InputError message={errors.title} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="thumbnail">Thumbnail Image (Optional)</Label>
                                        {music.thumbnail_url && (
                                            <div className="mb-2">
                                                <img src={music.thumbnail_url} alt={music.title} className="h-32 w-32 rounded-md object-cover" />
                                            </div>
                                        )}
                                        <Input type="file" id="thumbnail" name="thumbnail" accept="image/*" />
                                        <InputError message={errors.thumbnail} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="music">Music File (Optional - Leave empty to keep current)</Label>
                                        {music.url && (
                                            <div className="mb-2">
                                                <audio controls src={music.url} className="w-full" />
                                            </div>
                                        )}
                                        <Input type="file" id="music" name="music" accept="audio/*" />
                                        <InputError message={errors.music} />
                                    </div>

                                    <div className="flex items-center space-x-2">
                                        <Switch id="is_active" defaultChecked={music.is_active} />
                                        <Label htmlFor="is_active">Active</Label>
                                    </div>
                                </CardContent>
                            </Card>

                            <Button type="submit" disabled={processing}>
                                Save Changes
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
