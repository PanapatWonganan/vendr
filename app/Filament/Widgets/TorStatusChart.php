<?php

namespace App\Filament\Widgets;

use App\Models\TermsOfReference;
use Filament\Widgets\ChartWidget;

class TorStatusChart extends ChartWidget
{
    protected static ?string $heading = 'TOR ตามสถานะ';
    protected static ?string $pollingInterval = '60s';
    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole([
            'admin', 'department_head', 'procurement_officer',
            'procurement_manager', 'auditor',
        ]) ?? false;
    }

    protected function getData(): array
    {
        $companyId = session('company_id');

        $query = TermsOfReference::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId));

        $statuses = [
            'draft' => ['label' => 'ร่าง', 'color' => 'rgb(156, 163, 175)'],
            'submitted' => ['label' => 'ส่งพิจารณา', 'color' => 'rgb(59, 130, 246)'],
            'reviewing' => ['label' => 'กำลังพิจารณา', 'color' => 'rgb(245, 158, 11)'],
            'approved' => ['label' => 'อนุมัติ', 'color' => 'rgb(34, 197, 94)'],
            'rejected' => ['label' => 'ไม่อนุมัติ', 'color' => 'rgb(239, 68, 68)'],
            'amended' => ['label' => 'แก้ไข', 'color' => 'rgb(168, 162, 158)'],
            'cancelled' => ['label' => 'ยกเลิก', 'color' => 'rgb(120, 113, 108)'],
        ];

        $data = [];
        $labels = [];
        $colors = [];

        foreach ($statuses as $status => $info) {
            $count = (clone $query)->where('status', $status)->count();
            if ($count > 0) {
                $data[] = $count;
                $labels[] = $info['label'] . " ({$count})";
                $colors[] = $info['color'];
            }
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labels,
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
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 15,
                    ],
                ],
            ],
            'cutout' => '60%',
        ];
    }
}
