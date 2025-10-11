import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { ButtonGroup, ButtonGroupText } from '@/components/ui/button-group';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/admin/users';
import { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { ClipboardCopy, Eye, Shuffle } from 'lucide-react';
import { useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: index().url,
    },
    {
        title: 'Create users',
        href: '',
    },
];

export default function UsersCreate() {
    const passwordInputRef = useRef<HTMLInputElement>(null);
    const [password, setPassword] = useState('');

    const copyToClipboard = async () => {
        try {
            const inputElement = passwordInputRef.current;
            const htmlContent = inputElement?.value || '';

            if (htmlContent) {
                await navigator.clipboard.writeText(htmlContent);
            } else {
                console.error('No content to copy');
            }
        } catch (err) {
            console.error('Failed to copy text:', err);
        }
    };

    const generatePassword = () => {
        // Simple password generator (for demonstration purposes only)
        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        let password = '';
        for (let i = 0; i < 12; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return password;
    };

    const togglePasswordVisibility = () => {
        const input = passwordInputRef.current;
        if (input) {
            if (input.type === 'password') {
                input.type = 'text';
            } else {
                input.type = 'password';
            }
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create users" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Create users" description="Create new users for your system" />
                </div>

                <Form
                    {...UserController.store.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <Card className="mt-0">
                                <CardContent className="space-y-6">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Name</Label>

                                        <Input id="name" name="name" placeholder="Name" />

                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="email">Email</Label>

                                        <Input id="email" name="email" placeholder="Email" />

                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password">Password</Label>

                                        <ButtonGroup className="w-full">
                                            <ButtonGroupText asChild>
                                                <Button type="button" variant="ghost" size="icon" onClick={() => setPassword(generatePassword())}>
                                                    <Shuffle />
                                                </Button>
                                            </ButtonGroupText>

                                            <Input
                                                ref={passwordInputRef}
                                                id="password"
                                                name="password"
                                                type="password"
                                                placeholder="Password"
                                                className="rounded-l-none"
                                                readOnly
                                                onChange={(e) => setPassword(e.target.value)}
                                                value={password}
                                            />

                                            <ButtonGroupText asChild>
                                                <Button type="button" variant="ghost" size="icon" onClick={togglePasswordVisibility}>
                                                    <Eye />
                                                </Button>
                                            </ButtonGroupText>

                                            <ButtonGroupText asChild>
                                                <Button type="button" variant="ghost" size="icon" onClick={copyToClipboard}>
                                                    <ClipboardCopy />
                                                </Button>
                                            </ButtonGroupText>
                                        </ButtonGroup>

                                        <InputError message={errors.password} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="type">Type</Label>

                                        <Select name="type">
                                            <SelectTrigger id="type">
                                                <SelectValue placeholder="Select type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="1">Admin</SelectItem>
                                                <SelectItem value="0">Customer</SelectItem>
                                            </SelectContent>
                                        </Select>

                                        <InputError message={errors.type} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="phone">Phone</Label>

                                        <Input id="phone" name="phone" placeholder="Phone" />

                                        <InputError message={errors.phone} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="status">Status</Label>

                                        <Select name="status">
                                            <SelectTrigger id="status">
                                                <SelectValue placeholder="Select status" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="1">Active</SelectItem>
                                                <SelectItem value="0">Inactive</SelectItem>
                                            </SelectContent>
                                        </Select>

                                        <InputError message={errors.status} />
                                    </div>
                                </CardContent>
                            </Card>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Submit
                                </Button>

                                <Button type="button" variant="outline" asChild>
                                    <Link href={index().url}>Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
