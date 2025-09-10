<?php

namespace App\Filament\Widgets;

use App\Models\ValueAnalysis;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ValueAnalysisSavingsChart extends ApexChartWidget
{
    protected static ?string $chartId = 'valueAnalysisSavingsChart';
    protected static ?string $heading = 'ผลการวิเคราะห์มูลค่า (Value Analysis)';
    protected static ?string $subheading = 'สรุปการต่อรองราคาและประหยัดงบประมาณ';
    protected static ?int $sort = 4;
    protected static ?int $contentHeight = 300;

    protected function getOptions(): array
    {
        $companyId = session('company_id') ?: 2;

        // Get Purchase Requisitions for this company to filter VAs
        $prIds = \App\Models\PurchaseRequisition::where('company_id', $companyId)->pluck('id');

        // Get all approved Value Analysis records for this company
        $valueAnalyses = ValueAnalysis::where('status', 'approved')
            ->whereIn('purchase_requisition_id', $prIds)
            ->whereNotNull('total_budget')
            ->whereNotNull('agreed_amount')
            ->where('total_budget', '>', 0)
            ->get();

        if ($valueAnalyses->isEmpty()) {
            return $this->getEmptyChartOptions();
        }

        $totalProjects = $valueAnalyses->count();
        $totalBudget = $valueAnalyses->sum('total_budget');
        $totalAgreed = $valueAnalyses->sum('agreed_amount');
        $totalSavings = $totalBudget - $totalAgreed;
        $savingsPercent = $totalBudget > 0 ? round(($totalSavings / $totalBudget) * 100, 2) : 0;
        $remainingPercent = round(100 - $savingsPercent, 2);

        return [
            'chart' => [
                'type' => 'radialBar',
                'height' => 280,
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'series' => [$savingsPercent],
            'colors' => ['#10b981'],
            'plotOptions' => [
                'radialBar' => [
                    'hollow' => [
                        'size' => '60%',
                    ],
                    'track' => [
                        'background' => '#e5e7eb',
                        'strokeWidth' => '100%',
                        'margin' => 0,
                        'dropShadow' => [
                            'enabled' => false,
                        ]
                    ],
                    'dataLabels' => [
                        'name' => [
                            'show' => true,
                            'fontSize' => '14px',
                            'fontWeight' => '600',
                            'color' => '#6b7280',
                            'offsetY' => -10,
                        ],
                        'value' => [
                            'show' => true,
                            'fontSize' => '36px',
                            'fontWeight' => '700',
                            'color' => '#065f46',
                            'offsetY' => 10,
                            'formatter' => 'function(val) { return Math.round(val) + "%" }'
                        ],
                        'total' => [
                            'show' => true,
                            'label' => 'ประหยัดรวม',
                            'fontSize' => '14px',
                            'color' => '#6b7280',
                        ]
                    ]
                ]
            ],
            'labels' => ['ประหยัดได้'],
            'title' => [
                'text' => 'ผลการประหยัดจาก Value Analysis',
                'align' => 'center',
                'style' => [
                    'fontSize' => '16px',
                    'fontWeight' => '600',
                    'color' => '#065f46',
                ]
            ],
            'subtitle' => [
                'text' => 'ประหยัด ' . number_format($totalSavings / 1000000, 2) . ' ล้านบาท จาก ' . $totalProjects . ' โครงการ',
                'align' => 'center',
                'style' => [
                    'fontSize' => '12px',
                    'color' => '#6b7280',
                ]
            ],
            'tooltip' => [
                'enabled' => true,
                'formatter' => 'function(val) {
                    return "ประหยัด: " + Math.round(val) + "%"
                }'
            ],
            'responsive' => [
                [
                    'breakpoint' => 480,
                    'options' => [
                        'chart' => [
                            'width' => 200
                        ],
                        'legend' => [
                            'position' => 'bottom'
                        ]
                    ]
                ]
            ],
        ];
    }

    private function getEmptyChartOptions(): array
    {
        return [
            'chart' => [
                'type' => 'radialBar',
                'height' => 280,
            ],
            'series' => [0],
            'colors' => ['#d1d5db'],
            'plotOptions' => [
                'radialBar' => [
                    'hollow' => [
                        'size' => '60%',
                    ],
                    'track' => [
                        'background' => '#f3f4f6',
                        'strokeWidth' => '100%',
                    ],
                    'dataLabels' => [
                        'name' => [
                            'show' => true,
                            'fontSize' => '14px',
                            'color' => '#9ca3af',
                        ],
                        'value' => [
                            'show' => true,
                            'fontSize' => '24px',
                            'color' => '#9ca3af',
                            'formatter' => 'function(val) { return "0%" }'
                        ],
                        'total' => [
                            'show' => true,
                            'label' => 'ประหยัดรวม',
                            'fontSize' => '14px',
                            'color' => '#9ca3af',
                        ]
                    ]
                ]
            ],
            'labels' => ['ไม่มีข้อมูล'],
            'title' => [
                'text' => 'ยังไม่มีข้อมูล Value Analysis',
                'align' => 'center',
                'style' => [
                    'fontSize' => '16px',
                    'color' => '#9ca3af',
                ]
            ],
        ];
    }
}