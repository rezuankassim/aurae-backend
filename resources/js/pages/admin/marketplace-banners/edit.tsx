import AppLayout from '@/layouts/app-layout';
import { MarketplaceBanner, type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/react';

import MarketplaceBannerController from '@/actions/App/Http/Controllers/Admin/MarketplaceBannerController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit, index } from '@/routes/admin/marketplace-banners';
import { useState } from 'react';

const breadcrumbs = (banner: MarketplaceBanner): BreadcrumbItem[] => [
    {
        title: 'Marketplace Banners',
        href: index().url,
    },
    {
        title: 'Edit Banner',
        href: edit(banner.id).url,
    },
];

export default function EditMarketplaceBanner({ banner }: { banner: MarketplaceBanner }) {
    const [isActive, setIsActive] = useState(banner.is_active);

    return (
        <AppLayout breadcrumbs={breadcrumbs(banner)}>
            <Head title="Edit Marketplace Banner" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="Edit Marketplace Banner" description="Update banner image and settings" />

                <Form
                    {...MarketplaceBannerController.update.form(banner.id)}
                    options={{
                        preserveScroll: true,
                    }}
                    transform={(data) => ({
                        ...data,
                        is_active: isActive,
                    })}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
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
                                Update
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
