import { presignedUrl } from '@/routes/admin/s3-upload';
import { useState } from 'react';

interface S3UploadResult {
    key: string;
    url: string;
}

interface UseS3UploadReturn {
    uploadToS3: (file: File, folder: string) => Promise<S3UploadResult | null>;
    progress: number;
    isUploading: boolean;
    error: string | null;
}

export function useS3Upload(): UseS3UploadReturn {
    const [progress, setProgress] = useState(0);
    const [isUploading, setIsUploading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const uploadToS3 = async (file: File, folder: string): Promise<S3UploadResult | null> => {
        setIsUploading(true);
        setProgress(0);
        setError(null);

        try {
            // Get pre-signed URL from server
            const response = await fetch(presignedUrl().url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    filename: file.name,
                    content_type: file.type,
                    folder: folder,
                }),
            });

            if (!response.ok) {
                throw new Error('Failed to get pre-signed URL');
            }

            const data = await response.json();

            // If local upload (development), return null to signal using normal upload
            if (data.use_local) {
                setIsUploading(false);
                return null;
            }

            // Upload directly to S3
            await new Promise<void>((resolve, reject) => {
                const xhr = new XMLHttpRequest();

                xhr.upload.addEventListener('progress', (event) => {
                    if (event.lengthComputable) {
                        const percentComplete = Math.round((event.loaded / event.total) * 100);
                        setProgress(percentComplete);
                    }
                });

                xhr.addEventListener('load', () => {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        resolve();
                    } else {
                        reject(new Error(`Upload failed with status ${xhr.status}`));
                    }
                });

                xhr.addEventListener('error', () => {
                    reject(new Error('Upload failed'));
                });

                xhr.open('PUT', data.url);
                xhr.setRequestHeader('Content-Type', file.type);
                xhr.send(file);
            });

            // Return the S3 key for storing in database
            const s3Url = `https://${data.bucket}.s3.${data.region}.amazonaws.com/${data.key}`;

            return {
                key: data.key,
                url: s3Url,
            };
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Upload failed';
            setError(errorMessage);
            throw err;
        } finally {
            setIsUploading(false);
        }
    };

    return {
        uploadToS3,
        progress,
        isUploading,
        error,
    };
}
