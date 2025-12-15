<?php

namespace App\Jobs;

use App\Services\FirebaseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendPushNotification implements ShouldQueue
{
    use Queueable;

    public $userIds;

    public $title;

    public $body;

    public $data;

    public $type;

    public $sendToAll;

    /**
     * Create a new job instance.
     */
    public function __construct(
        array $userIds = [],
        string $title = '',
        string $body = '',
        array $data = [],
        string $type = 'general',
        bool $sendToAll = false
    ) {
        $this->userIds = $userIds;
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
        $this->type = $type;
        $this->sendToAll = $sendToAll;
    }

    /**
     * Execute the job.
     */
    public function handle(FirebaseService $firebaseService): void
    {
        if ($this->sendToAll) {
            $firebaseService->sendToAll($this->title, $this->body, $this->data, $this->type);
        } else {
            $firebaseService->sendToUsers($this->userIds, $this->title, $this->body, $this->data, $this->type);
        }
    }
}
