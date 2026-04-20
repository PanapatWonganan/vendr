<?php

namespace App\Mail;

use App\Models\TermsOfReference;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TorRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TermsOfReference $tor,
        public User $rejector,
        public User $creator,
        public string $reason,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "TOR ไม่อนุมัติ: {$this->tor->tor_number} - {$this->tor->title}",
        );
    }

    public function content(): Content
    {
        $torTypeLabels = TermsOfReference::getTorTypeOptions();

        return new Content(
            view: 'emails.tor-rejected',
            with: [
                'tor' => $this->tor,
                'rejector' => $this->rejector,
                'creator' => $this->creator,
                'torTypeLabel' => $torTypeLabels[$this->tor->tor_type] ?? $this->tor->tor_type,
                'reason' => $this->reason,
            ],
        );
    }
}
