import GeneralSettingController from '@/actions/App/Http/Controllers/Admin/GeneralSettingController';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Field, FieldError, FieldLabel, FieldLegend, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import UploadProgress from '@/components/upload-progress';
import AppLayout from '@/layouts/app-layout';
import { edit } from '@/routes/admin/general-settings';
import { BreadcrumbItem, GeneralSetting } from '@/types';
import { Form, Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'General Settings',
        href: edit().url,
    },
];

export default function GeneralSettingsEdit({ generalSetting }: { generalSetting: GeneralSetting }) {
    const [uploadProgress, setUploadProgress] = useState(0);
    const [isUploading, setIsUploading] = useState(false);

    useEffect(() => {
        const handleBeforeUnload = (e: BeforeUnloadEvent) => {
            if (isUploading) {
                e.preventDefault();
                e.returnValue = 'File upload is in progress. Are you sure you want to leave?';
                return e.returnValue;
            }
        };

        window.addEventListener('beforeunload', handleBeforeUnload);

        return () => {
            window.removeEventListener('beforeunload', handleBeforeUnload);
        };
    }, [isUploading]);

    useEffect(() => {
        const progressListener = router.on('progress', (event) => {
            if (event.detail.progress) {
                setUploadProgress(Math.round(event.detail.progress.percentage));
                setIsUploading(true);
            }
        });

        const finishListener = router.on('finish', () => {
            setIsUploading(false);
            setUploadProgress(0);
        });

        return () => {
            progressListener();
            finishListener();
        };
    }, []);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="General Settings" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <HeadingSmall title="General Settings" description="Manage general application settings" />

                <UploadProgress progress={uploadProgress} isUploading={isUploading} />

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

                            <Card>
                                <CardContent className="space-y-6">
                                    <div className="space-y-2">
                                        <h3 className="text-lg font-medium">Mobile App APK Management</h3>
                                        <p className="text-sm text-muted-foreground">Upload and manage the mobile application APK file</p>
                                    </div>

                                    {generalSetting.apk_file_path && (
                                        <div className="rounded-lg border border-muted bg-muted/50 p-4">
                                            <div className="space-y-1">
                                                <p className="text-sm font-medium">Current APK</p>
                                                <p className="text-sm text-muted-foreground">Version: {generalSetting.apk_version || 'N/A'}</p>
                                                <p className="text-sm text-muted-foreground">
                                                    Size:{' '}
                                                    {generalSetting.apk_file_size
                                                        ? `${(generalSetting.apk_file_size / 1024 / 1024).toFixed(2)} MB`
                                                        : 'N/A'}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    <FieldSet className="grid grid-cols-2 gap-6">
                                        <FieldLegend className="sr-only">APK Settings</FieldLegend>
                                        <Field className="col-span-2">
                                            <FieldLabel htmlFor="apk_file">APK File</FieldLabel>
                                            <Input id="apk_file" name="apk_file" type="file" accept=".apk" />
                                            <p className="mt-1 text-sm text-muted-foreground">Maximum file size: 500MB</p>
                                            {errors.apk_file ? <FieldError>{errors.apk_file}</FieldError> : null}
                                        </Field>

                                        <Field>
                                            <FieldLabel htmlFor="apk_version">APK Version</FieldLabel>
                                            <Input
                                                id="apk_version"
                                                name="apk_version"
                                                placeholder="e.g., 1.0.0"
                                                defaultValue={generalSetting.apk_version || ''}
                                            />
                                            <p className="mt-1 text-sm text-muted-foreground">Semantic version format (e.g., 1.0.0)</p>
                                            {errors.apk_version ? <FieldError>{errors.apk_version}</FieldError> : null}
                                        </Field>

                                        <Field className="col-span-2">
                                            <FieldLabel htmlFor="apk_release_notes">Release Notes</FieldLabel>
                                            <Textarea
                                                id="apk_release_notes"
                                                name="apk_release_notes"
                                                placeholder="What's new in this version..."
                                                defaultValue={generalSetting.apk_release_notes || ''}
                                                rows={4}
                                            />
                                            {errors.apk_release_notes ? <FieldError>{errors.apk_release_notes}</FieldError> : null}
                                        </Field>
                                    </FieldSet>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardContent className="space-y-6">
                                    <div className="space-y-2">
                                        <h3 className="text-lg font-medium">Tablet App APK Management</h3>
                                        <p className="text-sm text-muted-foreground">Upload and manage the tablet application APK file</p>
                                    </div>

                                    {generalSetting.tablet_apk_file_path && (
                                        <div className="rounded-lg border border-muted bg-muted/50 p-4">
                                            <div className="space-y-1">
                                                <p className="text-sm font-medium">Current Tablet APK</p>
                                                <p className="text-sm text-muted-foreground">Version: {generalSetting.tablet_apk_version || 'N/A'}</p>
                                                <p className="text-sm text-muted-foreground">
                                                    Size:{' '}
                                                    {generalSetting.tablet_apk_file_size
                                                        ? `${(generalSetting.tablet_apk_file_size / 1024 / 1024).toFixed(2)} MB`
                                                        : 'N/A'}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    <FieldSet className="grid grid-cols-2 gap-6">
                                        <FieldLegend className="sr-only">Tablet APK Settings</FieldLegend>
                                        <Field className="col-span-2">
                                            <FieldLabel htmlFor="tablet_apk_file">Tablet APK File</FieldLabel>
                                            <Input id="tablet_apk_file" name="tablet_apk_file" type="file" accept=".apk" />
                                            <p className="mt-1 text-sm text-muted-foreground">Maximum file size: 500MB</p>
                                            {errors.tablet_apk_file ? <FieldError>{errors.tablet_apk_file}</FieldError> : null}
                                        </Field>

                                        <Field>
                                            <FieldLabel htmlFor="tablet_apk_version">Tablet APK Version</FieldLabel>
                                            <Input
                                                id="tablet_apk_version"
                                                name="tablet_apk_version"
                                                placeholder="e.g., 1.0.0"
                                                defaultValue={generalSetting.tablet_apk_version || ''}
                                            />
                                            <p className="mt-1 text-sm text-muted-foreground">Semantic version format (e.g., 1.0.0)</p>
                                            {errors.tablet_apk_version ? <FieldError>{errors.tablet_apk_version}</FieldError> : null}
                                        </Field>

                                        <Field className="col-span-2">
                                            <FieldLabel htmlFor="tablet_apk_release_notes">Tablet Release Notes</FieldLabel>
                                            <Textarea
                                                id="tablet_apk_release_notes"
                                                name="tablet_apk_release_notes"
                                                placeholder="What's new in this version..."
                                                defaultValue={generalSetting.tablet_apk_release_notes || ''}
                                                rows={4}
                                            />
                                            {errors.tablet_apk_release_notes ? <FieldError>{errors.tablet_apk_release_notes}</FieldError> : null}
                                        </Field>
                                    </FieldSet>
                                </CardContent>
                            </Card>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing || isUploading}>
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
