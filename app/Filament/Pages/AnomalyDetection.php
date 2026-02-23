<?php

namespace App\Filament\Pages;

use App\Models\ProcurementAnomaly;
use App\Services\AnomalyDetectionService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Log;

class AnomalyDetection extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static ?string $navigationLabel = 'Anomaly Detection';
    protected static ?string $navigationGroup = 'Reports & Analytics';
    protected static ?int $navigationSort = 6;
    protected static ?string $title = 'Anomaly Detection & Budget Alert';
    protected static ?string $slug = 'anomaly-detection';
    protected static string $view = 'filament.pages.anomaly-detection';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'auditor']) ?? false;
    }

    public array $summary = [];
    public array $anomalies = [];
    public string $filterType = 'all';
    public string $filterSeverity = 'all';
    public string $filterStatus = 'unresolved';
    public bool $isScanning = false;

    public function mount(): void
    {
        $this->loadData();
    }

    protected function loadData(): void
    {
        $companyId = session('company_id');
        if (!$companyId) return;

        $service = app(AnomalyDetectionService::class);
        $this->summary = $service->getSummary($companyId);

        $query = ProcurementAnomaly::where('company_id', $companyId)
            ->with(['department', 'vendor', 'resolvedByUser'])
            ->orderByRaw("FIELD(severity, 'critical', 'warning', 'info')")
            ->orderBy('created_at', 'desc');

        if ($this->filterType !== 'all') {
            $query->where('type', $this->filterType);
        }

        if ($this->filterSeverity !== 'all') {
            $query->where('severity', $this->filterSeverity);
        }

        if ($this->filterStatus === 'unresolved') {
            $query->unresolved();
        } elseif ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        $this->anomalies = $query->limit(50)->get()->toArray();
    }

    /**
     * Run a scan now
     */
    public function runScan(): void
    {
        $this->isScanning = true;

        try {
            $companyId = session('company_id');
            $service = app(AnomalyDetectionService::class);
            $newAnomalies = $service->scan($companyId);

            $count = count($newAnomalies);

            if ($count > 0) {
                Notification::make()
                    ->title("พบความผิดปกติใหม่ {$count} รายการ")
                    ->body('กรุณาตรวจสอบรายละเอียดด้านล่าง')
                    ->warning()
                    ->send();
            } else {
                Notification::make()
                    ->title('ไม่พบความผิดปกติใหม่')
                    ->body('ระบบตรวจสอบเสร็จแล้ว ทุกอย่างปกติ')
                    ->success()
                    ->send();
            }

            $this->loadData();
        } catch (\Exception $e) {
            Log::error("Anomaly scan error: {$e->getMessage()}");
            Notification::make()
                ->title('เกิดข้อผิดพลาดในการสแกน')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->isScanning = false;
    }

    /**
     * Acknowledge an anomaly
     */
    public function acknowledgeAnomaly(int $id): void
    {
        $anomaly = ProcurementAnomaly::find($id);
        if ($anomaly) {
            $anomaly->acknowledge();
            $this->loadData();
            Notification::make()->title('รับทราบแล้ว')->success()->send();
        }
    }

    /**
     * Dismiss an anomaly
     */
    public function dismissAnomaly(int $id): void
    {
        $anomaly = ProcurementAnomaly::find($id);
        if ($anomaly) {
            $anomaly->dismiss(auth()->id(), 'Dismissed from dashboard');
            $this->loadData();
            Notification::make()->title('ยกเลิกรายการแล้ว')->success()->send();
        }
    }

    /**
     * Resolve an anomaly
     */
    public function resolveAnomaly(int $id): void
    {
        $anomaly = ProcurementAnomaly::find($id);
        if ($anomaly) {
            $anomaly->resolve(auth()->id(), 'Resolved from dashboard');
            $this->loadData();
            Notification::make()->title('แก้ไขแล้ว')->success()->send();
        }
    }

    /**
     * Update filters
     */
    public function updatedFilterType(): void
    {
        $this->loadData();
    }

    public function updatedFilterSeverity(): void
    {
        $this->loadData();
    }

    public function updatedFilterStatus(): void
    {
        $this->loadData();
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public static function getNavigationBadge(): ?string
    {
        $companyId = session('company_id');
        if (!$companyId) return null;

        $count = ProcurementAnomaly::where('company_id', $companyId)
            ->open()
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $companyId = session('company_id');
        if (!$companyId) return null;

        $hasCritical = ProcurementAnomaly::where('company_id', $companyId)
            ->open()
            ->critical()
            ->exists();

        return $hasCritical ? 'danger' : 'warning';
    }
}
