import AppLayout from '@/layouts/app-layout';
import { Music, type BreadcrumbItem } from '@/types';
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
import { edit, index, update } from '@/routes/admin/music';
import { useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Music',
        href: index().url,
    },
    {
        title: 'Edit Music',
        href: '', // Placeholder, will be set in component or handled by navigation logic if needed but mostly breadcrumbs are static
    },
];

export default function MusicEdit({ music }: { music: Music }) {
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const { uploadToS3, progress: s3Progress, isUploading: isS3Uploading } = useS3Upload();

    breadcrumbs[1].href = edit(music.id).url;

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

            // Check if we have files to upload to S3
            let musicResult = null;
            let thumbnailResult = null;
            let useS3 = false;

            if (musicFile) {
                musicResult = await uploadToS3(musicFile, 'music');
                if (musicResult !== null) {
                    useS3 = true;
                }
            }

            if (thumbnailFile) {
                thumbnailResult = await uploadToS3(thumbnailFile, 'music/thumbnails');
                if (thumbnailResult !== null) {
                    useS3 = true;
                }
            }

            if (useS3 || (!musicFile && !thumbnailFile)) {
                // S3 mode or no files to upload
                router.put(
                    update(music.id).url,
                    {
                        title,
                        music_s3_key: musicResult?.key || null,
                        thumbnail_s3_key: thumbnailResult?.key || null,
                        is_active: isActive ? '1' : '0',
                    },
                    {
                        preserveScroll: true,
                        onError: (errors) => setErrors(errors),
                        onFinish: () => setIsSubmitting(false),
                    },
                );
            } else {
                // Development mode - use traditional form upload
                const formData = new FormData();
                formData.append('_method', 'PUT');
                formData.append('title', title);
                if (musicFile) {
                    formData.append('music', musicFile);
                }
                if (thumbnailFile) {
                    formData.append('thumbnail', thumbnailFile);
                }
                formData.append('is_active', isActive ? '1' : '0');

                router.post(update(music.id).url, formData, {
                    preserveScroll: true,
                    onError: (errors) => setErrors(errors),
                    onFinish: () => setIsSubmitting(false),
                });
            }
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
            <Head title="Edit Music" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="Edit Music" description="Edit music details" />

                <UploadProgress progress={uploadProgress} isUploading={isS3Uploading} />

                <form ref={formRef} onSubmit={handleSubmit} className="space-y-6">
                    <Card className="mt-0">
                        <CardContent className="space-y-6">
                            <div className="grid gap-2">
                                <Label htmlFor="title">Title</Label>
                                <Input ref={titleRef} id="title" name="title" placeholder="Title" defaultValue={music.title} />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="thumbnail">Thumbnail Image (Optional)</Label>
                                {music.thumbnail_url && (
                                    <div className="mb-2">
                                        <img src={music.thumbnail_url} alt={music.title} className="h-32 w-32 rounded-md object-cover" />
                                    </div>
                                )}
                                <Input ref={thumbnailRef} type="file" id="thumbnail" name="thumbnail" accept="image/*" />
                                <InputError message={errors.thumbnail} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="music">Music File (Optional - Leave empty to keep current)</Label>
                                {music.url && (
                                    <div className="mb-2">
                                        <audio controls src={music.url} className="w-full" />
                                    </div>
                                )}
                                <Input ref={musicRef} type="file" id="music" name="music" accept="audio/*" />
                                <InputError message={errors.music} />
                            </div>

                            <div className="flex items-center space-x-2">
                                <Switch ref={isActiveRef} id="is_active" defaultChecked={music.is_active} />
                                <Label htmlFor="is_active">Active</Label>
                            </div>
                        </CardContent>
                    </Card>

                    <Button type="submit" disabled={isUploading}>
                        {isUploading ? 'Uploading...' : 'Save Changes'}
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
