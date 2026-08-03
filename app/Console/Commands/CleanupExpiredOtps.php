<?php

namespace App\Console\Commands;

use App\Models\LoginOtp;
use Illuminate\Console\Command;

class CleanupExpiredOtps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'otp:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired OTP records from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deletedCount = LoginOtp::where('expires_at', '<', now())->delete();

        $this->info("Deleted {$deletedCount} expired OTP(s).");

        return Command::SUCCESS;
    }
}
