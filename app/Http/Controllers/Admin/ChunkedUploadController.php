<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChunkedUploadController extends Controller
{
    /**
     * Initialize a chunked upload session
     */
    public function initiate(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
            'total_chunks' => 'required|integer|min:1',
            'file_size' => 'required|integer|min:1',
        ]);

        $uploadId = Str::uuid()->toString();
        $tempDir = "temp/uploads/{$uploadId}";

        // Create temporary directory
        Storage::disk('local')->makeDirectory($tempDir);

        // Store upload metadata
        Storage::disk('local')->put("{$tempDir}/metadata.json", json_encode([
            'filename' => $request->filename,
            'total_chunks' => $request->total_chunks,
            'file_size' => $request->file_size,
            'uploaded_chunks' => [],
            'created_at' => now()->toIso8601String(),
        ]));

        return response()->json([
            'upload_id' => $uploadId,
            'message' => 'Upload session initiated successfully',
        ]);
    }

    /**
     * Upload a single chunk
     */
    public function uploadChunk(Request $request)
    {
        $request->validate([
            'upload_id' => 'required|string',
            'chunk_index' => 'required|integer|min:0',
            'chunk' => 'required|file',
        ]);

        $uploadId = $request->upload_id;
        $chunkIndex = $request->chunk_index;
        $tempDir = "temp/uploads/{$uploadId}";

        // Verify upload session exists
        if (! Storage::disk('local')->exists("{$tempDir}/metadata.json")) {
            return response()->json(['error' => 'Invalid upload session'], 404);
        }

        // Save chunk
        $chunkPath = "{$tempDir}/chunk_{$chunkIndex}";
        Storage::disk('local')->putFileAs(
            dirname($chunkPath),
            $request->file('chunk'),
            basename($chunkPath)
        );

        // Update metadata
        $metadata = json_decode(Storage::disk('local')->get("{$tempDir}/metadata.json"), true);
        $metadata['uploaded_chunks'][] = $chunkIndex;
        $metadata['uploaded_chunks'] = array_unique($metadata['uploaded_chunks']);
        sort($metadata['uploaded_chunks']);
        Storage::disk('local')->put("{$tempDir}/metadata.json", json_encode($metadata));

        $progress = (count($metadata['uploaded_chunks']) / $metadata['total_chunks']) * 100;

        return response()->json([
            'message' => 'Chunk uploaded successfully',
            'progress' => round($progress, 2),
            'uploaded_chunks' => count($metadata['uploaded_chunks']),
            'total_chunks' => $metadata['total_chunks'],
        ]);
    }

    /**
     * Finalize the upload by combining all chunks
     */
    public function finalize(Request $request)
    {
        $request->validate([
            'upload_id' => 'required|string',
        ]);

        $uploadId = $request->upload_id;
        $tempDir = "temp/uploads/{$uploadId}";

        // Verify upload session exists
        if (! Storage::disk('local')->exists("{$tempDir}/metadata.json")) {
            return response()->json(['error' => 'Invalid upload session'], 404);
        }

        $metadata = json_decode(Storage::disk('local')->get("{$tempDir}/metadata.json"), true);

        // Verify all chunks are uploaded
        if (count($metadata['uploaded_chunks']) !== $metadata['total_chunks']) {
            return response()->json([
                'error' => 'Not all chunks uploaded',
                'uploaded' => count($metadata['uploaded_chunks']),
                'total' => $metadata['total_chunks'],
            ], 400);
        }

        // Combine chunks
        $finalPath = 'knowledge/videos/'.Str::uuid().'-'.$metadata['filename'];
        $localPath = Storage::disk('local')->path($tempDir);
        $finalLocalPath = Storage::disk('public')->path($finalPath);

        // Ensure directory exists
        $finalDir = dirname($finalLocalPath);
        if (! file_exists($finalDir)) {
            mkdir($finalDir, 0755, true);
        }

        // Create final file
        $finalFile = fopen($finalLocalPath, 'wb');

        for ($i = 0; $i < $metadata['total_chunks']; $i++) {
            $chunkPath = "{$localPath}/chunk_{$i}";
            if (! file_exists($chunkPath)) {
                fclose($finalFile);

                return response()->json(['error' => "Missing chunk {$i}"], 500);
            }

            $chunkContent = file_get_contents($chunkPath);
            fwrite($finalFile, $chunkContent);
        }

        fclose($finalFile);

        // Clean up temporary files
        Storage::disk('local')->deleteDirectory($tempDir);

        return response()->json([
            'message' => 'Upload completed successfully',
            'path' => $finalPath,
        ]);
    }

    /**
     * Cancel an upload and clean up temporary files
     */
    public function cancel(Request $request)
    {
        $request->validate([
            'upload_id' => 'required|string',
        ]);

        $uploadId = $request->upload_id;
        $tempDir = "temp/uploads/{$uploadId}";

        if (Storage::disk('local')->exists($tempDir)) {
            Storage::disk('local')->deleteDirectory($tempDir);
        }

        return response()->json([
            'message' => 'Upload cancelled successfully',
        ]);
    }
}
