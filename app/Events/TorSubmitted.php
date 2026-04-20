<?php

namespace App\Events;

use App\Models\TermsOfReference;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TorSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $torId;
    public int $submitterId;
    public string $connectionName;
    public int $companyId;

    public function __construct(TermsOfReference $tor, User $submitter, ?string $connectionName = null)
    {
        $this->torId = $tor->id;
        $this->submitterId = $submitter->id;
        $this->connectionName = $connectionName ?? $tor->getConnection()->getName();
        $this->companyId = $tor->company_id ?? session('company_id');
    }
}
