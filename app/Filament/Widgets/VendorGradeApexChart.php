<?php

namespace App\Filament\Widgets;

use App\Models\VendorScore;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class VendorGradeApexChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'vendorGradeApexChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'การกระจายเกรดผู้ขาย';

    /**
     * Widget Subheading
     *
     * @var string|null
     */
    protected static ?string $subheading = 'แสดงเปอร์เซ็นต์การกระจายเกรดของผู้ขายที่ได้รับการประเมิน';

    /**
     * Sort
     */
    protected static ?int $sort = 3;

    /**
     * Widget content height
     */
    protected static ?int $contentHeight = 350;

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $companyId = session('company_id') ?: 2; // Default to company ID 2 for testing

        // Get latest scores for each vendor
        $scores = VendorScore::where('company_id', $companyId)
            ->whereNotNull('weighted_grade')
            ->get()
            ->groupBy('vendor_id')
            ->map(function ($vendorScores) {
                return $vendorScores->sortByDesc('created_at')->first();
            });

        if ($scores->isEmpty()) {
            return [
                'chart' => [
                    'type' => 'donut',
                    'height' => 300,
                ],
                'series' => [0, 0, 0, 0],
                'labels' => ['เกรด A (ดีมาก)', 'เกรด B (ดี)', 'เกรด C (พอใช้)', 'เกรด D (ควรปรับปรุง)'],
                'colors' => ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
                'legend' => [
                    'position' => 'bottom',
                    'fontSize' => '14px',
                    'fontFamily' => 'Sarabun, sans-serif',
                ],
                'plotOptions' => [
                    'pie' => [
                        'donut' => [
                            'labels' => [
                                'show' => true,
                                'total' => [
                                    'show' => true,
                                    'label' => 'ทั้งหมด',
                                    'fontSize' => '16px',
                                    'fontWeight' => '600',
                                    'color' => '#374151',
                                    'formatter' => 'function (w) { return "0 ราย" }'
                                ],
                                'value' => [
                                    'fontSize' => '24px',
                                    'fontWeight' => '700',
                                    'color' => '#111827',
                                ],
                                'name' => [
                                    'fontSize' => '14px',
                                    'color' => '#6b7280',
                                ],
                            ],
                        ],
                    ],
                ],
                'tooltip' => [
                    'enabled' => false,
                ],
                'dataLabels' => [
                    'enabled' => true,
                    'formatter' => 'function(val, opts) { return "0%" }',
                ],
            ];
        }

        // Count grades
        $gradeCounts = [
            'A' => $scores->where('weighted_grade', 'A')->count(),
            'B' => $scores->where('weighted_grade', 'B')->count(), 
            'C' => $scores->where('weighted_grade', 'C')->count(),
            'D' => $scores->where('weighted_grade', 'D')->count(),
        ];

        $totalVendors = $scores->count();
        $seriesData = array_values($gradeCounts);

        return [
            'chart' => [
                'type' => 'donut',
                'height' => 300,
            ],
            'series' => $seriesData,
            'labels' => ['เกรด A (ดีมาก)', 'เกรด B (ดี)', 'เกรด C (พอใช้)', 'เกรด D (ควรปรับปรุง)'],
            'colors' => ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
            'legend' => [
                'position' => 'bottom',
                'fontSize' => '14px',
                'fontFamily' => 'Sarabun, sans-serif',
            ],
            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'size' => '70%',
                        'labels' => [
                            'show' => true,
                            'total' => [
                                'show' => true,
                                'label' => 'ทั้งหมด',
                                'fontSize' => '16px',
                                'fontWeight' => '600',
                                'color' => '#374151',
                                'formatter' => "function (w) { return '{$totalVendors} ราย' }"
                            ],
                            'value' => [
                                'fontSize' => '24px',
                                'fontWeight' => '700',
                                'color' => '#111827',
                                'formatter' => 'function (val) { return val }'
                            ],
                            'name' => [
                                'fontSize' => '14px',
                                'color' => '#6b7280',
                            ],
                        ],
                    ],
                ],
            ],
            'tooltip' => [
                'enabled' => true,
                'y' => [
                    'formatter' => "function(val, opts) { 
                        var percent = Math.round((val / {$totalVendors}) * 100);
                        return val + ' ราย (' + percent + '%)';
                    }"
                ],
            ],
            'dataLabels' => [
                'enabled' => true,
                'formatter' => "function(val, opts) { 
                    var count = opts.w.config.series[opts.seriesIndex];
                    var percent = Math.round(val);
                    return count > 0 ? percent + '%' : '';
                }",
                'style' => [
                    'fontSize' => '14px',
                    'fontWeight' => '600',
                    'colors' => ['#ffffff'],
                ],
            ],
            'responsive' => [
                [
                    'breakpoint' => 768,
                    'options' => [
                        'chart' => [
                            'height' => 250,
                        ],
                        'legend' => [
                            'fontSize' => '12px',
                        ],
                    ],
                ],
            ],
        ];
    }
}