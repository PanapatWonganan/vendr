<?php

namespace App\Mail;

use App\Models\GoodsReceipt;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GoodsReceiptNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public GoodsReceipt $goodsReceipt;
    public User $creator;
    public bool $isCreatorCopy;

    /**
     * Create a new message instance.
     */
    public function __construct(GoodsReceipt $goodsReceipt, User $creator, bool $isCreatorCopy = false)
    {
        $this->goodsReceipt = $goodsReceipt;
        $this->creator = $creator;
        $this->isCreatorCopy = $isCreatorCopy;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->isCreatorCopy 
            ? 'สำเนา: แจ้งเตือนใบตรวจรับงาน/วัสดุ (GR) - ' . $this->goodsReceipt->gr_number
            : 'แจ้งเตือนใบตรวจรับงาน/วัสดุ (GR) - ' . $this->goodsReceipt->gr_number;

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.goods-receipt-notification',
            with: [
                'goodsReceipt' => $this->goodsReceipt,
                'creator' => $this->creator,
                'isCreatorCopy' => $this->isCreatorCopy,
                'purchaseOrder' => $this->goodsReceipt->purchaseOrder,
                'supplier' => $this->goodsReceipt->supplier,
                'inspectionCommittee' => $this->goodsReceipt->inspectionCommittee,
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
