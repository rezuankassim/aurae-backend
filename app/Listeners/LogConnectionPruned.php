<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use Laravel\Reverb\Events\ConnectionPruned;

class LogConnectionPruned
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ConnectionPruned $event): void
    {
        $connection = $event->connection->connection();
        
        $logData = [
            'event' => 'connection_pruned',
            'connection_id' => $connection->id(),
            'connection_identifier' => $connection->identifier(),
            'app_id' => $connection->app()->id(),
            'app_key' => $connection->app()->key(),
            'origin' => $connection->origin(),
            'last_seen_at' => $connection->lastSeenAt() ? date('Y-m-d H:i:s', $connection->lastSeenAt()) : null,
            'last_seen_seconds_ago' => $connection->lastSeenAt() ? time() - $connection->lastSeenAt() : null,
            'was_inactive' => $connection->isInactive(),
            'was_stale' => $connection->isStale(),
            'uses_control_frames' => $connection->usesControlFrames(),
            'ping_interval' => $connection->app()->pingInterval(),
            'channel_data' => $event->connection->data(),
        ];

        Log::warning('WebSocket connection pruned due to inactivity', $logData);
    }
}
