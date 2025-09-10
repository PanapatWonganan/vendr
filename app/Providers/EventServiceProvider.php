<?php

namespace App\Providers;

use App\Events\GoodsReceiptCreated;
use App\Events\PurchaseOrderApproved;
use App\Events\PurchaseOrderRejected;
use App\Events\PurchaseRequisitionApproved;
use App\Events\PurchaseRequisitionRejected;
use App\Events\PurchaseRequisitionSubmitted;
use App\Listeners\SendGoodsReceiptNotification;
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
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Manual event registration using closures to prevent duplicates
        Event::listen(PurchaseRequisitionApproved::class, function ($event) {
            $listener = app(SendPurchaseRequisitionApprovedNotification::class);
            dispatch(function () use ($event, $listener) {
                $listener->handle($event);
            });
        });
        
        Event::listen(PurchaseRequisitionSubmitted::class, function ($event) {
            $listener = app(SendPurchaseRequisitionSubmittedNotification::class);
            dispatch(function () use ($event, $listener) {
                $listener->handle($event);
            });
        });
        
        Event::listen(PurchaseRequisitionRejected::class, function ($event) {
            $listener = app(SendPurchaseRequisitionRejectedNotification::class);
            dispatch(function () use ($event, $listener) {
                $listener->handle($event);
            });
        });
        
        Event::listen(PurchaseOrderApproved::class, function ($event) {
            $listener = app(SendPurchaseOrderApprovedNotification::class);
            dispatch(function () use ($event, $listener) {
                $listener->handle($event);
            });
        });
        
        Event::listen(PurchaseOrderRejected::class, function ($event) {
            $listener = app(SendPurchaseOrderRejectedNotification::class);
            dispatch(function () use ($event, $listener) {
                $listener->handle($event);
            });
        });
        
        Event::listen(GoodsReceiptCreated::class, function ($event) {
            $listener = app(SendGoodsReceiptNotification::class);
            dispatch(function () use ($event, $listener) {
                $listener->handle($event);
            });
        });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
} 