<?php

namespace Tests\Unit;

use App\Models\DailyReport;
use PHPUnit\Framework\TestCase;

class DailyReportTest extends TestCase
{
    public function test_total_capital_includes_additional_capital(): void
    {
        $report = new DailyReport([
            'initial_capital' => 100000,
            'additional_capital' => 50000,
        ]);

        $this->assertSame(150000, $report->total_capital);
    }
}
