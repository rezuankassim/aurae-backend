import TherapyController from '@/actions/App/Http/Controllers/Admin/TherapyController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Field, FieldError, FieldLabel, FieldLegend, FieldSet } from '@/components/ui/field';
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
import { index } from '@/routes/admin/therapies';
import { BreadcrumbItem, Music, Therapy } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Therapies',
        href: index().url,
    },
    {
        title: 'Edit therapy',
        href: '',
    },
];

export default function TherapiesEdit({ therapy, music }: { therapy: Therapy; music: Music[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit therapies" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Edit therapies" description="Change the information of therapy for your system" />
                </div>

                <Form
                    {...TherapyController.update.form(therapy.id)}
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

                                            {therapy.image_url ? (
                                                <img
                                                    src={therapy.image_url}
                                                    alt={therapy.name}
                                                    className="mb-2 aspect-3/2 max-w-sm rounded-md object-cover"
                                                />
                                            ) : null}

                                            <Input type="file" id="image" name="image" placeholder="Image" accept="image/*" />

                                            {errors.image ? <FieldError>{errors.image}</FieldError> : null}
                                        </Field>

                                        <Field>
                                            <FieldLabel htmlFor="name">Name</FieldLabel>
                                            <Input id="name" name="name" placeholder="Name" defaultValue={therapy.name} />

                                            {errors.name ? <FieldError>{errors.name}</FieldError> : null}
                                        </Field>

                                        <Field>
                                            <FieldLabel htmlFor="description">Description</FieldLabel>
                                            <Textarea
                                                id="description"
                                                name="description"
                                                placeholder="Description"
                                                defaultValue={therapy.description || ''}
                                            />

                                            {errors.description ? <FieldError>{errors.description}</FieldError> : null}
                                        </Field>

                                        <Field>
                                            <FieldLabel htmlFor="music_id">Music</FieldLabel>

                                            {therapy.music_url ? (
                                                <MediaPlayer className="h-20">
                                                    <MediaPlayerAudio className="sr-only">
                                                        <source src={therapy.music_url} />
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

                                            <Select name="music_id" defaultValue={therapy.music_id?.toString()}>
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
                                                    defaultValue={therapy.configuration.duration || ''}
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
                                                    defaultValue={therapy.configuration.temperature || ''}
                                                />

                                                {errors.temp ? <FieldError>{errors.temp}</FieldError> : null}
                                            </Field>

                                            <Field>
                                                <FieldLabel htmlFor="light">Light</FieldLabel>
                                                <Select name="light" defaultValue={therapy.configuration.light || 'off'}>
                                                    <SelectTrigger id="light">
                                                        <SelectValue placeholder="Select light" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="on">On</SelectItem>
                                                        <SelectItem value="off">Off</SelectItem>
                                                    </SelectContent>
                                                </Select>

                                                {errors.light ? <FieldError>{errors.light}</FieldError> : null}
                                            </Field>
                                        </div>

                                        <Field>
                                            <FieldLabel htmlFor="color_led">Color LED</FieldLabel>
                                            <Select name="color_led" defaultValue={therapy.configuration.color_led || 'Off'}>
                                                <SelectTrigger id="color_led">
                                                    <SelectValue placeholder="Select color LED" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="Off">Off</SelectItem>
                                                    <SelectItem value="Red">Red</SelectItem>
                                                    <SelectItem value="Orange">Orange</SelectItem>
                                                    <SelectItem value="Yellow">Yellow</SelectItem>
                                                    <SelectItem value="Green">Green</SelectItem>
                                                    <SelectItem value="Blue">Blue</SelectItem>
                                                    <SelectItem value="Purple">Purple</SelectItem>
                                                    <SelectItem value="White">White</SelectItem>
                                                </SelectContent>
                                            </Select>

                                            {errors.color_led ? <FieldError>{errors.color_led}</FieldError> : null}
                                        </Field>

                                        <Field>
                                            <FieldLabel htmlFor="status">Status</FieldLabel>
                                            <Select name="status" defaultValue={therapy.is_active ? '1' : '0'}>
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
