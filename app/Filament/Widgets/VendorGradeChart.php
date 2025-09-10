<?php

namespace App\Filament\Widgets;

use App\Models\VendorScore;
use Filament\Widgets\ChartWidget;

class VendorGradeChart extends ChartWidget
{
    protected static ?string $heading = 'การกระจายเกรดผู้ขาย';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function getDescription(): ?string
    {
        return 'แสดงเปอร์เซ็นต์การกระจายเกรดของผู้ขายที่ได้รับการประเมิน';
    }

    protected function getData(): array
    {
        $companyId = session('company_id') ?: 2; // Default to company ID 2 for testing
        
        if (!$companyId) {
            return $this->getEmptyData();
        }

        // Get latest scores for each vendor
        $scores = VendorScore::where('company_id', $companyId)
            ->whereNotNull('weighted_grade')
            ->get()
            ->groupBy('vendor_id')
            ->map(function ($vendorScores) {
                return $vendorScores->sortByDesc('created_at')->first();
            });

        if ($scores->isEmpty()) {
            return $this->getEmptyData();
        }

        // Count grades
        $gradeCounts = [
            'A' => $scores->where('weighted_grade', 'A')->count(),
            'B' => $scores->where('weighted_grade', 'B')->count(), 
            'C' => $scores->where('weighted_grade', 'C')->count(),
            'D' => $scores->where('weighted_grade', 'D')->count(),
        ];

        return [
            'datasets' => [
                [
                    'label' => 'จำนวนผู้ขาย',
                    'data' => array_values($gradeCounts),
                    'backgroundColor' => [
                        '#10b981', // Green for A
                        '#3b82f6', // Blue for B
                        '#f59e0b', // Yellow for C
                        '#ef4444', // Red for D
                    ],
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => [
                'เกรด A (ดีมาก)',
                'เกรด B (ดี)',
                'เกรด C (พอใช้)',
                'เกรด D (ควรปรับปรุง)'
            ],
        ];
    }

    protected function getEmptyData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'จำนวนผู้ขาย',
                    'data' => [0, 0, 0, 0],
                    'backgroundColor' => [
                        '#10b981', // Green for A
                        '#3b82f6', // Blue for B
                        '#f59e0b', // Yellow for C
                        '#ef4444', // Red for D
                    ],
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => [
                'เกรด A (ดีมาก)',
                'เกรด B (ดี)',
                'เกรด C (พอใช้)',
                'เกรด D (ควรปรับปรุง)'
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}