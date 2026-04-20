<?php

namespace App\Providers;

use App\Events\GoodsReceiptCreated;
use App\Events\PaymentMilestonePaid;
use App\Events\PurchaseOrderAmended;
use App\Events\PurchaseOrderApproved;
use App\Events\PurchaseOrderRejected;
use App\Events\PurchaseRequisitionApproved;
use App\Events\PurchaseRequisitionRejected;
use App\Events\PurchaseRequisitionSubmitted;
use App\Events\TorSubmitted;
use App\Events\TorApproved;
use App\Events\TorRejected;
use App\Listeners\SendGoodsReceiptNotification;
use App\Listeners\SendPaymentMilestoneNotification;
use App\Listeners\SendPurchaseOrderAmendedNotification;
use App\Listeners\SendPurchaseOrderApprovedNotification;
use App\Listeners\SendPurchaseOrderRejectedNotification;
use App\Listeners\SendPurchaseRequisitionApprovedNotification;
use App\Listeners\SendPurchaseRequisitionRejectedNotification;
use App\Listeners\SendPurchaseRequisitionSubmittedNotification;
use App\Listeners\SendTorSubmittedNotification;
use App\Listeners\SendTorApprovedNotification;
use App\Listeners\SendTorRejectedNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * Listeners ที่ implement ShouldQueue จะถูก queue อัตโนมัติโดย Laravel
     * ไม่ต้องใช้ dispatch(closure) ครอบอีกรอบ
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
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

        // Purchase Order Events
        PurchaseOrderApproved::class => [
            SendPurchaseOrderApprovedNotification::class,
        ],
        PurchaseOrderRejected::class => [
            SendPurchaseOrderRejectedNotification::class,
        ],
        PurchaseOrderAmended::class => [
            SendPurchaseOrderAmendedNotification::class,
        ],

        // Goods Receipt Events
        GoodsReceiptCreated::class => [
            SendGoodsReceiptNotification::class,
        ],

        // Payment Milestone Events
        PaymentMilestonePaid::class => [
            SendPaymentMilestoneNotification::class,
        ],

        // TOR Events
        TorSubmitted::class => [
            SendTorSubmittedNotification::class,
        ],
        TorApproved::class => [
            SendTorApprovedNotification::class,
        ],
        TorRejected::class => [
            SendTorRejectedNotification::class,
        ],
    ];

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
} 