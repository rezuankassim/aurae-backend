import { Form, Head, Link } from '@inertiajs/react';

import HeadingSmall from '@/components/heading-small';
import { Address, type BreadcrumbItem } from '@/types';

import AddressController from '@/actions/App/Http/Controllers/Settings/AddressController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { index } from '@/routes/address';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Address settings',
        href: index().url,
    },
];

export default function Appearance({
    addresses,
    countries,
}: {
    addresses: Array<Address>;
    countries: Array<{ code: string; name: string; states: Array<{ name: string; state_code: string }> }>;
}) {
    const [states, setStates] = useState<Array<{ name: string; state_code: string }>>([]);
    const onCountryChange = (countryCode: string) => {
        const country = countries.find((c) => c.code === countryCode);
        if (country) {
            setStates(country.states);
        } else {
            setStates([]);
        }
    };

    const [newAddressModalOpen, setNewAddressModalOpen] = useState(false);
    const [isDefault, setIsDefault] = useState(false);
    const [isBillingDefault, setIsBillingDefault] = useState(false);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Address settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <HeadingSmall title="Address settings" description="Manage your account's addresses here." />

                        <Dialog open={newAddressModalOpen} onOpenChange={setNewAddressModalOpen}>
                            <DialogTrigger asChild>
                                <Button>Add new address</Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogTitle>Create new address</DialogTitle>
                                <DialogDescription>Please make sure your information is correct and valid.</DialogDescription>

                                <Form
                                    {...AddressController.store.form()}
                                    options={{
                                        preserveScroll: true,
                                    }}
                                    resetOnSuccess
                                    onSuccess={() => setNewAddressModalOpen(false)}
                                    className="space-y-6"
                                    transform={(data) => ({ ...data, is_default: isDefault, is_billing_default: isBillingDefault })}
                                >
                                    {({ resetAndClearErrors, processing, errors }) => (
                                        <>
                                            <div className="max-h-[560px] space-y-6 overflow-y-auto">
                                                <div className="grid gap-2">
                                                    <Label htmlFor="title">Title</Label>

                                                    <Input id="title" name="title" placeholder="Title" />

                                                    <InputError message={errors.title} />
                                                </div>

                                                <div className="flex items-center gap-2">
                                                    <div className="grid gap-2">
                                                        <Label htmlFor="name">Full name</Label>

                                                        <Input id="name" name="name" placeholder="Full name" />

                                                        <InputError message={errors.name} />
                                                    </div>

                                                    <div className="grid gap-2">
                                                        <Label htmlFor="phone">Phone number</Label>

                                                        <Input id="phone" name="phone" placeholder="Phone number" />

                                                        <InputError message={errors.phone} />
                                                    </div>
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="email">Email Address</Label>

                                                    <Input id="email" name="email" placeholder="Email Address" />

                                                    <InputError message={errors.email} />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="line1">Line 1</Label>

                                                    <Input id="line1" name="line1" placeholder="Line 1" />

                                                    <InputError message={errors.line1} />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="line2">Line 2</Label>

                                                    <Input id="line2" name="line2" placeholder="Line 2" />

                                                    <InputError message={errors.line2} />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="line3">Line 3 (Optional)</Label>

                                                    <Input id="line3" name="line3" placeholder="Line 3" />

                                                    <InputError message={errors.line3} />
                                                </div>

                                                <div className="flex items-center gap-2">
                                                    <div className="grid gap-2">
                                                        <Label htmlFor="postal_code">Postcode</Label>

                                                        <Input id="postal_code" name="postal_code" placeholder="Postcode" />

                                                        <InputError message={errors.postal_code} />
                                                    </div>

                                                    <div className="grid gap-2">
                                                        <Label htmlFor="city">City</Label>

                                                        <Input id="city" name="city" placeholder="City" />

                                                        <InputError message={errors.city} />
                                                    </div>
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="country">Country</Label>

                                                    <Select name="country" onValueChange={onCountryChange}>
                                                        <SelectTrigger id="country">
                                                            <SelectValue placeholder="Select country" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {countries.map((country) => (
                                                                <SelectItem key={country.code} value={country.code}>
                                                                    {country.name}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>

                                                    <InputError message={errors.country} />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="state">State</Label>

                                                    <Select name="state">
                                                        <SelectTrigger id="state">
                                                            <SelectValue placeholder="Select state" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {states.map((state) => (
                                                                <SelectItem key={state.state_code} value={state.state_code}>
                                                                    {state.name}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>

                                                    <InputError message={errors.state} />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="delivery_instruction">Delivery Instruction</Label>

                                                    <Textarea
                                                        id="delivery_instruction"
                                                        name="delivery_instruction"
                                                        placeholder="Delivery Instruction"
                                                    />

                                                    <InputError message={errors.delivery_instruction} />
                                                </div>

                                                <div className="flex items-center justify-between">
                                                    <Label htmlFor="default">Set as Shipping Default Address</Label>

                                                    <Switch checked={isDefault} onCheckedChange={(checked) => setIsDefault(checked)} />
                                                </div>

                                                <div className="flex items-center justify-between">
                                                    <Label htmlFor="default">Set as Billing Default Address</Label>

                                                    <Switch checked={isBillingDefault} onCheckedChange={(checked) => setIsBillingDefault(checked)} />
                                                </div>
                                            </div>

                                            <DialogFooter className="gap-2">
                                                <DialogClose asChild>
                                                    <Button variant="secondary" onClick={() => resetAndClearErrors()}>
                                                        Cancel
                                                    </Button>
                                                </DialogClose>

                                                <Button disabled={processing} asChild>
                                                    <button type="submit">Submit</button>
                                                </Button>
                                            </DialogFooter>
                                        </>
                                    )}
                                </Form>
                            </DialogContent>
                        </Dialog>
                    </div>

                    <div className="grid gap-4">
                        {addresses.length === 0 && <p className="text-sm text-muted-foreground">No addresses found.</p>}

                        {addresses.map((address) => (
                            <Card key={address.id}>
                                <CardHeader>
                                    <CardTitle className="flex items-center justify-between">
                                        <div className="grid grid-flow-col items-center gap-2">
                                            <div className="flex">
                                                <span>{address.title}</span>
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-2">
                                            <Button variant="secondary" asChild>
                                                <Link href={AddressController.edit(address.id).url}>Edit</Link>
                                            </Button>

                                            <Form
                                                {...AddressController.destroy.form(address.id)}
                                                options={{
                                                    preserveScroll: true,
                                                }}
                                                onSuccess={() => {}}
                                            >
                                                {({ processing }) => (
                                                    <Button
                                                        type="submit"
                                                        variant="destructive"
                                                        disabled={processing}
                                                        className="hover:cursor-pointer"
                                                    >
                                                        Delete
                                                    </Button>
                                                )}
                                            </Form>
                                        </div>
                                    </CardTitle>
                                    <CardContent className="px-0 text-sm text-muted-foreground">
                                        <p>
                                            {address.first_name} {address.last_name}
                                        </p>
                                        <p>{address.contact_phone}</p>
                                        <p>{address.line_one}</p>
                                        {address.line_two && <p>{address.line_two}</p>}
                                        {address.line_three && <p>{address.line_three}</p>}
                                        <p>
                                            {address.postcode}, {address.city}, {address.stateData.name}, {address.country.name}
                                        </p>

                                        <div className="mt-2 flex items-center gap-2">
                                            {address.shipping_default ? <Badge variant="default">Shipping Default</Badge> : null}
                                            {address.billing_default ? <Badge variant="secondary">Billing Default</Badge> : null}
                                        </div>
                                    </CardContent>
                                </CardHeader>
                            </Card>
                        ))}
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
