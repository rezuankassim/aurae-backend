<?php

namespace App\Console\Commands;

use App\Models\Knowledge;
use Illuminate\Console\Command;

class PublishScheduledKnowledge extends Command
{
    protected $signature = 'knowledge:publish-scheduled';

    protected $description = 'Publish tutorial entries that have reached their scheduled published_at time';

    public function handle()
    {

        $knowledgeToPublish = Knowledge::where('is_published', false)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get();

        if ($knowledgeToPublish->isEmpty()) {
            $this->info('No scheduled tutorials to publish.');

            return Command::SUCCESS;
        }

        $count = $knowledgeToPublish->count();

        Knowledge::where('is_published', false)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->update(['is_published' => true]);

        $this->info("Successfully published {$count} scheduled tutorial(s).");

        return Command::SUCCESS;
    }
}
