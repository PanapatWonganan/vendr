<?php

namespace App\Providers;

use App\Events\PurchaseOrderApproved;
use App\Events\PurchaseOrderRejected;
use App\Events\PurchaseRequisitionApproved;
use App\Events\PurchaseRequisitionRejected;
use App\Events\PurchaseRequisitionSubmitted;
use App\Listeners\SendPurchaseOrderApprovedNotification;
use App\Listeners\SendPurchaseOrderRejectedNotification;
use App\Listeners\SendPurchaseRequisitionApprovedNotification;
use App\Listeners\SendPurchaseRequisitionRejectedNotification;
use App\Listeners\SendPurchaseRequisitionSubmittedNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        
        // Purchase Order Events
        PurchaseOrderApproved::class => [
            SendPurchaseOrderApprovedNotification::class,
        ],
        
        PurchaseOrderRejected::class => [
            SendPurchaseOrderRejectedNotification::class,
        ],
        
        // Purchase Requisition Events
        PurchaseRequisitionSubmitted::class => [
            SendPurchaseRequisitionSubmittedNotification::class,
        ],
        
        PurchaseRequisitionApproved::class => [
            SendPurchaseRequisitionApprovedNotification::class,
        ],
        
        PurchaseRequisitionRejected::class => [
            SendPurchaseRequisitionRejectedNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
} 