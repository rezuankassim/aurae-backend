<?php

namespace App\Console\Commands;

use App\Models\Music;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateMusicToS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'music:migrate-to-s3 {--dry-run : Show what would be migrated without actually migrating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate all music files from local storage to S3';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in dry-run mode. No files will be migrated.');
        }

        $music = Music::all();

        if ($music->isEmpty()) {
            $this->info('No music records found.');

            return self::SUCCESS;
        }

        $this->info("Found {$music->count()} music record(s) to process.");

        $bar = $this->output->createProgressBar($music->count());
        $bar->start();

        $migrated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($music as $item) {
            try {
                $updated = false;

                // Migrate music file
                if ($item->path && Storage::disk('public')->exists($item->path)) {
                    if (! $dryRun) {
                        $fileContents = Storage::disk('public')->get($item->path);
                        Storage::disk('s3')->put($item->path, $fileContents);
                        $updated = true;
                    }
                    $this->line(" Music file: {$item->path}");
                } elseif ($item->path && Storage::disk('s3')->exists($item->path)) {
                    $this->line(" Music file already on S3: {$item->path}");
                    $skipped++;
                }

                // Migrate thumbnail
                if ($item->thumbnail && Storage::disk('public')->exists($item->thumbnail)) {
                    if (! $dryRun) {
                        $thumbnailContents = Storage::disk('public')->get($item->thumbnail);
                        Storage::disk('s3')->put($item->thumbnail, $thumbnailContents);
                        $updated = true;
                    }
                    $this->line(" Thumbnail: {$item->thumbnail}");
                } elseif ($item->thumbnail && Storage::disk('s3')->exists($item->thumbnail)) {
                    $this->line(" Thumbnail already on S3: {$item->thumbnail}");
                }

                if ($updated) {
                    $migrated++;
                }
            } catch (\Exception $e) {
                $failed++;
                $this->error(" Failed to migrate music ID {$item->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Migration complete!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Migrated', $migrated],
                ['Skipped (already on S3)', $skipped],
                ['Failed', $failed],
            ]
        );

        if (! $dryRun && $migrated > 0) {
            if ($this->confirm('Do you want to delete the local files now?', false)) {
                $this->deleteLocalFiles($music);
            } else {
                $this->info('Local files kept. You can manually delete them later from storage/app/public/music/');
            }
        }

        return self::SUCCESS;
    }

    /**
     * Delete local files after successful migration.
     */
    protected function deleteLocalFiles($music): void
    {
        $deleted = 0;

        foreach ($music as $item) {
            if ($item->path && Storage::disk('public')->exists($item->path)) {
                Storage::disk('public')->delete($item->path);
                $deleted++;
            }

            if ($item->thumbnail && Storage::disk('public')->exists($item->thumbnail)) {
                Storage::disk('public')->delete($item->thumbnail);
            }
        }

        $this->info("Deleted {$deleted} local file(s).");
    }
}
