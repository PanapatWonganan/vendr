<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $purchaseOrder;
    public $rejector;
    public $recipient;
    public $rejectionReason;

    /**
     * Create a new message instance.
     */
    public function __construct(PurchaseOrder $purchaseOrder, User $rejector, User $recipient, string $rejectionReason)
    {
        $this->purchaseOrder = $purchaseOrder;
        $this->rejector = $rejector;
        $this->recipient = $recipient;
        $this->rejectionReason = $rejectionReason;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Innobic] ใบ PO ' . $this->purchaseOrder->po_number . ' ถูกปฏิเสธ',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-order-rejected',
            with: [
                'purchaseOrder' => $this->purchaseOrder,
                'rejector' => $this->rejector,
                'recipient' => $this->recipient,
                'rejectionReason' => $this->rejectionReason,
            ],
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