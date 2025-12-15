<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Knowledge;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VideoStreamController extends Controller
{
    /**
     * Stream video with support for range requests (for seeking/progressive download)
     */
    public function streamKnowledgeVideo(Knowledge $knowledge)
    {
        // Check if knowledge has a video
        if (! $knowledge->video_path) {
            return response()->json([
                'status' => 404,
                'message' => 'Video not found.',
            ], 404);
        }

        // Check if video file exists
        if (! Storage::disk('public')->exists($knowledge->video_path)) {
            return response()->json([
                'status' => 404,
                'message' => 'Video file not found.',
            ], 404);
        }

        $path = Storage::disk('public')->path($knowledge->video_path);
        $size = Storage::disk('public')->size($knowledge->video_path);
        $mimeType = Storage::disk('public')->mimeType($knowledge->video_path);

        $stream = fopen($path, 'r');

        $start = 0;
        $end = $size - 1;
        $length = $size;

        // Handle range request for video seeking
        if (request()->hasHeader('Range')) {
            $range = request()->header('Range');
            $range = str_replace('bytes=', '', $range);
            $range = explode('-', $range);

            $start = intval($range[0]);
            $end = isset($range[1]) && is_numeric($range[1]) ? intval($range[1]) : $end;
            $length = $end - $start + 1;

            fseek($stream, $start);

            $response = new StreamedResponse(function () use ($stream, $length) {
                $chunkSize = 1024 * 1024; // 1MB chunks
                $bytesRead = 0;

                while (! feof($stream) && $bytesRead < $length) {
                    $bytesToRead = min($chunkSize, $length - $bytesRead);
                    echo fread($stream, $bytesToRead);
                    flush();
                    $bytesRead += $bytesToRead;
                }

                fclose($stream);
            }, 206);

            $response->headers->set('Content-Range', "bytes $start-$end/$size");
        } else {
            $response = new StreamedResponse(function () use ($stream) {
                fpassthru($stream);
                fclose($stream);
            }, 200);
        }

        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Length', $length);
        $response->headers->set('Accept-Ranges', 'bytes');
        $response->headers->set('Cache-Control', 'public, max-age=31536000');

        return $response;
    }
}
