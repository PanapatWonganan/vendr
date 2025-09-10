<?php

namespace App\Events;

use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseRequisitionRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $purchaseRequisition;
    public $rejector;

    /**
     * Create a new event instance.
     */
    public function __construct(PurchaseRequisition $purchaseRequisition, User $rejector)
    {
        $this->purchaseRequisition = $purchaseRequisition;
        $this->rejector = $rejector;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}