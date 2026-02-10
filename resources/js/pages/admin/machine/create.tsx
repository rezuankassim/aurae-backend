import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';

import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { create, index, store } from '@/routes/admin/machines';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Machines',
        href: index().url,
    },
    {
        title: 'Create',
        href: create().url,
    },
];

interface Props {
    next_serial: string;
}

export default function CreateMachine({ next_serial }: Props) {
    const { data, setData, post, processing, errors } = useForm<{
        name: string;
        serial_number: string;
        model: string;
        year: string;
        product_code: string;
        variation_code: string;
        status: string;
        quantity: string;
        start_product_code: string;
        thumbnail: File | null;
        detail_image: File | null;
    }>({
        name: '',
        serial_number: '',
        model: 'A101',
        year: new Date().getFullYear().toString(),
        product_code: '',
        variation_code: '1',
        status: '1',
        quantity: '',
        start_product_code: '1',
        thumbnail: null,
        detail_image: null,
    });

    const [thumbnailPreview, setThumbnailPreview] = useState<string | null>(null);
    const [detailImagePreview, setDetailImagePreview] = useState<string | null>(null);

    const handleThumbnailChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0] || null;
        setData('thumbnail', file);
        if (file) {
            setThumbnailPreview(URL.createObjectURL(file));
        } else {
            setThumbnailPreview(null);
        }
    };

    const handleDetailImageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0] || null;
        setData('detail_image', file);
        if (file) {
            setDetailImagePreview(URL.createObjectURL(file));
        } else {
            setDetailImagePreview(null);
        }
    };

    // Build serial number from components
    useEffect(() => {
        if (data.model && data.year && data.product_code && data.variation_code) {
            const serial = `${data.model}${data.year}${data.product_code.padStart(4, '0')} ${data.variation_code}`;
            setData('serial_number', serial);
        } else if (!data.product_code) {
            setData('serial_number', '');
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [data.model, data.year, data.product_code, data.variation_code]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(store().url, {
            forceFormData: true,
        });
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
                        </CardContent>
                    </Card>

                    {!isBulk && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Serial Number</CardTitle>
                                <CardDescription>Format: [Model][Year][Product Code] [Variation] — Example: A10120260001 1</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-4 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="model">Model (4 chars)</Label>
                                        <Input
                                            id="model"
                                            value={data.model}
                                            onChange={(e) => setData('model', e.target.value.toUpperCase().slice(0, 4))}
                                            placeholder="A101"
                                            maxLength={4}
                                        />
                                        <p className="text-xs text-muted-foreground">e.g., A101</p>
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="year">Year (4 digits)</Label>
                                        <Input
                                            id="year"
                                            value={data.year}
                                            onChange={(e) => setData('year', e.target.value.replace(/\D/g, '').slice(0, 4))}
                                            placeholder="2026"
                                            maxLength={4}
                                        />
                                        <p className="text-xs text-muted-foreground">e.g., 2026</p>
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="product_code">Product Code</Label>
                                        <Input
                                            id="product_code"
                                            value={data.product_code}
                                            onChange={(e) => setData('product_code', e.target.value.replace(/\D/g, '').slice(0, 4))}
                                            placeholder="0001"
                                            maxLength={4}
                                        />
                                        <p className="text-xs text-muted-foreground">4 digits, e.g., 0001</p>
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="variation_code">Variation</Label>
                                        <Input
                                            id="variation_code"
                                            value={data.variation_code}
                                            onChange={(e) => setData('variation_code', e.target.value.replace(/\D/g, '').slice(0, 1))}
                                            placeholder="1"
                                            maxLength={1}
                                        />
                                        <p className="text-xs text-muted-foreground">1 digit</p>
                                    </div>
                                </div>

                                <div className="rounded-lg bg-muted p-4">
                                    <Label className="text-muted-foreground">Generated Serial Number</Label>
                                    <p className="mt-1 font-mono text-lg font-semibold">{data.serial_number || 'Enter product code to generate'}</p>
                                </div>

                                {errors.serial_number && <p className="text-sm text-red-600">{errors.serial_number}</p>}

                                <p className="text-sm text-muted-foreground">
                                    Leave product code empty to auto-generate. Next auto serial: <span className="font-mono">{next_serial}</span>
                                </p>
                            </CardContent>
                        </Card>
                    )}

                    {isBulk && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Bulk Serial Number Format</CardTitle>
                                <CardDescription>
                                    Format: [Model][Year][Product Code] [Variation] — Product code will increment for each machine
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-4 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="bulk_model">Model (4 chars)</Label>
                                        <Input
                                            id="bulk_model"
                                            value={data.model}
                                            onChange={(e) => setData('model', e.target.value.toUpperCase().slice(0, 4))}
                                            placeholder="A101"
                                            maxLength={4}
                                        />
                                        <p className="text-xs text-muted-foreground">e.g., A101</p>
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="bulk_year">Year (4 digits)</Label>
                                        <Input
                                            id="bulk_year"
                                            value={data.year}
                                            onChange={(e) => setData('year', e.target.value.replace(/\D/g, '').slice(0, 4))}
                                            placeholder="2026"
                                            maxLength={4}
                                        />
                                        <p className="text-xs text-muted-foreground">e.g., 2026</p>
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="start_product_code">Starting Product Code</Label>
                                        <Input
                                            id="start_product_code"
                                            value={data.start_product_code}
                                            onChange={(e) => setData('start_product_code', e.target.value.replace(/\D/g, '').slice(0, 4))}
                                            placeholder="1"
                                            maxLength={4}
                                        />
                                        <p className="text-xs text-muted-foreground">Will increment</p>
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="bulk_variation">Variation</Label>
                                        <Input
                                            id="bulk_variation"
                                            value={data.variation_code}
                                            onChange={(e) => setData('variation_code', e.target.value.replace(/\D/g, '').slice(0, 1))}
                                            placeholder="1"
                                            maxLength={1}
                                        />
                                        <p className="text-xs text-muted-foreground">1 digit</p>
                                    </div>
                                </div>

                                <div className="rounded-lg border border-blue-200 bg-blue-50 p-4">
                                    <Label className="text-blue-900">Preview (first 3 serial numbers)</Label>
                                    <div className="mt-2 space-y-1 font-mono text-sm text-blue-800">
                                        {Array.from({ length: Math.min(3, parseInt(data.quantity) || 0) }, (_, i) => {
                                            const startCode = parseInt(data.start_product_code) || 1;
                                            const productCode = String(startCode + i).padStart(4, '0');
                                            return (
                                                <div key={i}>
                                                    {data.model}
                                                    {data.year}
                                                    {productCode} {data.variation_code}
                                                </div>
                                            );
                                        })}
                                        {parseInt(data.quantity) > 3 && <div>...</div>}
                                    </div>
                                </div>

                                {errors.model && <p className="text-sm text-red-600">{errors.model}</p>}
                                {errors.year && <p className="text-sm text-red-600">{errors.year}</p>}
                                {errors.start_product_code && <p className="text-sm text-red-600">{errors.start_product_code}</p>}
                                {errors.variation_code && <p className="text-sm text-red-600">{errors.variation_code}</p>}
                            </CardContent>
                        </Card>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle>Machine Images</CardTitle>
                            <CardDescription>Upload thumbnail and detail images (stored in S3)</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-6">
                                <div className="space-y-2">
                                    <Label htmlFor="thumbnail">Thumbnail Image</Label>
                                    <Input id="thumbnail" type="file" accept="image/*" onChange={handleThumbnailChange} />
                                    {thumbnailPreview && (
                                        <div className="mt-2">
                                            <img src={thumbnailPreview} alt="Thumbnail preview" className="h-32 w-32 rounded-lg object-cover" />
                                        </div>
                                    )}
                                    {errors.thumbnail && <p className="text-sm text-red-600">{errors.thumbnail}</p>}
                                    <p className="text-xs text-muted-foreground">Max 5MB. Recommended: 200x200px</p>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="detail_image">Detail Image</Label>
                                    <Input id="detail_image" type="file" accept="image/*" onChange={handleDetailImageChange} />
                                    {detailImagePreview && (
                                        <div className="mt-2">
                                            <img
                                                src={detailImagePreview}
                                                alt="Detail image preview"
                                                className="h-32 w-auto rounded-lg object-cover"
                                            />
                                        </div>
                                    )}
                                    {errors.detail_image && <p className="text-sm text-red-600">{errors.detail_image}</p>}
                                    <p className="text-xs text-muted-foreground">Max 5MB. Recommended: 800x600px</p>
                                </div>
                            </div>
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
