<?php

namespace App\Mail;

use App\Models\TermsOfReference;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TorApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TermsOfReference $tor,
        public User $approver,
        public User $creator,
        public ?string $comments = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "TOR อนุมัติแล้ว: {$this->tor->tor_number} - {$this->tor->title}",
        );
    }

    public function content(): Content
    {
        $torTypeLabels = TermsOfReference::getTorTypeOptions();

        return new Content(
            view: 'emails.tor-approved',
            with: [
                'tor' => $this->tor,
                'approver' => $this->approver,
                'creator' => $this->creator,
                'torTypeLabel' => $torTypeLabels[$this->tor->tor_type] ?? $this->tor->tor_type,
                'comments' => $this->comments,
            ],
        );
    }
}
