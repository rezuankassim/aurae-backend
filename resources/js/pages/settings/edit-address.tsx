import { Form, Head } from '@inertiajs/react';

import HeadingSmall from '@/components/heading-small';
import { Address, type BreadcrumbItem } from '@/types';

import AddressController from '@/actions/App/Http/Controllers/Settings/AddressController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { index } from '@/routes/address';
import { useCallback, useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Address settings',
        href: index().url,
    },
    {
        title: 'Edit address',
        href: '',
    },
];

export default function EditAddress({
    address,
    countries,
}: {
    address: Address;
    countries: Array<{ code: string; name: string; states: Array<{ name: string; state_code: string }> }>;
}) {
    const [states, setStates] = useState<Array<{ name: string; state_code: string }>>([]);
    const onCountryChange = useCallback(
        (countryCode: string) => {
            const country = countries.find((c) => c.code === countryCode);
            if (country) {
                setStates(country.states);
            } else {
                setStates([]);
            }
        },
        [countries],
    );

    const [isDefault, setIsDefault] = useState(false);
    const [type, setType] = useState<'0' | '1' | '2'>('0');

    useEffect(() => {
        onCountryChange(address.country);
        setIsDefault(address.is_default);
        setType(address.type.toString() as '0' | '1' | '2');
    }, [address.country, onCountryChange, address.is_default, address.type]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Address settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Address settings" description="Update your current address" />

                    <Form
                        {...AddressController.update.form(address.id)}
                        options={{
                            preserveScroll: true,
                        }}
                        resetOnSuccess
                        className="space-y-6"
                        transform={(data) => ({ ...data, type: parseInt(type), is_default: isDefault })}
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid grid-flow-col gap-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Full name</Label>

                                        <Input id="name" name="name" placeholder="Full name" defaultValue={address.name} />

                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="phone">Phone number</Label>

                                        <Input id="phone" name="phone" placeholder="Phone number" defaultValue={address.phone} />

                                        <InputError message={errors.phone} />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="line1">Line 1</Label>

                                    <Input id="line1" name="line1" placeholder="Line 1" defaultValue={address.line1} />

                                    <InputError message={errors.line1} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="line2">Line 2</Label>

                                    <Input id="line2" name="line2" placeholder="Line 2" defaultValue={address.line2 || ''} />

                                    <InputError message={errors.line2} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="line3">Line 3 (Optional)</Label>

                                    <Input id="line3" name="line3" placeholder="Line 3" defaultValue={address.line3 || ''} />

                                    <InputError message={errors.line3} />
                                </div>

                                <div className="grid grid-flow-col gap-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="postal_code">Postcode</Label>

                                        <Input id="postal_code" name="postal_code" placeholder="Postcode" defaultValue={address.postal_code} />

                                        <InputError message={errors.postal_code} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="city">City</Label>

                                        <Input id="city" name="city" placeholder="City" defaultValue={address.city} />

                                        <InputError message={errors.city} />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="country">Country</Label>

                                    <Select name="country" onValueChange={onCountryChange} defaultValue={address.country}>
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

                                    <Select name="state" defaultValue={address.state || ''}>
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

                                <div className="flex items-center justify-between">
                                    <Label htmlFor="default">Set as Default Address</Label>

                                    <Switch checked={isDefault} onCheckedChange={(checked) => setIsDefault(checked)} />
                                </div>

                                <div className="flex items-center justify-between">
                                    <Label htmlFor="type">Label As</Label>

                                    <ToggleGroup value={type} onValueChange={(type) => setType(type as '0' | '1' | '2')} type="single">
                                        <ToggleGroupItem value="0" aria-label="Home">
                                            Home
                                        </ToggleGroupItem>
                                        <ToggleGroupItem value="1" aria-label="Work">
                                            Work
                                        </ToggleGroupItem>
                                        <ToggleGroupItem value="2" aria-label="Other">
                                            Other
                                        </ToggleGroupItem>
                                    </ToggleGroup>
                                </div>

                                <Button disabled={processing} asChild>
                                    <button type="submit">Submit</button>
                                </Button>
                            </>
                        )}
                    </Form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
