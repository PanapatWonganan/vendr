<?php

namespace App\Filament\Widgets;

use App\Models\VendorScore;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class VendorPerformanceChart extends ChartWidget
{
    protected static ?string $heading = 'การกระจายเกรดผู้ขาย (Vendor Grade Distribution)';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function getDescription(): ?string
    {
        return 'แสดงเปอร์เซ็นต์การกระจายเกรดของผู้ขายตามการประเมินล่าสุด';
    }

    protected function getData(): array
    {
        $companyId = session('company_id');
        
        if (!$companyId) {
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
                'labels' => ['เกรด A (ดีมาก)', 'เกรด B (ดี)', 'เกรด C (พอใช้)', 'เกรด D (ควรปรับปรุง)'],
            ];
        }

        // Get latest scores for each vendor in this company
        $latestScores = VendorScore::selectRaw('vendor_id, MAX(created_at) as latest_created')
            ->where('company_id', $companyId)
            ->whereNotNull('weighted_grade')
            ->groupBy('vendor_id')
            ->get();

        $vendorIds = $latestScores->pluck('vendor_id')->toArray();
        
        if (empty($vendorIds)) {
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
                'labels' => ['เกรด A (ดีมาก)', 'เกรด B (ดี)', 'เกรด C (พอใช้)', 'เกรด D (ควรปรับปรุง)'],
            ];
        }

        // Get the actual latest score records
        $scores = VendorScore::whereIn('vendor_id', $vendorIds)
            ->where('company_id', $companyId)
            ->whereIn('created_at', $latestScores->pluck('latest_created')->toArray())
            ->get();

        // Calculate grade distribution
        $gradeDistribution = $scores->groupBy('weighted_grade')
            ->map(function ($group) {
                return $group->count();
            })->toArray();

        // Ensure all grades are present
        $allGrades = ['A', 'B', 'C', 'D'];
        $gradeCounts = [];
        foreach ($allGrades as $grade) {
            $gradeCounts[] = $gradeDistribution[$grade] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'จำนวนผู้ขาย',
                    'data' => $gradeCounts,
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
            'labels' => ['เกรด A (ดีมาก)', 'เกรด B (ดี)', 'เกรด C (พอใช้)', 'เกรด D (ควรปรับปรุง)'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => new \Filament\Support\RawJs('function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                            return context.label + ": " + context.parsed + " ราย (" + percentage + "%)";
                        }')
                    ]
                ]
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}