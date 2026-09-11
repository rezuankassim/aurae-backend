<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CleanupOrphanedUploads extends Command
{
    protected $signature = 'uploads:cleanup {--hours=24 : Delete uploads older than this many hours}';

    protected $description = 'Clean up orphaned and incomplete uploads to free up storage space';

    public function handle()
    {
        $hours = (int) $this->option('hours');
        $cutoffTime = now()->subHours($hours);

        $this->info("Cleaning up uploads older than {$hours} hours (before {$cutoffTime})...");
        $this->newLine();

        $totalDeleted = 0;
        $totalSize = 0;

        $this->info('🔍 Checking for incomplete chunked uploads...');
        [$deletedChunks, $chunksSize] = $this->cleanupIncompleteChunks($cutoffTime);
        $totalDeleted += $deletedChunks;
        $totalSize += $chunksSize;

        $this->info('🔍 Checking for orphaned video files...');
        [$deletedVideos, $videosSize] = $this->cleanupOrphanedVideos($cutoffTime);
        $totalDeleted += $deletedVideos;
        $totalSize += $videosSize;

        $this->newLine();
        $this->info('✅ Cleanup complete!');
        $this->info("   - Files deleted: {$totalDeleted}");
        $this->info('   - Space freed: '.$this->formatBytes($totalSize));

        return Command::SUCCESS;
    }

    protected function cleanupIncompleteChunks($cutoffTime): array
    {
        $tempPath = 'temp/uploads';
        $deleted = 0;
        $size = 0;

        if (! Storage::disk('local')->exists($tempPath)) {
            $this->line('   No temp upload directory found.');

            return [0, 0];
        }

        $uploadDirs = Storage::disk('local')->directories($tempPath);

        foreach ($uploadDirs as $dir) {
            $metadataPath = "{$dir}/metadata.json";

            if (! Storage::disk('local')->exists($metadataPath)) {
                continue;
            }

            $metadata = json_decode(Storage::disk('local')->get($metadataPath), true);
            $createdAt = \Carbon\Carbon::parse($metadata['created_at'] ?? now());

            if ($createdAt->lt($cutoffTime)) {

                $files = Storage::disk('local')->allFiles($dir);
                foreach ($files as $file) {
                    $size += Storage::disk('local')->size($file);
                }

                Storage::disk('local')->deleteDirectory($dir);
                $deleted++;

                $uploadId = basename($dir);
                $this->line("   ❌ Deleted incomplete upload: {$uploadId}");
            }
        }

        if ($deleted === 0) {
            $this->line('   ✓ No incomplete uploads to clean up.');
        }

        return [$deleted, $size];
    }

    protected function cleanupOrphanedVideos($cutoffTime): array
    {
        $videoPath = 'knowledge/videos';
        $deleted = 0;
        $size = 0;

        if (! Storage::disk('public')->exists($videoPath)) {
            $this->line('   No videos directory found.');

            return [0, 0];
        }

        $allVideos = Storage::disk('public')->files($videoPath);

        $linkedVideos = DB::table('knowledge')
            ->whereNotNull('video_path')
            ->pluck('video_path')
            ->toArray();

        foreach ($allVideos as $video) {

            $lastModified = Storage::disk('public')->lastModified($video);
            $modifiedAt = \Carbon\Carbon::createFromTimestamp($lastModified);

            if ($modifiedAt->lt($cutoffTime)) {

                if (! in_array($video, $linkedVideos)) {
                    $fileSize = Storage::disk('public')->size($video);
                    $size += $fileSize;

                    Storage::disk('public')->delete($video);
                    $deleted++;

                    $fileName = basename($video);
                    $this->line("   ❌ Deleted orphaned video: {$fileName}");
                }
            }
        }

        if ($deleted === 0) {
            $this->line('   ✓ No orphaned videos to clean up.');
        }

        return [$deleted, $size];
    }

    protected function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
