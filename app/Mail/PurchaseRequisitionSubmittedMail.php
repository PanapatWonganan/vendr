<?php

namespace App\Mail;

use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseRequisitionSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $purchaseRequisition;
    public $submittedBy;
    public $recipient;

    /**
     * Create a new message instance.
     */
    public function __construct(PurchaseRequisition $purchaseRequisition, User $submittedBy, User $recipient)
    {
        $this->purchaseRequisition = $purchaseRequisition;
        $this->submittedBy = $submittedBy;
        $this->recipient = $recipient;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "ใบขอซื้อ {$this->purchaseRequisition->pr_number} ได้ถูกส่งขออนุมัติ - Innobic System",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-requisition-submitted',
            with: [
                'purchaseRequisition' => $this->purchaseRequisition,
                'submittedBy' => $this->submittedBy,
                'recipient' => $this->recipient,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
} 