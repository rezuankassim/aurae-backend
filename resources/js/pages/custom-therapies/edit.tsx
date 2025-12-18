import CustomTherapyController from '@/actions/App/Http/Controllers/CustomTherapyController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Field, FieldDescription, FieldError, FieldLabel, FieldLegend, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    MediaPlayer,
    MediaPlayerAudio,
    MediaPlayerControls,
    MediaPlayerLoop,
    MediaPlayerPlay,
    MediaPlayerPlaybackSpeed,
    MediaPlayerSeek,
    MediaPlayerSeekBackward,
    MediaPlayerSeekForward,
    MediaPlayerVolume,
} from '@/components/ui/media-player';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/custom-therapies';
import { BreadcrumbItem, Music, Therapy } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Custom Therapies',
        href: index().url,
    },
    {
        title: 'Edit custom therapy',
        href: '',
    },
];

export default function CustomTherapiesEdit({ customTherapy, music }: { customTherapy: Therapy; music: Music[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit custom therapy" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Edit custom therapy" description="Change the information of your custom therapy" />
                </div>

                <Form
                    {...CustomTherapyController.update.form(customTherapy.id)}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <Card className="mt-0">
                                <CardContent className="space-y-6">
                                    <FieldSet className="grid gap-6">
                                        <FieldLegend className="sr-only">Therapy Information</FieldLegend>
                                        <Field>
                                            <FieldLabel htmlFor="image">Image</FieldLabel>

                                            {customTherapy.image_url ? (
                                                <img
                                                    src={customTherapy.image_url}
                                                    alt={customTherapy.name}
                                                    className="mb-2 aspect-3/2 max-w-sm rounded-md object-cover"
                                                />
                                            ) : null}

                                            <Input type="file" id="image" name="image" placeholder="Image" accept="image/*" />

                                            {errors.image ? <FieldError>{errors.image}</FieldError> : null}
                                        </Field>

                                        <Field>
                                            <FieldLabel htmlFor="name">Name</FieldLabel>
                                            <Input id="name" name="name" placeholder="Name" defaultValue={customTherapy.name} />

                                            {errors.name ? <FieldError>{errors.name}</FieldError> : null}
                                        </Field>

                                        <Field>
                                            <FieldLabel htmlFor="description">Description</FieldLabel>
                                            <Textarea
                                                id="description"
                                                name="description"
                                                placeholder="Description"
                                                defaultValue={customTherapy.description || ''}
                                            />

                                            {errors.description ? <FieldError>{errors.description}</FieldError> : null}
                                        </Field>

                                        <Field>
                                            <FieldLabel htmlFor="music_id">Music</FieldLabel>

                                            {customTherapy.music_url ? (
                                                <MediaPlayer className="h-20">
                                                    <MediaPlayerAudio className="sr-only">
                                                        <source src={customTherapy.music_url} />
                                                    </MediaPlayerAudio>
                                                    <MediaPlayerControls className="flex-col items-start gap-2.5">
                                                        <MediaPlayerSeek withTime />
                                                        <div className="flex w-full items-center justify-center gap-2">
                                                            <MediaPlayerSeekBackward />
                                                            <MediaPlayerPlay />
                                                            <MediaPlayerSeekForward />
                                                            <MediaPlayerVolume />
                                                            <MediaPlayerPlaybackSpeed />
                                                            <MediaPlayerLoop />
                                                        </div>
                                                    </MediaPlayerControls>
                                                </MediaPlayer>
                                            ) : null}

                                            <Select name="music_id" defaultValue={customTherapy.music_id?.toString()}>
                                                <SelectTrigger id="music_id">
                                                    <SelectValue placeholder="Select music" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {music.map((item) => (
                                                        <SelectItem key={item.id} value={item.id.toString()}>
                                                            {item.title}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>

                                            {errors.music_id ? <FieldError>{errors.music_id}</FieldError> : null}
                                        </Field>

                                        <div className="grid grid-cols-3 gap-2">
                                            <Field>
                                                <FieldLabel htmlFor="duration">Duration</FieldLabel>
                                                <Input
                                                    type="number"
                                                    id="duration"
                                                    name="duration"
                                                    placeholder="Duration"
                                                    step="0.01"
                                                    defaultValue={customTherapy.configuration.duration || ''}
                                                />

                                                {errors.duration ? <FieldError>{errors.duration}</FieldError> : null}
                                            </Field>

                                            <Field>
                                                <FieldLabel htmlFor="temp">Temperature</FieldLabel>
                                                <Input
                                                    type="number"
                                                    id="temp"
                                                    name="temp"
                                                    placeholder="Temperature"
                                                    step="0.01"
                                                    defaultValue={customTherapy.configuration.temperature || ''}
                                                />

                                                {errors.temp ? <FieldError>{errors.temp}</FieldError> : null}
                                            </Field>

                                            <Field>
                                                <FieldLabel htmlFor="light">Light</FieldLabel>
                                                <Input
                                                    type="number"
                                                    id="light"
                                                    name="light"
                                                    placeholder="Light"
                                                    step="0.01"
                                                    defaultValue={customTherapy.configuration.light || ''}
                                                />

                                                <FieldDescription>Wait get actual data, then replace this field</FieldDescription>
                                                {errors.light ? <FieldError>{errors.light}</FieldError> : null}
                                            </Field>
                                        </div>

                                        <Field>
                                            <FieldLabel htmlFor="status">Status</FieldLabel>
                                            <Select name="status" defaultValue={customTherapy.is_active ? '1' : '0'}>
                                                <SelectTrigger id="status">
                                                    <SelectValue placeholder="Select status" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="1">Active</SelectItem>
                                                    <SelectItem value="0">Inactive</SelectItem>
                                                </SelectContent>
                                            </Select>

                                            {errors.status ? <FieldError>{errors.status}</FieldError> : null}
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
            </div>
        </AppLayout>
    );
}
