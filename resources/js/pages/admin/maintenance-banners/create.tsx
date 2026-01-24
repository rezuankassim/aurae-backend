import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/react';

import MaintenanceBannerController from '@/actions/App/Http/Controllers/Admin/MaintenanceBannerController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create, index } from '@/routes/admin/maintenance-banners';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Maintenance Banners',
        href: index().url,
    },
    {
        title: 'Create Banner',
        href: create().url,
    },
];

export default function CreateMaintenanceBanner() {
    const [isActive, setIsActive] = useState(true);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Maintenance Banner" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="Create Maintenance Banner" description="Upload a banner image for device maintenance page" />

                <Form
                    {...MaintenanceBannerController.store.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    resetOnSuccess
                    transform={(data) => ({
                        ...data,
                        is_active: isActive,
                    })}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="image">Image *</Label>

                                <Input type="file" id="image" name="image" accept="image/*" required />

                                <InputError message={errors.image} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="title">Title (Optional)</Label>

                                <Input id="title" name="title" placeholder="Banner title" />

                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="order">Display Order</Label>

                                <Input type="number" id="order" name="order" placeholder="0" defaultValue="0" min="0" />

                                <InputError message={errors.order} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="is_active"
                                        name="is_active"
                                        checked={isActive}
                                        onCheckedChange={(checked) => setIsActive(checked as boolean)}
                                    />
                                    <Label htmlFor="is_active" className="cursor-pointer">
                                        Active
                                    </Label>
                                </div>
                                <InputError message={errors.is_active} />
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
