<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;

class PublishScheduledNews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'news:publish-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish news items that have reached their scheduled published_at time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Find all unpublished news with published_at <= now
        $newsToPublish = News::where('is_published', false)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get();

        if ($newsToPublish->isEmpty()) {
            $this->info('No scheduled news to publish.');

            return Command::SUCCESS;
        }

        $count = $newsToPublish->count();

        // Update all matching news to published
        News::where('is_published', false)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->update(['is_published' => true]);

        $this->info("Successfully published {$count} scheduled news item(s).");

        return Command::SUCCESS;
    }
}
