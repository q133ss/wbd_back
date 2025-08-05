<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;

class TransactionCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Проверяет транзакции и обновляет их статус';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Transaction::where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(60)) // Проверяем транзакции старше 60 минут
            ->each(function ($transaction) {
                $transaction->update(['status' => 'failed']);
                $this->info("Transaction {$transaction->id} status updated to failed.");
            });
    }
}
