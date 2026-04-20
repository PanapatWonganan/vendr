<?php

namespace App\Events;

use App\Models\TermsOfReference;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TorRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $torId;
    public int $rejectorId;
    public string $connectionName;
    public int $companyId;
    public string $reason;

    public function __construct(TermsOfReference $tor, User $rejector, string $reason = '', ?string $connectionName = null)
    {
        $this->torId = $tor->id;
        $this->rejectorId = $rejector->id;
        $this->connectionName = $connectionName ?? $tor->getConnection()->getName();
        $this->companyId = $tor->company_id ?? session('company_id');
        $this->reason = $reason;
    }
}
