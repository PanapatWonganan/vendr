<?php

namespace App\Events;

use App\Models\TermsOfReference;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TorApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $torId;
    public int $approverId;
    public string $connectionName;
    public int $companyId;

    public function __construct(TermsOfReference $tor, User $approver, ?string $connectionName = null)
    {
        $this->torId = $tor->id;
        $this->approverId = $approver->id;
        $this->connectionName = $connectionName ?? $tor->getConnection()->getName();
        $this->companyId = $tor->company_id ?? session('company_id');
    }
}
