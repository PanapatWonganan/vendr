<?php

namespace App\Mail;

use App\Models\PaymentMilestone;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentMilestoneNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public PaymentMilestone $paymentMilestone;
    public User $payer;
    public bool $isPayerCopy;

    /**
     * Create a new message instance.
     */
    public function __construct(PaymentMilestone $paymentMilestone, User $payer, bool $isPayerCopy = false)
    {
        $this->paymentMilestone = $paymentMilestone;
        $this->payer = $payer;
        $this->isPayerCopy = $isPayerCopy;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->isPayerCopy 
            ? 'สำเนา: แจ้งการจ่ายเงินงวดที่ ' . $this->paymentMilestone->milestone_number . ' - PO: ' . $this->paymentMilestone->purchaseOrder->po_number
            : 'แจ้งการจ่ายเงินงวดที่ ' . $this->paymentMilestone->milestone_number . ' - PO: ' . $this->paymentMilestone->purchaseOrder->po_number;

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
            markdown: 'emails.payment-milestone-notification',
            with: [
                'paymentMilestone' => $this->paymentMilestone,
                'payer' => $this->payer,
                'isPayerCopy' => $this->isPayerCopy,
                'purchaseOrder' => $this->paymentMilestone->purchaseOrder,
                'vendor' => $this->paymentMilestone->purchaseOrder->vendor,
                'inspectionCommittee' => $this->paymentMilestone->purchaseOrder->inspectionCommittee,
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