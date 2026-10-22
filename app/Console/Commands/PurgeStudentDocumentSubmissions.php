<?php

namespace App\Console\Commands;

use App\Services\StudentDocumentCollectionService;
use Illuminate\Console\Command;

class PurgeStudentDocumentSubmissions extends Command
{
    protected $signature = 'student-documents:purge';

    protected $description = 'Expire unused document links and permanently delete submitted documents after the 7-day retention period';

    public function handle(StudentDocumentCollectionService $service): int
    {
        $expired = $service->expireUnusedLinks();
        $purged = $service->purgeExpiredSubmissions();

        $this->info("Expired {$expired} unused submission link(s).");
        $this->info("Purged {$purged} submitted document package(s).");

        return self::SUCCESS;
    }
}
