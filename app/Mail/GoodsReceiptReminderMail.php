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

class GoodsReceiptReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public GoodsReceipt $goodsReceipt;
    public User $creator;
    public int $daysUntilDelivery;

    /**
     * Create a new message instance.
     */
    public function __construct(GoodsReceipt $goodsReceipt, User $creator, int $daysUntilDelivery)
    {
        $this->goodsReceipt = $goodsReceipt;
        $this->creator = $creator;
        $this->daysUntilDelivery = $daysUntilDelivery;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = "🔔 แจ้งเตือน: ใบตรวจรับงาน/วัสดุ (GR) ครบกำหนดในอีก {$this->daysUntilDelivery} วัน - {$this->goodsReceipt->gr_number}";

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
            markdown: 'emails.goods-receipt-reminder',
            with: [
                'goodsReceipt' => $this->goodsReceipt,
                'creator' => $this->creator,
                'daysUntilDelivery' => $this->daysUntilDelivery,
                'purchaseOrder' => $this->goodsReceipt->purchaseOrder,
                'supplier' => $this->goodsReceipt->supplier,
                'inspectionCommittee' => $this->goodsReceipt->inspectionCommittee,
                'expectedDeliveryDate' => $this->goodsReceipt->purchaseOrder?->expected_delivery_date,
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
