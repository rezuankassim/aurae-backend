import AppLayout from '@/layouts/app-layout';
import { MaintenanceBanner, type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/react';

import MaintenanceBannerController from '@/actions/App/Http/Controllers/Admin/MaintenanceBannerController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit, index } from '@/routes/admin/maintenance-banners';

const breadcrumbs = (banner: MaintenanceBanner): BreadcrumbItem[] => [
    {
        title: 'Maintenance Banners',
        href: index().url,
    },
    {
        title: 'Edit Banner',
        href: edit(banner.id).url,
    },
];

export default function EditMaintenanceBanner({ banner }: { banner: MaintenanceBanner }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs(banner)}>
            <Head title="Edit Maintenance Banner" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="Edit Maintenance Banner" description="Update banner image and settings" />

                <Form
                    {...MaintenanceBannerController.update.form(banner.id)}
                    data={{
                        title: banner.title || '',
                        order: banner.order,
                        is_active: banner.is_active,
                    }}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors, data, setData }) => (
                        <>
                            <div className="grid gap-2">
                                <Label>Current Image</Label>
                                {banner.image && (
                                    <img
                                        src={`/storage/${banner.image}`}
                                        alt={banner.title || 'Banner'}
                                        className="h-32 w-auto rounded-md border object-contain"
                                    />
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="image">New Image (Optional)</Label>

                                <Input type="file" id="image" name="image" accept="image/*" />

                                <InputError message={errors.image} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="title">Title (Optional)</Label>

                                <Input id="title" name="title" placeholder="Banner title" defaultValue={banner.title || ''} />

                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="order">Display Order</Label>

                                <Input type="number" id="order" name="order" placeholder="0" defaultValue={banner.order} min="0" />

                                <InputError message={errors.order} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="is_active"
                                        name="is_active"
                                        checked={data.is_active}
                                        onCheckedChange={(checked) => setData('is_active', checked as boolean)}
                                    />
                                    <Label htmlFor="is_active" className="cursor-pointer">
                                        Active
                                    </Label>
                                </div>
                                <InputError message={errors.is_active} />
                            </div>

                            <Button type="submit" disabled={processing}>
                                Update
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
