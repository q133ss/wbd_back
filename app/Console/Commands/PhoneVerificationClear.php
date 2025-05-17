<?php

namespace App\Console\Commands;

use App\Models\PhoneVerification;
use Illuminate\Console\Command;

class PhoneVerificationClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verification:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Очищает все коды подтверждения, которые не были использованы';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        PhoneVerification::where('expires_at', '<', now())
            ->delete();
    }
}
