<?php

namespace App\Events;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $purchaseOrder;
    public $rejector;

    /**
     * Create a new event instance.
     */
    public function __construct(PurchaseOrder $purchaseOrder, User $rejector)
    {
        $this->purchaseOrder = $purchaseOrder;
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