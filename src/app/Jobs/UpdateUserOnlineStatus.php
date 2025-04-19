<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UpdateUserOnlineStatus implements ShouldQueue
{
    use Queueable;

    private string $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $userId) {
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Пакетное обновление без загрузки модели
        DB::table('users')
            ->where('id', $this->userId)
            ->update([
                'last_seen_at' => now(),
                'updated_at' => DB::raw('updated_at') // Не изменяем updated_at
            ]);

        // Очищаем кеш
        Cache::forget("user:online:{$this->userId}");
    }
}
