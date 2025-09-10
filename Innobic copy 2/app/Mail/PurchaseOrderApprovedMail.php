<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $purchaseOrder;
    public $approver;
    public $recipient;
    private $pdfContent;
    private $pdfFilename;
    private $deliveryNotePdfContent;
    private $deliveryNotePdfFilename;

    /**
     * Create a new message instance.
     */
    public function __construct(
        PurchaseOrder $purchaseOrder, 
        User $approver, 
        User $recipient = null, 
        $pdfContent = null, 
        $pdfFilename = null,
        $deliveryNotePdfContent = null,
        $deliveryNotePdfFilename = null
    ) {
        $this->purchaseOrder = $purchaseOrder;
        $this->approver = $approver;
        $this->recipient = $recipient;
        $this->pdfContent = $pdfContent;
        $this->pdfFilename = $pdfFilename;
        $this->deliveryNotePdfContent = $deliveryNotePdfContent;
        $this->deliveryNotePdfFilename = $deliveryNotePdfFilename;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Innobic] ใบ PO ' . $this->purchaseOrder->po_number . ' ได้รับการอนุมัติแล้ว',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-order-approved',
            with: [
                'purchaseOrder' => $this->purchaseOrder,
                'approver' => $this->approver,
                'recipient' => $this->recipient,
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
        $attachments = [];
        
        // Attach SOW PDF if available
        if ($this->pdfContent && $this->pdfFilename) {
            $attachments[] = Attachment::fromData(
                fn () => $this->pdfContent,
                $this->pdfFilename
            )->withMime('application/pdf');
        }
        
        // Attach Delivery Note PDF if available
        if ($this->deliveryNotePdfContent && $this->deliveryNotePdfFilename) {
            $attachments[] = Attachment::fromData(
                fn () => $this->deliveryNotePdfContent,
                $this->deliveryNotePdfFilename
            )->withMime('application/pdf');
        }
        
        return $attachments;
    }
} 