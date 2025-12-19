import GeneralSettingController from '@/actions/App/Http/Controllers/Admin/GeneralSettingController';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Field, FieldError, FieldLabel, FieldLegend, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { edit } from '@/routes/admin/general-settings';
import { BreadcrumbItem, GeneralSetting } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'General Settings',
        href: edit().url,
    },
];

export default function GeneralSettingsEdit({ generalSetting }: { generalSetting: GeneralSetting }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="General Settings" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <HeadingSmall title="General Settings" description="Manage general application settings" />

                <Form
                    {...GeneralSettingController.update.form()}
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
                                        <FieldLegend className="sr-only">General Settings</FieldLegend>
                                        <Field>
                                            <FieldLabel htmlFor="contact_no">Contact Number</FieldLabel>
                                            <Input
                                                id="contact_no"
                                                name="contact_no"
                                                placeholder="Contact Number"
                                                defaultValue={generalSetting.contact_no || ''}
                                            />

                                            {errors.contact_no ? <FieldError>{errors.contact_no}</FieldError> : null}
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
