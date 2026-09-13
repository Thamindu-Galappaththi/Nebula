<?php

namespace App\Console\Commands;

use App\Support\SpecifiedFranchisePaymentRemoval;
use Illuminate\Console\Command;

class RemoveSpecifiedFranchisePayments extends Command
{
    protected $signature = 'payments:remove-specified-franchise {--dry-run : List matching intakes without changing data}';

    protected $description = 'Remove franchise payments from HND EEE (24/26, 25/27), HND Digital Technology (24/26), and Foundation (Batch 7, 8).';

    public function handle(): int
    {
        $matches = SpecifiedFranchisePaymentRemoval::matchingIntakes();

        if ($matches->isEmpty()) {
            $this->info('No matching intakes found.');
            return self::SUCCESS;
        }

        $this->table(
            ['intake_id', 'course', 'batch', 'franchise_payment'],
            $matches->map(fn ($intake) => [
                $intake->intake_id,
                optional($intake->course)->course_name ?: $intake->course_name,
                $intake->batch,
                $intake->franchise_payment,
            ])->all()
        );

        if ($this->option('dry-run')) {
            $this->warn('Dry-run mode — no changes written.');
            return self::SUCCESS;
        }

        $summary = SpecifiedFranchisePaymentRemoval::run();
        $this->info("Updated intakes: {$summary['intakes']}");
        $this->info("Updated payment plans: {$summary['payment_plans']}");
        $this->info("Updated unpaid student installments: {$summary['student_installments']}");

        return self::SUCCESS;
    }
}
