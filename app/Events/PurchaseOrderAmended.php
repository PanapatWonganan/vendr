<?php

namespace App\Events;

use App\Models\PoAmendment;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderAmended
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $purchaseOrderId;
    public $approverId;
    public $amendmentId;
    public $connectionName;
    public $companyId;

    /**
     * Create a new event instance.
     */
    public function __construct(PurchaseOrder $purchaseOrder, User $approver, PoAmendment $amendment)
    {
        $this->purchaseOrderId = $purchaseOrder->id;
        $this->approverId = $approver->id;
        $this->amendmentId = $amendment->id;
        $this->connectionName = $purchaseOrder->getConnection()->getName();
        $this->companyId = $purchaseOrder->company_id ?? session('company_id', 1);
    }
}
