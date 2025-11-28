<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use App\Models\VendorEvaluation;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseOrder;
use App\Observers\VendorEvaluationObserver;
use App\Observers\PurchaseRequisitionObserver;
use App\Observers\PurchaseOrderObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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

        // Temporary fix for intl extension issue
        if (!extension_loaded('intl')) {
            // Set locale fallback
            setlocale(LC_ALL, 'en_US.UTF-8');
        }
    }
}
