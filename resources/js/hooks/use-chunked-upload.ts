import { useState } from 'react';

interface UploadProgress {
    uploadId: string | null;
    progress: number;
    uploading: boolean;
    error: string | null;
    videoPath: string | null;
}

interface ChunkedUploadOptions {
    chunkSize?: number; // in bytes, default 10MB
    onProgress?: (progress: number) => void;
    onComplete?: (path: string) => void;
    onError?: (error: string) => void;
}

export function useChunkedUpload(options: ChunkedUploadOptions = {}) {
    const { chunkSize = 10 * 1024 * 1024, onProgress, onComplete, onError } = options;

    const [uploadState, setUploadState] = useState<UploadProgress>({
        uploadId: null,
        progress: 0,
        uploading: false,
        error: null,
        videoPath: null,
    });

    const uploadFile = async (file: File) => {
        setUploadState({
            uploadId: null,
            progress: 0,
            uploading: true,
            error: null,
            videoPath: null,
        });

        try {
            // Get CSRF token from meta tag or cookie
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            if (!csrfToken) {
                throw new Error('CSRF token not found');
            }

            const totalChunks = Math.ceil(file.size / chunkSize);

            // Step 1: Initiate upload
            const initiateResponse = await fetch('/admin/chunked-upload/initiate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    filename: file.name,
                    total_chunks: totalChunks,
                    file_size: file.size,
                }),
            });

            if (!initiateResponse.ok) {
                const errorData = await initiateResponse.json().catch(() => ({}));
                throw new Error(errorData.message || `Failed to initiate upload (${initiateResponse.status})`);
            }

            const { upload_id } = await initiateResponse.json();

            setUploadState((prev) => ({ ...prev, uploadId: upload_id }));

            // Step 2: Upload chunks
            for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                const start = chunkIndex * chunkSize;
                const end = Math.min(start + chunkSize, file.size);
                const chunk = file.slice(start, end);

                const formData = new FormData();
                formData.append('upload_id', upload_id);
                formData.append('chunk_index', chunkIndex.toString());
                formData.append('chunk', chunk);

                const chunkResponse = await fetch('/admin/chunked-upload/chunk', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        Accept: 'application/json',
                    },
                    body: formData,
                });

                if (!chunkResponse.ok) {
                    const errorData = await chunkResponse.json().catch(() => ({}));
                    throw new Error(errorData.message || `Failed to upload chunk ${chunkIndex} (${chunkResponse.status})`);
                }

                const { progress } = await chunkResponse.json();

                setUploadState((prev) => ({ ...prev, progress }));
                onProgress?.(progress);
            }

            // Step 3: Finalize upload
            const finalizeResponse = await fetch('/admin/chunked-upload/finalize', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    upload_id,
                }),
            });

            if (!finalizeResponse.ok) {
                const errorData = await finalizeResponse.json().catch(() => ({}));
                throw new Error(errorData.message || `Failed to finalize upload (${finalizeResponse.status})`);
            }

            const { path } = await finalizeResponse.json();

            setUploadState({
                uploadId: upload_id,
                progress: 100,
                uploading: false,
                error: null,
                videoPath: path,
            });

            onComplete?.(path);

            return path;
        } catch (error) {
            const errorMessage = error instanceof Error ? error.message : 'Upload failed';

            setUploadState((prev) => ({
                ...prev,
                uploading: false,
                error: errorMessage,
            }));

            onError?.(errorMessage);

            // Cancel upload if it was initiated
            if (uploadState.uploadId) {
                try {
                    const cancelCsrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    await fetch('/admin/chunked-upload/cancel', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': cancelCsrfToken,
                            Accept: 'application/json',
                        },
                        body: JSON.stringify({
                            upload_id: uploadState.uploadId,
                        }),
                    });
                } catch {
                    // Ignore cancel errors
                }
            }

            throw error;
        }
    };

    const cancelUpload = async () => {
        if (!uploadState.uploadId) return;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            await fetch('/admin/chunked-upload/cancel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    upload_id: uploadState.uploadId,
                }),
            });

            setUploadState({
                uploadId: null,
                progress: 0,
                uploading: false,
                error: null,
                videoPath: null,
            });
        } catch (error) {
            console.error('Failed to cancel upload:', error);
        }
    };

    return {
        uploadFile,
        cancelUpload,
        uploadState,
    };
}
