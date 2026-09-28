<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class PruneAuditLogs extends Command
{
    protected $signature = 'audit:prune
                            {--days= : Keep logs from the last N days. Defaults to AUDIT_LOG_RETENTION_DAYS.}';

    protected $description = 'Delete audit logs older than the configured retention period';

    public function handle(): int
    {
        $daysOption = $this->option('days');
        $days = $daysOption === null || $daysOption === ''
            ? (int) config('audit.retention_days', 7)
            : (int) $daysOption;

        if ($days < 1) {
            $this->error('Retention days must be at least 1.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $deleted = AuditLog::pruneOlderThan($days);

        $this->info("Deleted {$deleted} audit log(s) older than {$days} day(s) (before {$cutoff->toDateTimeString()}).");

        return self::SUCCESS;
    }
}
