<?php

namespace App\Events;

use App\Models\PaymentMilestone;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentMilestonePaid
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $paymentMilestoneId;
    public $payerUserId;
    public $connectionName;
    public $companyId;

    /**
     * Create a new event instance.
     */
    public function __construct(PaymentMilestone $paymentMilestone, User $payer, ?string $connectionName = null)
    {
        $this->paymentMilestoneId = $paymentMilestone->id;
        $this->payerUserId = $payer->id;
        
        // Use provided connection name or detect from model
        $this->connectionName = $connectionName ?? $paymentMilestone->getConnection()->getName();
        
        $this->companyId = $paymentMilestone->company_id ?? session('company_id', 1);
    }
}