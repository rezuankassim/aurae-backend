import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';

import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { edit, index, update } from '@/routes/admin/machines';
import { useState } from 'react';

interface Machine {
    id: string;
    name: string;
    serial_number: string;
    status: number;
    thumbnail: string | null;
    detail_image: string | null;
    thumbnail_url: string | null;
    detail_image_url: string | null;
}

interface Props {
    machine: Machine;
}

export default function EditMachine({ machine }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Machines',
            href: index().url,
        },
        {
            title: 'Edit',
            href: edit(machine.id).url,
        },
    ];

    const { data, setData, post, processing, errors } = useForm<{
        name: string;
        serial_number: string;
        status: string;
        thumbnail: File | null;
        detail_image: File | null;
        _method: string;
    }>({
        name: machine.name,
        serial_number: machine.serial_number,
        status: String(machine.status),
        thumbnail: null,
        detail_image: null,
        _method: 'PUT',
    });

    const [thumbnailPreview, setThumbnailPreview] = useState<string | null>(machine.thumbnail_url);
    const [detailImagePreview, setDetailImagePreview] = useState<string | null>(machine.detail_image_url);

    const handleThumbnailChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0] || null;
        setData('thumbnail', file);
        if (file) {
            setThumbnailPreview(URL.createObjectURL(file));
        }
    };

    const handleDetailImageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0] || null;
        setData('detail_image', file);
        if (file) {
            setDetailImagePreview(URL.createObjectURL(file));
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(update(machine.id).url, {
            forceFormData: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Machine" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl px-4 py-6">
                <Heading title="Edit Machine" description={`Edit machine: ${machine.serial_number}`} />

                <form onSubmit={handleSubmit} className="max-w-2xl space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Machine Information</CardTitle>
                            <CardDescription>Update machine details</CardDescription>
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
                                <Label htmlFor="serial_number">Serial Number *</Label>
                                <Input
                                    id="serial_number"
                                    value={data.serial_number}
                                    onChange={(e) => setData('serial_number', e.target.value)}
                                    placeholder="A10120260001 1"
                                    required
                                />
                                {errors.serial_number && <p className="text-sm text-red-600">{errors.serial_number}</p>}
                                <p className="text-xs text-muted-foreground">Format: [Model][Year][Product Code] [Variation]</p>
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
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Machine Images</CardTitle>
                            <CardDescription>Upload or update thumbnail and detail images (stored in S3)</CardDescription>
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
                            {processing ? 'Saving...' : 'Save Changes'}
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
