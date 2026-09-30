<?php

namespace Tests\Unit;

use App\Http\Controllers\DGMDashboardController;
use App\Models\PaymentDetail;
use Carbon\Carbon;
use Tests\TestCase;

class DGMDashboardPaymentAggregationTest extends TestCase
{
    public function test_payment_contribution_uses_effective_date_and_json_partial_payments(): void
    {
        $controller = new DGMDashboardController();
        $payment = new PaymentDetail();
        $payment->created_at = Carbon::parse('2024-01-10');
        $payment->payment_effective_date = Carbon::parse('2024-02-15');
        $payment->total_fee = 1000;
        $payment->amount = 1000;
        $payment->partial_payments = json_encode([
            ['amount' => 250, 'date' => '2024-02-10'],
            ['amount' => 750, 'date' => '2024-03-05'],
        ]);

        $method = new \ReflectionMethod(DGMDashboardController::class, 'getPaymentContributionForPeriod');
        $method->setAccessible(true);

        $start = Carbon::parse('2024-02-01')->startOfDay();
        $end = Carbon::parse('2024-02-29')->endOfDay();

        $result = $method->invoke($controller, $payment, $start, $end);

        $this->assertSame(250.0, round($result, 2));
    }

    public function test_year_date_filter_covers_full_calendar_year(): void
    {
        $controller = new DGMDashboardController();
        $method = new \ReflectionMethod(DGMDashboardController::class, 'buildDateFilter');
        $method->setAccessible(true);

        $filter = $method->invoke($controller, 2026);

        $this->assertSame('2026-01-01', $filter['start']->toDateString());
        $this->assertSame('2026-12-31', $filter['end']->toDateString());
        $this->assertTrue($filter['start']->lt($filter['end']));
        $this->assertNotSame($filter['start'], $filter['end']);
    }

    public function test_mid_year_payment_counts_in_yearly_revenue_window(): void
    {
        $controller = new DGMDashboardController();

        $filterMethod = new \ReflectionMethod(DGMDashboardController::class, 'buildDateFilter');
        $filterMethod->setAccessible(true);
        $filter = $filterMethod->invoke($controller, 2026);

        $payment = new PaymentDetail();
        $payment->created_at = Carbon::parse('2026-06-15 10:00:00');
        $payment->payment_effective_date = Carbon::parse('2026-06-15');
        $payment->amount = 5000;
        $payment->partial_payments = null;

        $contribMethod = new \ReflectionMethod(DGMDashboardController::class, 'getPaymentContributionForPeriod');
        $contribMethod->setAccessible(true);

        $result = $contribMethod->invoke($controller, $payment, $filter['start'], $filter['end']);

        $this->assertSame(5000.0, round($result, 2));
    }
}
