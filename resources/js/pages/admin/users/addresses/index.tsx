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
import AppLayout from '@/layouts/app-layout';
import UsersLayout from '@/layouts/users/layout';
import { index } from '@/routes/admin/users';
import { destroy, store, update } from '@/routes/admin/users/addresses';
import { BreadcrumbItem, User, UserAddress } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

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

const TYPE_LABELS: Record<number, string> = {
    0: 'Home',
    1: 'Work',
    2: 'Other',
};

type AddressFormData = {
    type: string;
    name: string;
    phone: string;
    line1: string;
    line2: string;
    line3: string;
    city: string;
    state: string;
    postal_code: string;
    country: string;
    is_default: boolean;
};

const defaultFormData: AddressFormData = {
    type: '0',
    name: '',
    phone: '',
    line1: '',
    line2: '',
    line3: '',
    city: '',
    state: '',
    postal_code: '',
    country: '',
    is_default: false,
};

export default function UserAddressesIndex({ user, addresses }: { user: User; addresses: UserAddress[] }) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingAddress, setEditingAddress] = useState<UserAddress | null>(null);

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm<AddressFormData>(defaultFormData);

    const openCreate = () => {
        setEditingAddress(null);
        reset();
        clearErrors();
        setDialogOpen(true);
    };

    const openEdit = (address: UserAddress) => {
        setEditingAddress(address);
        setData({
            type: String(address.type),
            name: address.name,
            phone: address.phone,
            line1: address.line1,
            line2: address.line2 ?? '',
            line3: address.line3 ?? '',
            city: address.city,
            state: address.state ?? '',
            postal_code: address.postal_code,
            country: address.country,
            is_default: address.is_default,
        });
        clearErrors();
        setDialogOpen(true);
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

    const handleDelete = (address: UserAddress) => {
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
                                    <TableHead>Type</TableHead>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Phone</TableHead>
                                    <TableHead>Address</TableHead>
                                    <TableHead>Default</TableHead>
                                    <TableHead className="w-[100px]"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {addresses.length > 0 ? (
                                    addresses.map((address) => (
                                        <TableRow key={address.id}>
                                            <TableCell>
                                                <Badge variant="outline">{TYPE_LABELS[address.type]}</Badge>
                                            </TableCell>
                                            <TableCell>{address.name}</TableCell>
                                            <TableCell>{address.phone}</TableCell>
                                            <TableCell>
                                                <span className="text-sm">
                                                    {[address.line1, address.line2, address.line3, address.city, address.state, address.postal_code, address.country]
                                                        .filter(Boolean)
                                                        .join(', ')}
                                                </span>
                                            </TableCell>
                                            <TableCell>{address.is_default ? <Badge>Default</Badge> : null}</TableCell>
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

                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="type">Type</Label>
                                    <Select name="type" value={data.type} onValueChange={(val) => setData('type', val)}>
                                        <SelectTrigger id="type">
                                            <SelectValue placeholder="Select type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="0">Home</SelectItem>
                                            <SelectItem value="1">Work</SelectItem>
                                            <SelectItem value="2">Other</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.type} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Full name" />
                                    <InputError message={errors.name} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone">Phone</Label>
                                <Input id="phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} placeholder="Phone number" />
                                <InputError message={errors.phone} />
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
                                    <Label htmlFor="state">State (optional)</Label>
                                    <Input id="state" value={data.state} onChange={(e) => setData('state', e.target.value)} placeholder="State" />
                                    <InputError message={errors.state} />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
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

                                <div className="grid gap-2">
                                    <Label htmlFor="country">Country</Label>
                                    <Input id="country" value={data.country} onChange={(e) => setData('country', e.target.value)} placeholder="Country" />
                                    <InputError message={errors.country} />
                                </div>
                            </div>

                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="is_default"
                                    checked={data.is_default}
                                    onCheckedChange={(checked) => setData('is_default', checked === true)}
                                />
                                <Label htmlFor="is_default">Set as default address</Label>
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
