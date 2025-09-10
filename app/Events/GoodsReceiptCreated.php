<?php

namespace App\Events;

use App\Models\GoodsReceipt;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GoodsReceiptCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $goodsReceiptId;
    public $creatorId;
    public $connectionName;
    public $companyId;

    /**
     * Create a new event instance.
     */
    public function __construct(GoodsReceipt $goodsReceipt, User $creator, ?string $connectionName = null)
    {
        $this->goodsReceiptId = $goodsReceipt->id;
        $this->creatorId = $creator->id;
        
        // Use provided connection name or detect from model
        $this->connectionName = $connectionName ?? $goodsReceipt->getConnection()->getName();
        
        $this->companyId = $goodsReceipt->company_id ?? session('company_id', 1);
    }
}