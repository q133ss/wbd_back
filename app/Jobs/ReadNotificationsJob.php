<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ReadNotificationsJob implements ShouldQueue
{
    use Queueable;

    private array $notificationIds;

    /**
     * Create a new job instance.
     */
    public function __construct(array $notificationIds)
    {
        $this->notificationIds = $notificationIds;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Notification::whereIn('id', $this->notificationIds)->update(['is_read' => true]);
    }
}
