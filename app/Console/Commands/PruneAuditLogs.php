<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

class PruneAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:prune-audit-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete database audit logs older than 30 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Pruning audit logs older than 30 days...');

        $count = AuditLog::where('created_at', '<', now()->subDays(30))->delete();

        $this->info("Successfully deleted {$count} old audit log records.");
        
        Log::info("Audit log pruning task completed. Deleted {$count} records.");
    }
}
