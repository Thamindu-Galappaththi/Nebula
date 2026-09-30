<?php

namespace App\Console\Commands;

use App\Models\Semester;
use Illuminate\Console\Command;

class FixSemesterNames extends Command
{
    protected $signature = 'semester:fix-names {--dry-run : Show changes without writing}';

    protected $description = 'Rename semester rows that stored the database id (31, 37, 38) to A/B or 1/2';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $semesters = Semester::query()->with('course')->orderBy('id')->get();
        $updated = 0;

        foreach ($semesters as $semester) {
            if (!$semester->nameLooksLikeRowId()) {
                continue;
            }

            $newName = $semester->displayName();
            if ($newName === (string) $semester->name) {
                continue;
            }

            $clash = Semester::query()
                ->where('course_id', $semester->course_id)
                ->where('intake_id', $semester->intake_id)
                ->where('id', '!=', $semester->id)
                ->where('name', $newName)
                ->exists();

            if ($clash) {
                $this->warn("Skip id {$semester->id}: '{$newName}' already exists on this intake.");
                continue;
            }

            $this->line("id {$semester->id}: '{$semester->name}' → '{$newName}'");
            if (!$dryRun) {
                $semester->update(['name' => $newName]);
            }
            $updated++;
        }

        $this->info(($dryRun ? 'Would update' : 'Updated')." {$updated} semester(s).");

        return self::SUCCESS;
    }
}
