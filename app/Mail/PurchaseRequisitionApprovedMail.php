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

class PurchaseRequisitionApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $purchaseRequisition;
    public $approver;
    public $recipient;

    /**
     * Create a new message instance.
     */
    public function __construct(PurchaseRequisition $purchaseRequisition, User $approver, ?User $recipient = null)
    {
        $this->purchaseRequisition = $purchaseRequisition;
        $this->approver = $approver;
        $this->recipient = $recipient;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "การขอซื้อ #{$this->purchaseRequisition->pr_number} ได้รับอนุมัติแล้ว",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-requisition-approved',
            with: [
                'purchaseRequisition' => $this->purchaseRequisition,
                'approver' => $this->approver,
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
