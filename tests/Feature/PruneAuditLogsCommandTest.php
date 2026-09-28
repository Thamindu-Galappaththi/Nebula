<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneAuditLogsCommandTest extends TestCase
{
    use RefreshDatabase;

    private function makeLog(string $createdAt): AuditLog
    {
        $log = AuditLog::create([
            'user_name' => 'Retention Tester',
            'user_role' => 'Developer',
            'action' => 'created',
            'summary' => 'Retention test log.',
            'item_label' => 'Retention Item',
        ]);

        $log->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

        return $log->fresh();
    }

    public function test_prune_command_deletes_logs_older_than_configured_days(): void
    {
        config(['audit.retention_days' => 7]);

        $old = $this->makeLog(now()->subDays(8)->toDateTimeString());
        $keep = $this->makeLog(now()->subDays(3)->toDateTimeString());

        $this->artisan('audit:prune')
            ->expectsOutputToContain('Deleted 1 audit log(s) older than 7 day(s)')
            ->assertSuccessful();

        $this->assertDatabaseMissing('audit_logs', ['id' => $old->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $keep->id]);
    }

    public function test_prune_command_days_option_overrides_config(): void
    {
        config(['audit.retention_days' => 7]);

        $threeDaysOld = $this->makeLog(now()->subDays(4)->toDateTimeString());
        $today = $this->makeLog(now()->toDateTimeString());

        $this->artisan('audit:prune', ['--days' => 3])
            ->expectsOutputToContain('Deleted 1 audit log(s) older than 3 day(s)')
            ->assertSuccessful();

        $this->assertDatabaseMissing('audit_logs', ['id' => $threeDaysOld->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $today->id]);
    }

    public function test_prune_command_rejects_invalid_days(): void
    {
        $this->artisan('audit:prune', ['--days' => 0])
            ->expectsOutputToContain('Retention days must be at least 1.')
            ->assertFailed();
    }
}
