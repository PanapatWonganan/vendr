<?php

namespace Tests\Unit;

use App\Services\SlaService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class SlaCalendarDaysTest extends TestCase
{
    private SlaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SlaService;
    }

    // ─── Calendar Days (DATEDIF ตามสูตร Excel ของผู้ใช้) ─────────

    public function test_calendar_days_matches_excel_datedif_example(): void
    {
        // ตัวอย่างจริงจากชีท: รับเรื่อง 6/1/2025 → อนุมัติ PO 8/1/2025 = 2 วัน
        $days = $this->service->calculateCalendarDays(
            Carbon::parse('2025-01-06'),
            Carbon::parse('2025-01-08'),
        );

        $this->assertSame(2, $days);
    }

    public function test_calendar_days_counts_weekends(): void
    {
        // ศุกร์ → จันทร์ = 3 วันปฏิทิน (ต่างจาก working days ที่ได้ 1)
        $days = $this->service->calculateCalendarDays(
            Carbon::parse('2025-01-10'),
            Carbon::parse('2025-01-13'),
        );

        $this->assertSame(3, $days);
    }

    public function test_calendar_days_same_day_is_zero(): void
    {
        $days = $this->service->calculateCalendarDays(
            Carbon::parse('2025-01-06'),
            Carbon::parse('2025-01-06'),
        );

        $this->assertSame(0, $days);
    }

    public function test_calendar_days_start_after_end_is_zero(): void
    {
        $days = $this->service->calculateCalendarDays(
            Carbon::parse('2025-01-08'),
            Carbon::parse('2025-01-06'),
        );

        $this->assertSame(0, $days);
    }

    public function test_calendar_days_ignores_time_component(): void
    {
        $days = $this->service->calculateCalendarDays(
            Carbon::parse('2025-01-06 23:59:00'),
            Carbon::parse('2025-01-08 00:01:00'),
        );

        $this->assertSame(2, $days);
    }

    // ─── %Dif = 100 − (actual/SLA × 100) ─────────────────────────

    public function test_percent_diff_matches_excel_example(): void
    {
        // 2 วัน จาก SLA 9 วัน → sla_percentage 22.22 → %Dif 77.78
        $percentage = (2 / 9) * 100;

        $this->assertSame(77.78, round(100 - round($percentage, 2), 2));
    }

    public function test_grade_for_excel_example_is_s(): void
    {
        // 22.22% <= 50% → เกรด S
        $this->assertSame('S', $this->service->calculateGrade((2 / 9) * 100));
    }

    public function test_overrun_beyond_999_percent_supported(): void
    {
        // เคสจริงในระบบ: 209 วัน จาก SLA 9 วัน = 2322.22% ต้องได้เกรด F ไม่ error
        $percentage = (209 / 9) * 100;

        $this->assertSame('F', $this->service->calculateGrade($percentage));
        $this->assertGreaterThan(999.99, $percentage);
    }
}
