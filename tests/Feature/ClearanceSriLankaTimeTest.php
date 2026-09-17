<?php

namespace Tests\Feature;

use App\Models\ClearanceRequest;
use Carbon\Carbon;
use Tests\TestCase;

class ClearanceSriLankaTimeTest extends TestCase
{
    public function test_requested_and_processed_times_use_sri_lanka_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-17 08:42:00', 'Asia/Colombo'));

        $request = new ClearanceRequest();
        $request->requested_at = now();
        $request->approved_at = now()->addMinutes(48);

        $this->assertSame('17/09/2026 08:42', $request->requestedAtSriLanka()?->format('d/m/Y H:i'));
        $this->assertSame('17/09/2026 09:30', $request->processedAtSriLanka()?->format('d/m/Y H:i'));
        $this->assertNotSame('17/09/2026 14:12', $request->requestedAtSriLanka()?->format('d/m/Y H:i'));

        Carbon::setTestNow();
    }

    public function test_clearance_management_pages_render_sri_lanka_times(): void
    {
        $partials = [
            resource_path('views/clearance/partials/pending_requests_body.blade.php'),
            resource_path('views/clearance/partials/processed_requests_body.blade.php'),
        ];

        $pending = file_get_contents($partials[0]);
        $processed = file_get_contents($partials[1]);

        $this->assertStringContainsString('requestedAtSriLanka()', $pending);
        $this->assertStringContainsString('processedAtSriLanka()', $processed);
        $this->assertStringNotContainsString('requested_at->format(', $pending);
        $this->assertStringNotContainsString('approved_at->format(', $processed);

        $pages = [
            resource_path('views/clearance/library_clearance.blade.php'),
            resource_path('views/clearance/hostel_clearance.blade.php'),
            resource_path('views/clearance/project_clearance.blade.php'),
            resource_path('views/clearance/payment_clearance.blade.php'),
        ];

        foreach ($pages as $file) {
            $source = file_get_contents($file);
            $this->assertStringContainsString("partials.pending_requests_body", $source, $file);
            $this->assertStringContainsString("partials.processed_requests_body", $source, $file);
        }
    }
}
