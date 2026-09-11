<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;

class PublishScheduledNews extends Command
{
    protected $signature = 'news:publish-scheduled';

    protected $description = 'Publish news items that have reached their scheduled published_at time';

    public function handle()
    {

        $newsToPublish = News::where('is_published', false)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get();

        if ($newsToPublish->isEmpty()) {
            $this->info('No scheduled news to publish.');

            return Command::SUCCESS;
        }

        $count = $newsToPublish->count();

        News::where('is_published', false)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->update(['is_published' => true]);

        $this->info("Successfully published {$count} scheduled news item(s).");

        return Command::SUCCESS;
    }
}
