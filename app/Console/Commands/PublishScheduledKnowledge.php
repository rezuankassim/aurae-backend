<?php

namespace App\Console\Commands;

use App\Models\Knowledge;
use Illuminate\Console\Command;

class PublishScheduledKnowledge extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'knowledge:publish-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish knowledge entries that have reached their scheduled published_at time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Find all unpublished knowledge with published_at <= now
        $knowledgeToPublish = Knowledge::where('is_published', false)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get();

        if ($knowledgeToPublish->isEmpty()) {
            $this->info('No scheduled knowledge entries to publish.');

            return Command::SUCCESS;
        }

        $count = $knowledgeToPublish->count();

        // Update all matching knowledge entries to published
        Knowledge::where('is_published', false)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->update(['is_published' => true]);

        $this->info("Successfully published {$count} scheduled knowledge entry(ies).");

        return Command::SUCCESS;
    }
}
