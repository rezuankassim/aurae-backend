# Chunked Video Upload Implementation

## Overview

This document describes the chunked upload implementation for handling large video files (up to 5GB) in the Knowledge Center module. Chunked uploads significantly improve the upload experience by:

- **Preventing timeouts**: Files are uploaded in smaller chunks (10MB default)
- **Progress tracking**: Real-time progress feedback to users
- **Better reliability**: Failed chunks can be retried without re-uploading the entire file
- **Non-blocking UI**: Upload happens asynchronously without freezing the browser

## How It Works

### 1. Upload Process

1. **Initiation**: Client initiates an upload session with file metadata
2. **Chunking**: File is split into 10MB chunks (configurable)
3. **Sequential Upload**: Each chunk is uploaded one by one
4. **Progress Updates**: After each chunk, progress percentage is calculated
5. **Finalization**: Server combines all chunks into the final video file
6. **Cleanup**: Temporary chunk files are deleted

### 2. Backend Components

#### ChunkedUploadController (`app/Http/Controllers/Admin/ChunkedUploadController.php`)

**Endpoints:**

- `POST /admin/chunked-upload/initiate` - Initialize upload session
  ```json
  {
    "filename": "video.mp4",
    "total_chunks": 512,
    "file_size": 5368709120
  }
  ```
  Response:
  ```json
  {
    "upload_id": "550e8400-e29b-41d4-a716-446655440000",
    "message": "Upload session initiated successfully"
  }
  ```

- `POST /admin/chunked-upload/chunk` - Upload a single chunk
  ```
  FormData:
  - upload_id: string
  - chunk_index: number
  - chunk: File
  ```
  Response:
  ```json
  {
    "message": "Chunk uploaded successfully",
    "progress": 45.32,
    "uploaded_chunks": 232,
    "total_chunks": 512
  }
  ```

- `POST /admin/chunked-upload/finalize` - Combine chunks into final file
  ```json
  {
    "upload_id": "550e8400-e29b-41d4-a716-446655440000"
  }
  ```
  Response:
  ```json
  {
    "message": "Upload completed successfully",
    "path": "knowledge/videos/550e8400-e29b-41d4-a716-446655440000-video.mp4"
  }
  ```

- `POST /admin/chunked-upload/cancel` - Cancel upload and cleanup
  ```json
  {
    "upload_id": "550e8400-e29b-41d4-a716-446655440000"
  }
  ```

**Temporary Storage:**

Chunks are stored in `storage/app/temp/uploads/{upload_id}/` with metadata:
```
temp/uploads/{upload_id}/
├── metadata.json      # Upload metadata
├── chunk_0            # First chunk
├── chunk_1            # Second chunk
└── chunk_N            # Nth chunk
```

After finalization, chunks are combined and moved to `storage/app/public/knowledge/videos/`.

### 3. Frontend Components

#### React Hook (`resources/js/hooks/use-chunked-upload.ts`)

**Usage:**

```typescript
const { uploadFile, cancelUpload, uploadState } = useChunkedUpload({
    chunkSize: 10 * 1024 * 1024, // 10MB (default)
    onProgress: (progress) => console.log(`Progress: ${progress}%`),
    onComplete: (path) => console.log(`Uploaded to: ${path}`),
    onError: (error) => console.error(`Error: ${error}`),
});

// Start upload
await uploadFile(file);

// Cancel upload
await cancelUpload();
```

**Upload State:**

```typescript
uploadState = {
    uploadId: string | null,
    progress: number,          // 0-100
    uploading: boolean,
    error: string | null,
    videoPath: string | null   // Final path after completion
}
```

#### Updated Pages

- `resources/js/pages/admin/knowledge/create.tsx` - Create knowledge with chunked upload
- `resources/js/pages/admin/knowledge/edit.tsx` - Edit knowledge with chunked upload

**Features:**
- Progress bar showing upload percentage
- Loading spinner during upload
- Error messages if upload fails
- Disabled UI during upload
- Automatic upload on file selection

### 4. Updated Controllers

`app/Http/Controllers/Admin/KnowledgeController.php` now handles both:
- Traditional direct file upload (for backward compatibility)
- Chunked upload path (new method)

```php
// Store method
if ($request->hasFile('video')) {
    // Traditional upload
    $validated['video_path'] = $request->file('video')->store('knowledge/videos', 'public');
} elseif ($request->has('video_path')) {
    // Chunked upload - already saved, just store the path
    $validated['video_path'] = $request->input('video_path');
}
```

## Configuration

### Chunk Size

Default: 10MB (10 * 1024 * 1024 bytes)

To adjust chunk size:

```typescript
const { uploadFile } = useChunkedUpload({
    chunkSize: 5 * 1024 * 1024, // 5MB chunks
});
```

**Considerations:**
- Smaller chunks = more reliable, slower overall
- Larger chunks = faster overall, less reliable on poor connections
- 10MB is a good balance for most cases

### Server Configuration

Your PHP configuration should still support the max chunk size + overhead:

```ini
upload_max_filesize = 50M      # Must be > chunk size
post_max_size = 50M            # Must be > chunk size
max_execution_time = 300       # Enough time for chunk upload
memory_limit = 256M            # Enough memory for chunk processing
```

### Nginx Configuration (if using)

```nginx
client_max_body_size 50M;      # Must be > chunk size
client_body_timeout 300s;
```

## Advantages Over Traditional Upload

| Feature | Traditional Upload | Chunked Upload |
|---------|-------------------|----------------|
| Max file size | Limited by PHP/server | Virtually unlimited |
| Progress tracking | None/basic | Precise percentage |
| Network reliability | One failure = restart | Retry individual chunks |
| Browser timeout | High risk with large files | Very low risk |
| Memory usage | Entire file in memory | Only one chunk at a time |
| User experience | Blocked UI | Live progress feedback |

## Testing

### Manual Testing

1. Select a large video file (>100MB recommended)
2. Observe progress bar filling up
3. Check network tab - you should see multiple chunk uploads
4. Verify video is playable after upload completes

### Testing Large Files

For 5GB files:
- Total chunks: ~512 (at 10MB per chunk)
- Expected upload time: Depends on network speed
  - 10 Mbps: ~67 minutes
  - 50 Mbps: ~13 minutes
  - 100 Mbps: ~7 minutes

## Troubleshooting

### Upload Fails Immediately

**Check:**
- CSRF token is present in page (`<meta name="csrf-token">`)
- User is authenticated and has admin privileges
- Storage permissions: `storage/app/temp` must be writable

### Upload Stalls at X%

**Check:**
- Server logs for PHP errors
- Network connectivity
- Disk space on server
- PHP timeout settings

### Upload Completes but Video Path is Null

**Check:**
- Finalize endpoint response
- Server logs during finalization
- File permissions on `storage/app/public/knowledge/videos`

### Chunks Not Cleaning Up

**Check:**
- Finalize endpoint was called successfully
- Look for orphaned directories in `storage/app/temp/uploads/`

**Cleanup orphaned uploads:**

```bash
# Laravel command (you can create this)
php artisan chunked-uploads:cleanup

# Or manually
rm -rf storage/app/temp/uploads/*
```

## Future Enhancements

### Potential Improvements

1. **Parallel Chunk Upload**: Upload multiple chunks simultaneously
2. **Resume Support**: Resume interrupted uploads
3. **Retry Logic**: Automatic retry for failed chunks
4. **Background Processing**: Move chunk combining to queue
5. **Video Transcoding**: Automatically transcode videos to web-friendly formats
6. **Cloud Storage**: Direct upload to S3/DigitalOcean Spaces
7. **Cleanup Job**: Scheduled job to remove old incomplete uploads

### Cloud Storage Integration (Recommended for Production)

For production, consider using AWS S3 presigned URLs for direct-to-S3 upload:

**Benefits:**
- No server bandwidth used
- Faster uploads (direct to CDN)
- Infinite scalability
- Built-in redundancy

**Implementation outline:**
1. Generate presigned S3 URL in backend
2. Upload file directly from browser to S3
3. Store S3 URL in database
4. Serve videos from CloudFront CDN

## Related Files

- `/app/Http/Controllers/Admin/ChunkedUploadController.php` - Backend controller
- `/resources/js/hooks/use-chunked-upload.ts` - React hook
- `/resources/js/components/ui/progress.tsx` - Progress bar component
- `/resources/js/pages/admin/knowledge/create.tsx` - Create page
- `/resources/js/pages/admin/knowledge/edit.tsx` - Edit page
- `/routes/admin.php` - Routes configuration

## Security Considerations

1. **Authentication**: All endpoints require authentication + admin middleware
2. **CSRF Protection**: All requests include CSRF tokens
3. **File Validation**: Only video MIME types accepted
4. **Upload ID Validation**: UUID format prevents path traversal
5. **Cleanup**: Temporary files are deleted after finalization
6. **Timeout Cleanup**: Consider implementing a cron job to delete uploads older than 24 hours

## Performance Notes

- **Disk I/O**: Chunk assembly is I/O intensive - ensure fast disk (SSD recommended)
- **Memory**: Memory usage is constant regardless of file size (only one chunk loaded at a time)
- **Concurrent Uploads**: Each upload session is independent - server can handle multiple simultaneous uploads
- **Database**: No database queries during chunk upload (only during init/finalize)

## Migration from Traditional Upload

The implementation is **backward compatible**. Existing code using traditional file upload will continue to work. The chunked upload is used automatically when users upload files through the updated frontend forms.

No database migrations are required - the same `video_path` column is used for both methods.
