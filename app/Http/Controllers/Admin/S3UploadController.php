<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Aws\S3\S3Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class S3UploadController extends Controller
{
    /**
     * Generate a pre-signed URL for direct S3 upload.
     */
    public function presignedUrl(Request $request): JsonResponse
    {
        $request->validate([
            'filename' => ['required', 'string'],
            'content_type' => ['required', 'string'],
            'folder' => ['required', 'string', 'in:music,music/thumbnails'],
        ]);

        // Check if we're in production (use S3) or development (use local)
        if (! app()->environment('production')) {
            return response()->json([
                'use_local' => true,
                'message' => 'Use local upload in development',
            ]);
        }

        $filename = $request->input('filename');
        $contentType = $request->input('content_type');
        $folder = $request->input('folder');

        // Generate unique filename
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $uniqueFilename = Str::uuid().'.'.$extension;
        $key = $folder.'/'.$uniqueFilename;

        $s3Client = new S3Client([
            'version' => 'latest',
            'region' => config('filesystems.disks.s3.region'),
            'credentials' => [
                'key' => config('filesystems.disks.s3.key'),
                'secret' => config('filesystems.disks.s3.secret'),
            ],
        ]);

        $cmd = $s3Client->getCommand('PutObject', [
            'Bucket' => config('filesystems.disks.s3.bucket'),
            'Key' => $key,
            'ContentType' => $contentType,
        ]);

        $presignedRequest = $s3Client->createPresignedRequest($cmd, '+30 minutes');
        $presignedUrl = (string) $presignedRequest->getUri();

        return response()->json([
            'use_local' => false,
            'url' => $presignedUrl,
            'key' => $key,
            'bucket' => config('filesystems.disks.s3.bucket'),
            'region' => config('filesystems.disks.s3.region'),
        ]);
    }
}
