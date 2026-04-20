<?php

namespace App\Filament\Widgets;

use App\Models\TermsOfReference;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TorDepartmentChart extends ChartWidget
{
    protected static ?string $heading = 'TOR ตามแผนก';
    protected static ?string $pollingInterval = '60s';
    protected static ?int $sort = 4;

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

        $results = TermsOfReference::query()
            ->join('departments', 'terms_of_references.department_id', '=', 'departments.id')
            ->when($companyId, fn ($q) => $q->where('terms_of_references.company_id', $companyId))
            ->whereNull('terms_of_references.deleted_at')
            ->select(
                'departments.name as department_name',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN terms_of_references.status = \'approved\' THEN 1 ELSE 0 END) as approved'),
                DB::raw('SUM(CASE WHEN terms_of_references.status IN (\'submitted\', \'reviewing\') THEN 1 ELSE 0 END) as pending'),
                DB::raw('SUM(CASE WHEN terms_of_references.status = \'draft\' THEN 1 ELSE 0 END) as draft')
            )
            ->groupBy('departments.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'อนุมัติ',
                    'data' => $results->pluck('approved')->toArray(),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.7)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'รอพิจารณา',
                    'data' => $results->pluck('pending')->toArray(),
                    'backgroundColor' => 'rgba(245, 158, 11, 0.7)',
                    'borderColor' => 'rgb(245, 158, 11)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'ร่าง',
                    'data' => $results->pluck('draft')->toArray(),
                    'backgroundColor' => 'rgba(156, 163, 175, 0.7)',
                    'borderColor' => 'rgb(156, 163, 175)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $results->pluck('department_name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'x' => [
                    'stacked' => true,
                ],
                'y' => [
                    'stacked' => true,
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
