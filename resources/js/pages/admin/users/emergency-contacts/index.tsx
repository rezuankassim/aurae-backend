import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import UsersLayout from '@/layouts/users/layout';
import { index } from '@/routes/admin/users';
import { BreadcrumbItem, EmergencyContact, User } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import dayjs from 'dayjs';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: index().url,
    },
    {
        title: 'Emergency Contacts',
        href: '',
    },
];

type EmergencyContactFormData = {
    name: string;
    phone: string;
};

const defaultFormData: EmergencyContactFormData = {
    name: '',
    phone: '',
};

export default function UserEmergencyContactsIndex({ user, emergencyContacts }: { user: User; emergencyContacts: EmergencyContact[] }) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingContact, setEditingContact] = useState<EmergencyContact | null>(null);

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm<EmergencyContactFormData>(defaultFormData);

    const openCreate = () => {
        setEditingContact(null);
        reset();
        clearErrors();
        setDialogOpen(true);
    };

    const openEdit = (contact: EmergencyContact) => {
        setEditingContact(contact);
        setData({
            name: contact.name,
            phone: contact.phone,
        });
        clearErrors();
        setDialogOpen(true);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (editingContact) {
            put(`/admin/users/${user.id}/emergency-contacts/${editingContact.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    setDialogOpen(false);
                    reset();
                },
            });
            return;
        }

        post(`/admin/users/${user.id}/emergency-contacts`, {
            preserveScroll: true,
            onSuccess: () => {
                setDialogOpen(false);
                reset();
            },
        });
    };

    const handleDelete = (contact: EmergencyContact) => {
        if (confirm('Are you sure you want to delete this emergency contact?')) {
            router.delete(`/admin/users/${user.id}/emergency-contacts/${contact.id}`, {
                preserveScroll: true,
            });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Emergency Contacts" />

            <UsersLayout id_record={user.id}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto">
                    <div className="flex items-center justify-between">
                        <HeadingSmall title="Emergency contacts" description="Manage user's emergency contacts" />

                        <Button size="sm" onClick={openCreate}>
                            <Plus className="mr-1 h-4 w-4" />
                            Add Contact
                        </Button>
                    </div>

                    <div className="overflow-hidden rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Phone</TableHead>
                                    <TableHead>Added At</TableHead>
                                    <TableHead className="w-[100px]"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {emergencyContacts.length > 0 ? (
                                    emergencyContacts.map((contact) => (
                                        <TableRow key={contact.id}>
                                            <TableCell>{contact.name}</TableCell>
                                            <TableCell>{contact.phone}</TableCell>
                                            <TableCell>{dayjs(contact.created_at).format('DD MMM YYYY, HH:mm')}</TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-1">
                                                    <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => openEdit(contact)}>
                                                        <Pencil className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-8 w-8 text-destructive hover:text-destructive"
                                                        onClick={() => handleDelete(contact)}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={4} className="h-24 text-center">
                                            No emergency contacts found.
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
                            <DialogTitle>{editingContact ? 'Edit Emergency Contact' : 'Add Emergency Contact'}</DialogTitle>
                        </DialogHeader>

                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Contact name" />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone">Phone</Label>
                                <Input id="phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} placeholder="Phone number" />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="flex justify-end gap-2 pt-2">
                                <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {editingContact ? 'Save Changes' : 'Add Contact'}
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </UsersLayout>
        </AppLayout>
    );
}
