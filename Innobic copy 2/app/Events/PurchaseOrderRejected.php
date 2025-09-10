<?php

namespace App\Events;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $purchaseOrder;
    public $rejector;
    public $rejectionReason;

    /**
     * Create a new event instance.
     */
    public function __construct(PurchaseOrder $purchaseOrder, User $rejector, string $rejectionReason)
    {
        $this->purchaseOrder = $purchaseOrder;
        $this->rejector = $rejector;
        $this->rejectionReason = $rejectionReason;
    }
} 