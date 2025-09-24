import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/react';

import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create, index } from '@/routes/health-reports';

import HealthReportController from '@/actions/App/Http/Controllers/HealthReportController';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Health Reports',
        href: index().url,
    },
    {
        title: 'Upload new report',
        href: create().url,
    },
];

export default function News() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Upload new report" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="Upload new report" description="Upload a new health report to the system" />

                <Form
                    {...HealthReportController.store.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    resetOnSuccess
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="file">File</Label>

                                <Input type="file" id="file" name="file" placeholder="file URL" accept="application/pdf,.pdf" />

                                <InputError message={errors.file} />
                            </div>

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
