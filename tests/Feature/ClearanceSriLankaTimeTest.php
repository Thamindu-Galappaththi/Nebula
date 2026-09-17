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
        $files = [
            resource_path('views/clearance/library_clearance.blade.php'),
            resource_path('views/clearance/hostel_clearance.blade.php'),
            resource_path('views/clearance/project_clearance.blade.php'),
            resource_path('views/clearance/payment_clearance.blade.php'),
        ];

        foreach ($files as $file) {
            $source = file_get_contents($file);
            $this->assertStringContainsString('requestedAtSriLanka()', $source, $file);
            $this->assertStringContainsString('processedAtSriLanka()', $source, $file);
            $this->assertStringNotContainsString('requested_at->format(', $source, $file);
            $this->assertStringNotContainsString('approved_at->format(', $source, $file);
        }
    }
}
