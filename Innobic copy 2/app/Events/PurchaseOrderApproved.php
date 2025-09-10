<?php

namespace App\Events;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $purchaseOrderId;
    public $approverId;
    public $connectionName;
    public $companyId;

    /**
     * Create a new event instance.
     */
    public function __construct(PurchaseOrder $purchaseOrder, User $approver, string $connectionName = null)
    {
        $this->purchaseOrderId = $purchaseOrder->id;
        $this->approverId = $approver->id;
        
        // Use provided connection name or detect from model
        $this->connectionName = $connectionName ?? $purchaseOrder->getConnection()->getName();
        
        $this->companyId = $purchaseOrder->company_id ?? session('company_id', 1);
    }
} 