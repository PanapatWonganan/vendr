<?php

namespace App\Mail;

use App\Models\GoodsReceipt;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GoodsReceiptCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $goodsReceipt;
    public $creator;
    public $inspectionCommittee;

    /**
     * Create a new message instance.
     */
    public function __construct(
        GoodsReceipt $goodsReceipt, 
        User $creator, 
        ?User $inspectionCommittee = null
    ) {
        $this->goodsReceipt = $goodsReceipt;
        $this->creator = $creator;
        $this->inspectionCommittee = $inspectionCommittee;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Innobic] ใบตรวจรับงาน/วัสดุ ' . $this->goodsReceipt->gr_number . ' ได้ถูกสร้างแล้ว',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.goods-receipt-created',
            with: [
                'goodsReceipt' => $this->goodsReceipt,
                'creator' => $this->creator,
                'inspectionCommittee' => $this->inspectionCommittee,
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