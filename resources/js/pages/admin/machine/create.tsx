import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';

import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { index, store } from '@/routes/admin/machines';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Machines',
        href: index().url,
    },
    {
        title: 'Create',
    },
];

interface Props {
    next_serial: string;
    format_example: string;
}

export default function CreateMachine({ next_serial, format_example }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        serial_number: '',
        status: '1',
        quantity: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(store().url);
    };

    const isBulk = data.quantity && parseInt(data.quantity) > 1;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Machine" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl px-4 py-6">
                <Heading title="Create Machine" description="Add new machines to inventory" />

                <form onSubmit={handleSubmit} className="max-w-2xl space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Machine Information</CardTitle>
                            <CardDescription>Enter machine details or bulk generate multiple machines</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Name *</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="Aurae Machine"
                                    required
                                />
                                {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="serial_number">Serial Number (Optional for single machine)</Label>
                                <Input
                                    id="serial_number"
                                    value={data.serial_number}
                                    onChange={(e) => setData('serial_number', e.target.value)}
                                    placeholder={next_serial}
                                    disabled={isBulk}
                                />
                                <p className="text-sm text-muted-foreground">
                                    Leave empty to auto-generate. Format: {format_example}
                                </p>
                                {errors.serial_number && <p className="text-sm text-red-600">{errors.serial_number}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="status">Status *</Label>
                                <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1">Active</SelectItem>
                                        <SelectItem value="0">Inactive</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.status && <p className="text-sm text-red-600">{errors.status}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="quantity">Quantity (Bulk Generation)</Label>
                                <Input
                                    id="quantity"
                                    type="number"
                                    min="1"
                                    max="1000"
                                    value={data.quantity}
                                    onChange={(e) => setData('quantity', e.target.value)}
                                    placeholder="1"
                                />
                                <p className="text-sm text-muted-foreground">
                                    Enter a number greater than 1 to bulk generate machines with auto-serial numbers
                                </p>
                                {errors.quantity && <p className="text-sm text-red-600">{errors.quantity}</p>}
                            </div>

                            {isBulk && (
                                <div className="rounded-lg bg-blue-50 p-4">
                                    <p className="text-sm text-blue-900">
                                        <strong>Bulk Generation:</strong> {data.quantity} machines will be created with sequential
                                        serial numbers starting from {next_serial}
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Creating...' : isBulk ? `Create ${data.quantity} Machines` : 'Create Machine'}
                        </Button>
                        <Button type="button" variant="outline" onClick={() => window.history.back()}>
                            Cancel
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
