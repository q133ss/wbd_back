<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateUserOnlineStatus implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected User $user) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->user->update([
            'last_seen_at' => now()
        ]);
    }
}
