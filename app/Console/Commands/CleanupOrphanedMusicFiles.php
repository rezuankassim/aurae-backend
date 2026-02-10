<?php

namespace App\Console\Commands;

use App\Models\Music;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupOrphanedMusicFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'music:cleanup-orphaned 
                            {--disk=s3 : The storage disk to check (s3 or public)}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find and delete orphaned music files in storage that are not linked to any database record';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $disk = $this->option('disk');
        $dryRun = $this->option('dry-run');

        if (! in_array($disk, ['s3', 'public'])) {
            $this->error('Invalid disk. Use "s3" or "public".');

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->info('Running in dry-run mode. No files will be deleted.');
        }

        $this->info("Checking {$disk} disk for orphaned music files...");

        try {
            $storage = Storage::disk($disk);
        } catch (\Exception $e) {
            $this->error("Failed to connect to {$disk} disk: {$e->getMessage()}");

            return self::FAILURE;
        }

        // Get all music file paths from database
        $dbMusicPaths = Music::pluck('path')->filter()->toArray();
        $dbThumbnailPaths = Music::pluck('thumbnail')->filter()->toArray();
        $dbPaths = array_merge($dbMusicPaths, $dbThumbnailPaths);

        $this->info('Found '.count($dbMusicPaths).' music files and '.count($dbThumbnailPaths).' thumbnails in database.');

        // Get all files from storage
        $orphanedFiles = [];
        $totalSize = 0;

        // Check music folder
        $this->info('Scanning music/ folder...');
        $musicFiles = $this->getFilesRecursively($storage, 'music');

        foreach ($musicFiles as $file) {
            if (! in_array($file, $dbPaths)) {
                $orphanedFiles[] = $file;

                try {
                    $totalSize += $storage->size($file);
                } catch (\Exception $e) {
                    // Ignore size errors
                }
            }
        }

        if (empty($orphanedFiles)) {
            $this->info('No orphaned files found. Storage is clean!');

            return self::SUCCESS;
        }

        $this->warn('Found '.count($orphanedFiles).' orphaned file(s):');
        $this->newLine();

        // Display orphaned files
        $tableData = [];
        foreach ($orphanedFiles as $file) {
            $size = 'Unknown';

            try {
                $bytes = $storage->size($file);
                $size = $this->formatBytes($bytes);
            } catch (\Exception $e) {
                // Ignore
            }

            $tableData[] = [$file, $size];

            if (! $dryRun) {
                $this->line("  - {$file} ({$size})");
            }
        }

        if ($dryRun) {
            $this->table(['File Path', 'Size'], $tableData);
        }

        $this->newLine();
        $this->info('Total orphaned files: '.count($orphanedFiles));
        $this->info('Total size: '.$this->formatBytes($totalSize));
        $this->newLine();

        if ($dryRun) {
            $this->warn('Run without --dry-run to delete these files.');

            return self::SUCCESS;
        }

        // Confirm deletion
        if (! $this->confirm('Do you want to delete these orphaned files?', false)) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        // Delete orphaned files
        $deleted = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar(count($orphanedFiles));
        $bar->start();

        foreach ($orphanedFiles as $file) {
            try {
                $storage->delete($file);
                $deleted++;
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("Failed to delete {$file}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Cleanup complete!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Deleted', $deleted],
                ['Failed', $failed],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Get all files recursively from a directory.
     */
    protected function getFilesRecursively($storage, string $directory): array
    {
        $files = [];

        try {
            $allFiles = $storage->allFiles($directory);
            $files = array_merge($files, $allFiles);
        } catch (\Exception $e) {
            $this->warn("Could not scan {$directory}: {$e->getMessage()}");
        }

        return $files;
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
