import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/react';

import SubscriptionController from '@/actions/App/Http/Controllers/Admin/SubscriptionController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { edit, index } from '@/routes/admin/subscription';
import { useState } from 'react';
import { Subscription } from './index';

const breadcrumbs = (subscription: Subscription): BreadcrumbItem[] => [
    {
        title: 'Subscriptions',
        href: index().url,
    },
    {
        title: 'Edit subscription',
        href: edit(subscription.id).url,
    },
];

export default function EditSubscription({ subscription }: { subscription: Subscription }) {
    const [isActive, setIsActive] = useState(subscription.is_active);

    return (
        <AppLayout breadcrumbs={breadcrumbs(subscription)}>
            <Head title="Edit subscription" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="Edit subscription" description="Update subscription plan details" />

                <Form
                    {...SubscriptionController.update.form(subscription.id)}
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
                                <Label htmlFor="icon">Icon</Label>

                                {subscription.icon_url && (
                                    <div className="mb-2">
                                        <img src={subscription.icon_url} alt="Current icon" className="h-20 w-20 rounded object-contain" />
                                    </div>
                                )}

                                <Input type="file" id="icon" name="icon" placeholder="Icon" accept="image/*" />

                                <InputError message={errors.icon} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="title">Title</Label>

                                <Input id="title" name="title" placeholder="e.g. Basic Plan, Premium Plan" defaultValue={subscription.title} />

                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="pricing_title">Pricing Title</Label>

                                <Input
                                    id="pricing_title"
                                    name="pricing_title"
                                    placeholder="e.g. RM 59.90 / month"
                                    defaultValue={subscription.pricing_title}
                                />

                                <InputError message={errors.pricing_title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>

                                <Textarea
                                    id="description"
                                    name="description"
                                    placeholder="Plan description"
                                    rows={4}
                                    defaultValue={subscription.description || ''}
                                />

                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="price">Price (RM)</Label>

                                <Input
                                    type="number"
                                    id="price"
                                    name="price"
                                    placeholder="59.90"
                                    min="0"
                                    step="0.01"
                                    defaultValue={subscription.price}
                                />

                                <InputError message={errors.price} />
                            </div>

                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="is_active"
                                    name="is_active"
                                    checked={isActive}
                                    onCheckedChange={(checked) => setIsActive(checked as boolean)}
                                />
                                <Label htmlFor="is_active" className="cursor-pointer">
                                    Is Active
                                </Label>
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
