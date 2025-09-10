<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeliveryReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $record;
    public $user;
    public $daysUntilDelivery;
    public $recordType;

    /**
     * Create a new message instance.
     */
    public function __construct($record, User $user, int $daysUntilDelivery, string $recordType)
    {
        $this->record = $record;
        $this->user = $user;
        $this->daysUntilDelivery = $daysUntilDelivery;
        $this->recordType = $recordType;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->recordType === 'purchase_order' 
            ? '[Innobic] แจ้งเตือน: ใบ PO ' . $this->record->po_number . ' จะครบกำหนดส่งใน ' . $this->daysUntilDelivery . ' วัน'
            : '[Innobic] แจ้งเตือน: ใบ PR ' . $this->record->pr_number . ' จะครบกำหนดส่งใน ' . $this->daysUntilDelivery . ' วัน';

        return new Envelope(subject: $subject);
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.delivery-reminder',
            with: [
                'record' => $this->record,
                'user' => $this->user,
                'daysUntilDelivery' => $this->daysUntilDelivery,
                'recordType' => $this->recordType,
                'deliveryDate' => $this->getDeliveryDate(),
                'documentNumber' => $this->getDocumentNumber(),
                'documentTitle' => $this->getDocumentTitle(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }

    private function getDeliveryDate()
    {
        if ($this->recordType === 'purchase_order') {
            return $this->record->expected_delivery_date;
        }
        
        return $this->record->required_date;
    }

    private function getDocumentNumber()
    {
        if ($this->recordType === 'purchase_order') {
            return $this->record->po_number;
        }
        
        return $this->record->pr_number;
    }

    private function getDocumentTitle()
    {
        if ($this->recordType === 'purchase_order') {
            return $this->record->po_title ?? 'ไม่ได้ระบุ';
        }
        
        return $this->record->title ?? 'ไม่ได้ระบุ';
    }
}