<?php

namespace App\Filament\Widgets;

use App\Models\VendorScore;
use Filament\Support\RawJs;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class VendorGradeApexChart extends ApexChartWidget
{
    use InteractsWithPageFilters;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'procurement_officer', 'procurement_manager', 'auditor']) ?? false;
    }

    /**
     * Chart Id
     */
    protected static ?string $chartId = 'vendorGradeApexChart';

    /**
     * Widget Title
     */
    protected static ?string $heading = 'การกระจายเกรดผู้ขาย';

    /**
     * Widget Subheading
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
     */
    protected function getOptions(): array
    {
        $companyId = session('company_id') ?: 2; // Default to company ID 2 for testing
        $year = $this->filters['year'] ?? (int) date('Y');

        // Get latest scores for each vendor (กรองตามปีประเมินจากคอลัมน์ year โดยตรง)
        $scores = VendorScore::where('company_id', $companyId)
            ->whereNotNull('weighted_grade')
            ->when($year, fn ($q) => $q->where('year', $year))
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
                'enabled' => true,
            ],
            'dataLabels' => [
                'enabled' => true,
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

    /**
     * JS formatters ต้องส่งผ่าน RawJs เท่านั้น — ใส่เป็น string ใน getOptions()
     * จะถูก JSON.parse เป็น string ธรรมดา ทำให้ ApexCharts โยน TypeError
     * แล้ววาด slice ไม่ครบ (บั๊กกราฟโชว์สีเดียว 2026-07-20)
     */
    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
        {
            plotOptions: {
                pie: {
                    donut: {
                        labels: {
                            total: {
                                formatter: (w) => ((w && w.globals && w.globals.seriesTotals) || [])
                                    .reduce((a, b) => a + b, 0) + ' ราย',
                            },
                        },
                    },
                },
            },
            tooltip: {
                y: {
                    formatter: (val, opts) => {
                        const totals = opts && opts.w && opts.w.globals ? opts.w.globals.seriesTotals : null;
                        if (!totals) {
                            return val + ' ราย';
                        }
                        const total = totals.reduce((a, b) => a + b, 0);
                        const percent = total > 0 ? Math.round((val / total) * 100) : 0;
                        return val + ' ราย (' + percent + '%)';
                    },
                },
            },
            dataLabels: {
                formatter: (val, opts) => {
                    const series = opts && opts.w && opts.w.config ? opts.w.config.series : null;
                    const count = series ? series[opts.seriesIndex] : val;
                    return count > 0 ? Math.round(val) + '%' : '';
                },
            },
        }
        JS);
    }
}
