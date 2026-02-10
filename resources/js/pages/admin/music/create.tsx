import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';

import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import UploadProgress from '@/components/upload-progress';
import { useS3Upload } from '@/hooks/use-s3-upload';
import { create, index, store } from '@/routes/admin/music';
import { useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Music',
        href: index().url,
    },
    {
        title: 'Add Music',
        href: create().url,
    },
];

export default function MusicCreate() {
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const { uploadToS3, progress: s3Progress, isUploading: isS3Uploading } = useS3Upload();

    const formRef = useRef<HTMLFormElement>(null);
    const titleRef = useRef<HTMLInputElement>(null);
    const thumbnailRef = useRef<HTMLInputElement>(null);
    const musicRef = useRef<HTMLInputElement>(null);
    const isActiveRef = useRef<HTMLButtonElement>(null);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setErrors({});
        setIsSubmitting(true);

        try {
            const title = titleRef.current?.value || '';
            const thumbnailFile = thumbnailRef.current?.files?.[0];
            const musicFile = musicRef.current?.files?.[0];
            const isActive = isActiveRef.current?.getAttribute('data-state') === 'checked';

            if (!title) {
                setErrors({ title: 'Title is required' });
                setIsSubmitting(false);
                return;
            }

            if (!musicFile) {
                setErrors({ music: 'Music file is required' });
                setIsSubmitting(false);
                return;
            }

            // Try S3 upload first (will return null in development)
            const musicResult = await uploadToS3(musicFile, 'music');

            if (musicResult === null) {
                // Development mode - use traditional form upload
                const formData = new FormData();
                formData.append('title', title);
                formData.append('music', musicFile);
                if (thumbnailFile) {
                    formData.append('thumbnail', thumbnailFile);
                }
                formData.append('is_active', isActive ? '1' : '0');

                router.post(store().url, formData, {
                    preserveScroll: true,
                    onError: (errors) => setErrors(errors),
                    onFinish: () => setIsSubmitting(false),
                });
                return;
            }

            // S3 upload succeeded - upload thumbnail if present
            let thumbnailResult = null;
            if (thumbnailFile) {
                thumbnailResult = await uploadToS3(thumbnailFile, 'music/thumbnails');
            }

            // Send S3 keys to server
            router.post(
                store().url,
                {
                    title,
                    music_s3_key: musicResult.key,
                    thumbnail_s3_key: thumbnailResult?.key || null,
                    is_active: isActive ? '1' : '0',
                },
                {
                    preserveScroll: true,
                    onError: (errors) => setErrors(errors),
                    onFinish: () => setIsSubmitting(false),
                },
            );
        } catch (error) {
            console.error('Upload failed:', error);
            setErrors({ music: 'Upload failed. Please try again.' });
            setIsSubmitting(false);
        }
    };

    const isUploading = isS3Uploading || isSubmitting;
    const uploadProgress = s3Progress;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add Music" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="Add Music" description="Upload new music file" />

                <UploadProgress progress={uploadProgress} isUploading={isS3Uploading} />

                <form ref={formRef} onSubmit={handleSubmit} className="space-y-6">
                    <Card className="mt-0">
                        <CardContent className="space-y-6">
                            <div className="grid gap-2">
                                <Label htmlFor="title">Title</Label>
                                <Input ref={titleRef} id="title" name="title" placeholder="Title" />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="thumbnail">Thumbnail Image (Optional)</Label>
                                <Input ref={thumbnailRef} type="file" id="thumbnail" name="thumbnail" accept="image/*" />
                                <InputError message={errors.thumbnail} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="music">Music File</Label>
                                <Input ref={musicRef} type="file" id="music" name="music" accept="audio/*" />
                                <InputError message={errors.music} />
                            </div>

                            <div className="flex items-center space-x-2">
                                <Switch ref={isActiveRef} id="is_active" />
                                <Label htmlFor="is_active">Active</Label>
                            </div>
                        </CardContent>
                    </Card>

                    <Button type="submit" disabled={isUploading}>
                        {isUploading ? 'Uploading...' : 'Submit'}
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
