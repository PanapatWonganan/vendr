<?php

namespace App\Mail;

use App\Models\TermsOfReference;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TorSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TermsOfReference $tor,
        public User $submitter,
        public User $recipient,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "TOR รอพิจารณา: {$this->tor->tor_number} - {$this->tor->title}",
        );
    }

    public function content(): Content
    {
        $torTypeLabels = TermsOfReference::getTorTypeOptions();
        $priorityLabels = TermsOfReference::getPriorityOptions();

        return new Content(
            view: 'emails.tor-submitted',
            with: [
                'tor' => $this->tor,
                'submitter' => $this->submitter,
                'recipient' => $this->recipient,
                'torTypeLabel' => $torTypeLabels[$this->tor->tor_type] ?? $this->tor->tor_type,
                'priorityLabel' => $priorityLabels[$this->tor->priority] ?? $this->tor->priority,
            ],
        );
    }
}
