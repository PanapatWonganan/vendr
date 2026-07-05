<?php

namespace App\Providers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\VendorEvaluation;
use App\Observers\PurchaseOrderObserver;
use App\Observers\PurchaseRequisitionObserver;
use App\Observers\VendorEvaluationObserver;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Infolists\Infolist;
use Filament\Tables\Table;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind custom LoginResponse for Filament
        $this->app->bind(
            \Filament\Http\Responses\Auth\Contracts\LoginResponse::class,
            \App\Http\Responses\LoginResponse::class
        );

        // Bind custom LogoutResponse for Filament
        $this->app->bind(
            \Filament\Http\Responses\Auth\Contracts\LogoutResponse::class,
            \App\Http\Responses\LogoutResponse::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ใช้ Bootstrap 5 สำหรับ pagination
        Paginator::useBootstrap();

        // Set default string length for MySQL
        Schema::defaultStringLength(191);

        // Register observers
        VendorEvaluation::observe(VendorEvaluationObserver::class);
        PurchaseRequisition::observe(PurchaseRequisitionObserver::class);
        PurchaseOrder::observe(PurchaseOrderObserver::class);

        // แสดงวันที่แบบ วัน/เดือน/ปี (d/m/Y) ทั่วทั้งระบบ — ตั้งครั้งเดียวคุมทุกที่
        // ครอบคลุมทั้งของเดิมและที่สร้างใหม่ (form date pickers, table columns, infolist entries)
        // field ที่กำหนด displayFormat/date() เองไว้แล้วจะ override ค่านี้ตามปกติ
        //
        // หมายเหตุ: DatePicker extends DateTimePicker ใน Filament ดังนั้น callback ของ
        // DateTimePicker จะทำงานกับ DatePicker ด้วย — จึงต้องเช็ค instanceof เพื่อไม่ให้
        // DatePicker (ไม่มีเวลา) ถูก override เป็นรูปแบบที่มีเวลา
        DateTimePicker::configureUsing(function (DateTimePicker $component): void {
            $component->native(false);

            if ($component instanceof DatePicker) {
                $component->displayFormat('d/m/Y');
            } else {
                $component->displayFormat('d/m/Y H:i');
            }
        });

        Table::$defaultDateDisplayFormat = 'd/m/Y';
        Table::$defaultDateTimeDisplayFormat = 'd/m/Y H:i';

        Infolist::$defaultDateDisplayFormat = 'd/m/Y';
        Infolist::$defaultDateTimeDisplayFormat = 'd/m/Y H:i';

        // Temporary fix for intl extension issue
        if (! extension_loaded('intl')) {
            // Set locale fallback
            setlocale(LC_ALL, 'en_US.UTF-8');
        }
    }
}
