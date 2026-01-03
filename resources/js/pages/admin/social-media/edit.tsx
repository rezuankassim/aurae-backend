import SocialMediaController from '@/actions/App/Http/Controllers/Admin/SocialMediaController';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Field, FieldError, FieldLabel, FieldLegend, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { edit } from '@/routes/admin/social-media';
import { BreadcrumbItem, SocialMedia } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Social Media',
        href: edit().url,
    },
];

export default function SocialMediaEdit({ socialMedia }: { socialMedia: SocialMedia }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Social Media" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <HeadingSmall title="Social Media" description="Manage social media links" />

                <Form
                    {...SocialMediaController.update.form()}
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
                                            <FieldLabel htmlFor="facebook">Facebook</FieldLabel>
                                            <Input
                                                id="facebook"
                                                name="facebook"
                                                placeholder="Facebook"
                                                defaultValue={socialMedia.links?.facebook || ''}
                                            />

                                            {errors.facebook ? <FieldError>{errors.facebook}</FieldError> : null}
                                        </Field>

                                        <Field>
                                            <FieldLabel htmlFor="instagram">Instagram</FieldLabel>
                                            <Input
                                                id="instagram"
                                                name="instagram"
                                                placeholder="Instagram"
                                                defaultValue={socialMedia.links?.instagram || ''}
                                            />

                                            {errors.instagram ? <FieldError>{errors.instagram}</FieldError> : null}
                                        </Field>

                                        <Field>
                                            <FieldLabel htmlFor="xhs">Xiao Hong Shu</FieldLabel>
                                            <Input id="xhs" name="xhs" placeholder="Xiao Hong Shu" defaultValue={socialMedia.links?.xhs || ''} />

                                            {errors.xhs ? <FieldError>{errors.xhs}</FieldError> : null}
                                        </Field>
                                    </FieldSet>
                                </CardContent>
                            </Card>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Submit
                                </Button>

                                <Button type="button" variant="outline" asChild>
                                    <Link href={edit().url}>Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
