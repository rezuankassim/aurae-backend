import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import UsersLayout from '@/layouts/users/layout';
import { index } from '@/routes/admin/users';
import { destroy, store, update } from '@/routes/admin/users/addresses';
import { Address, BreadcrumbItem, User } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: index().url,
    },
    {
        title: 'Addresses',
        href: '',
    },
];

type CountryOption = {
    id: number;
    name: string;
    code: string;
    states: { id: number; name: string; state_code: string }[];
};

type AddressFormData = {
    title: string;
    name: string;
    phone: string;
    email: string;
    line1: string;
    line2: string;
    line3: string;
    city: string;
    state: string;
    postal_code: string;
    country: string;
    delivery_instructions: string;
    is_default: boolean;
    is_billing_default: boolean;
};

const defaultFormData: AddressFormData = {
    title: '',
    name: '',
    phone: '',
    email: '',
    line1: '',
    line2: '',
    line3: '',
    city: '',
    state: '',
    postal_code: '',
    country: '',
    delivery_instructions: '',
    is_default: false,
    is_billing_default: false,
};

export default function UserAddressesIndex({
    user,
    addresses,
    countries,
}: {
    user: User;
    addresses: Address[];
    countries: CountryOption[];
}) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingAddress, setEditingAddress] = useState<Address | null>(null);

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm<AddressFormData>(defaultFormData);

    const selectedCountry = useMemo(
        () => countries.find((c) => c.code === data.country),
        [countries, data.country],
    );
    const states = selectedCountry?.states ?? [];

    const openCreate = () => {
        setEditingAddress(null);
        reset();
        clearErrors();
        setDialogOpen(true);
    };

    const openEdit = (address: Address) => {
        setEditingAddress(address);
        setData({
            title: address.title ?? '',
            name: `${address.first_name ?? ''}${address.last_name ? ` ${address.last_name}` : ''}`.trim(),
            phone: address.contact_phone ?? '',
            email: address.contact_email ?? '',
            line1: address.line_one ?? '',
            line2: address.line_two ?? '',
            line3: address.line_three ?? '',
            city: address.city ?? '',
            state: address.state ?? '',
            postal_code: address.postcode ?? '',
            country: address.country?.iso3 ?? '',
            delivery_instructions: address.delivery_instructions ?? '',
            is_default: !!address.shipping_default,
            is_billing_default: !!address.billing_default,
        });
        clearErrors();
        setDialogOpen(true);
    };

    const handleCountryChange = (code: string) => {
        setData((prev) => ({ ...prev, country: code, state: '' }));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (editingAddress) {
            put(update({ user: user.id, address: editingAddress.id }).url, {
                preserveScroll: true,
                onSuccess: () => {
                    setDialogOpen(false);
                    reset();
                },
            });
        } else {
            post(store(user.id).url, {
                preserveScroll: true,
                onSuccess: () => {
                    setDialogOpen(false);
                    reset();
                },
            });
        }
    };

    const handleDelete = (address: Address) => {
        if (confirm('Are you sure you want to delete this address?')) {
            router.delete(destroy({ user: user.id, address: address.id }).url, {
                preserveScroll: true,
            });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="User Addresses" />

            <UsersLayout id_record={user.id}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto">
                    <div className="flex items-center justify-between">
                        <HeadingSmall title="Addresses" description="Manage user's saved addresses" />

                        <Button size="sm" onClick={openCreate}>
                            <Plus className="mr-1 h-4 w-4" />
                            Add Address
                        </Button>
                    </div>

                    <div className="overflow-hidden rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Title</TableHead>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Phone</TableHead>
                                    <TableHead>Address</TableHead>
                                    <TableHead>Defaults</TableHead>
                                    <TableHead className="w-[100px]"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {addresses.length > 0 ? (
                                    addresses.map((address) => (
                                        <TableRow key={address.id}>
                                            <TableCell>{address.title ?? '-'}</TableCell>
                                            <TableCell>
                                                {[address.first_name, address.last_name].filter(Boolean).join(' ')}
                                            </TableCell>
                                            <TableCell>{address.contact_phone ?? '-'}</TableCell>
                                            <TableCell>
                                                <span className="text-sm">
                                                    {[
                                                        address.line_one,
                                                        address.line_two,
                                                        address.line_three,
                                                        address.city,
                                                        address.stateData?.name ?? address.state,
                                                        address.postcode,
                                                        address.country?.name,
                                                    ]
                                                        .filter(Boolean)
                                                        .join(', ')}
                                                </span>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-wrap items-center gap-1">
                                                    {address.shipping_default ? <Badge>Shipping</Badge> : null}
                                                    {address.billing_default ? <Badge variant="secondary">Billing</Badge> : null}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-1">
                                                    <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => openEdit(address)}>
                                                        <Pencil className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="text-destructive hover:text-destructive h-8 w-8"
                                                        onClick={() => handleDelete(address)}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={6} className="h-24 text-center">
                                            No addresses found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </div>

                <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                    <DialogContent className="max-w-lg">
                        <DialogHeader>
                            <DialogTitle>{editingAddress ? 'Edit Address' : 'Add Address'}</DialogTitle>
                        </DialogHeader>

                        <form onSubmit={handleSubmit} className="max-h-[70vh] space-y-4 overflow-y-auto pr-1">
                            <div className="grid gap-2">
                                <Label htmlFor="title">Title (optional)</Label>
                                <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} placeholder="Home, Office, etc." />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Full Name</Label>
                                    <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Full name" />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="phone">Phone</Label>
                                    <Input id="phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} placeholder="Phone number" />
                                    <InputError message={errors.phone} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email (optional)</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    placeholder="Contact email"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="line1">Address Line 1</Label>
                                <Input id="line1" value={data.line1} onChange={(e) => setData('line1', e.target.value)} placeholder="Street address" />
                                <InputError message={errors.line1} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="line2">Address Line 2 (optional)</Label>
                                <Input id="line2" value={data.line2} onChange={(e) => setData('line2', e.target.value)} placeholder="Apt, suite, unit, etc." />
                                <InputError message={errors.line2} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="line3">Address Line 3 (optional)</Label>
                                <Input id="line3" value={data.line3} onChange={(e) => setData('line3', e.target.value)} placeholder="Additional info" />
                                <InputError message={errors.line3} />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="city">City</Label>
                                    <Input id="city" value={data.city} onChange={(e) => setData('city', e.target.value)} placeholder="City" />
                                    <InputError message={errors.city} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="postal_code">Postal Code</Label>
                                    <Input
                                        id="postal_code"
                                        value={data.postal_code}
                                        onChange={(e) => setData('postal_code', e.target.value)}
                                        placeholder="Postal code"
                                    />
                                    <InputError message={errors.postal_code} />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="country">Country</Label>
                                    <Select value={data.country} onValueChange={handleCountryChange}>
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
                                    <Label htmlFor="state">State (optional)</Label>
                                    <Select value={data.state} onValueChange={(val) => setData('state', val)} disabled={!states.length}>
                                        <SelectTrigger id="state">
                                            <SelectValue placeholder={states.length ? 'Select state' : 'Select country first'} />
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
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="delivery_instructions">Delivery Instructions (optional)</Label>
                                <Textarea
                                    id="delivery_instructions"
                                    value={data.delivery_instructions}
                                    onChange={(e) => setData('delivery_instructions', e.target.value)}
                                    placeholder="Delivery instructions"
                                />
                                <InputError message={errors.delivery_instructions} />
                            </div>

                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="is_default"
                                    checked={data.is_default}
                                    onCheckedChange={(checked) => setData('is_default', checked === true)}
                                />
                                <Label htmlFor="is_default">Set as default shipping address</Label>
                            </div>

                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="is_billing_default"
                                    checked={data.is_billing_default}
                                    onCheckedChange={(checked) => setData('is_billing_default', checked === true)}
                                />
                                <Label htmlFor="is_billing_default">Set as default billing address</Label>
                            </div>

                            <div className="flex justify-end gap-2 pt-2">
                                <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {editingAddress ? 'Save Changes' : 'Add Address'}
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </UsersLayout>
        </AppLayout>
    );
}
