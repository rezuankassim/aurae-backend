import TherapyController from '@/actions/App/Http/Controllers/Admin/TherapyController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Field, FieldDescription, FieldError, FieldLabel, FieldLegend, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/admin/therapies';
import { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Therapies',
        href: index().url,
    },
    {
        title: 'Create therapy',
        href: '',
    },
];

export default function TherapiesCreate() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create therapies" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Create therapies" description="Create new therapy for your system" />
                </div>

                <Form
                    {...TherapyController.store.form()}
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
                                            <Input type="file" id="image" name="image" placeholder="Image" accept="image/*" />

                                            {errors.image ? <FieldError>{errors.image}</FieldError> : null}
                                        </Field>

                                        <Field>
                                            <FieldLabel htmlFor="name">Name</FieldLabel>
                                            <Input id="name" name="name" placeholder="Name" />

                                            {errors.name ? <FieldError>{errors.name}</FieldError> : null}
                                        </Field>

                                        <Field>
                                            <FieldLabel htmlFor="description">Description</FieldLabel>
                                            <Textarea id="description" name="description" placeholder="Description" />

                                            {errors.description ? <FieldError>{errors.description}</FieldError> : null}
                                        </Field>

                                        <Field>
                                            <FieldLabel htmlFor="music">Music</FieldLabel>
                                            <Input type="file" id="music" name="music" placeholder="Music" accept="audio/*" />

                                            {errors.music ? <FieldError>{errors.music}</FieldError> : null}
                                        </Field>

                                        <div className="grid grid-cols-3 gap-2">
                                            <Field>
                                                <FieldLabel htmlFor="duration">Duration</FieldLabel>
                                                <Input type="number" id="duration" name="duration" placeholder="Duration" step="0.01" />

                                                {errors.duration ? <FieldError>{errors.duration}</FieldError> : null}
                                            </Field>

                                            <Field>
                                                <FieldLabel htmlFor="temp">Temperature</FieldLabel>
                                                <Input type="number" id="temp" name="temp" placeholder="Temperature" step="0.01" />

                                                {errors.temp ? <FieldError>{errors.temp}</FieldError> : null}
                                            </Field>

                                            <Field>
                                                <FieldLabel htmlFor="light">Light</FieldLabel>
                                                <Input type="number" id="light" name="light" placeholder="Light" step="0.01" />

                                                <FieldDescription>Wait get actual data, then replace this field</FieldDescription>
                                                {errors.light ? <FieldError>{errors.light}</FieldError> : null}
                                            </Field>
                                        </div>

                                        <Field>
                                            <FieldLabel htmlFor="status">Status</FieldLabel>
                                            <Select name="status">
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
