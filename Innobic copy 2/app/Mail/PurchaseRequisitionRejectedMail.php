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

class PurchaseRequisitionRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $purchaseRequisition;
    public $rejectedBy;
    public $rejectionReason;

    /**
     * Create a new message instance.
     */
    public function __construct(PurchaseRequisition $purchaseRequisition, User $rejectedBy, string $rejectionReason)
    {
        $this->purchaseRequisition = $purchaseRequisition;
        $this->rejectedBy = $rejectedBy;
        $this->rejectionReason = $rejectionReason;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "การขอซื้อ #{$this->purchaseRequisition->pr_number} ถูกปฏิเสธ",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-requisition-rejected',
            with: [
                'purchaseRequisition' => $this->purchaseRequisition,
                'rejectedBy' => $this->rejectedBy,
                'rejectionReason' => $this->rejectionReason,
                'requester' => $this->purchaseRequisition->requester,
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
