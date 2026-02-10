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
import { create, index } from '@/routes/admin/subscription';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Subscriptions',
        href: index().url,
    },
    {
        title: 'Create subscription',
        href: create().url,
    },
];

export default function CreateSubscription() {
    const [isActive, setIsActive] = useState(true);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create subscription" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="Create subscription" description="Create a new subscription plan" />

                <Form
                    {...SubscriptionController.store.form()}
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
                                <Label htmlFor="icon">Icon</Label>

                                <Input type="file" id="icon" name="icon" placeholder="Icon" accept="image/*" />

                                <InputError message={errors.icon} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="title">Title</Label>

                                <Input id="title" name="title" placeholder="e.g. Basic Plan, Premium Plan" />

                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="pricing_title">Pricing Title</Label>

                                <Input id="pricing_title" name="pricing_title" placeholder="e.g. RM 59.90 / month" />

                                <InputError message={errors.pricing_title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>

                                <Textarea id="description" name="description" placeholder="Plan description" rows={4} />

                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="price">Price (RM)</Label>

                                <Input type="number" id="price" name="price" placeholder="59.90" min="0" step="0.01" />

                                <InputError message={errors.price} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="senangpay_recurring_id">SenangPay Recurring ID</Label>

                                <Input id="senangpay_recurring_id" name="senangpay_recurring_id" placeholder="e.g. 172500523839" />

                                <p className="text-sm text-muted-foreground">
                                    Get this ID from SenangPay dashboard after creating a recurring product
                                </p>

                                <InputError message={errors.senangpay_recurring_id} />
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
                                Submit
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
