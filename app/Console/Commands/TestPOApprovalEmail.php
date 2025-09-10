<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Events\PurchaseOrderApproved;
use App\Mail\PurchaseOrderApprovedMail;
use Illuminate\Support\Facades\Mail;

class TestPOApprovalEmail extends Command
{
    protected $signature = 'test:po-approval-email {po_id?} {email?}';
    
    protected $description = 'Test PO approval email sending';

    public function handle()
    {
        $poId = $this->argument('po_id');
        $testEmail = $this->argument('email');
        
        // หา PO ที่จะทดสอบ
        if ($poId) {
            $po = PurchaseOrder::find($poId);
        } else {
            // หา PO ที่มี status pending_approval หรือ approved
            $po = PurchaseOrder::whereIn('status', ['pending_approval', 'approved'])
                ->with(['creator'])
                ->first();
        }
        
        if (!$po) {
            $this->error('❌ No PO found. Please create a PO first or specify PO ID.');
            return;
        }
        
        $this->info("🔍 Testing with PO: {$po->po_number} (ID: {$po->id})");
        
        // หาผู้อนุมัติ (ใช้ admin user หรือ user แรก)
        $approver = User::where('email', '!=', null)->first();
        
        if (!$approver) {
            $this->error('❌ No user found for approver');
            return;
        }
        
        $this->info("👤 Approver: {$approver->name} ({$approver->email})");
        
        // Email ที่จะทดสอบส่ง
        $recipientEmail = $testEmail ?: 'panapat.w@apppresso.com';
        
        $this->info("📧 Sending test email to: {$recipientEmail}");
        $this->info("🏢 PO Details:");
        $this->info("   - PO Number: {$po->po_number}");
        $this->info("   - Status: {$po->status}");
        $this->info("   - Total Amount: ฿" . number_format($po->total_amount ?? 0, 2));
        $this->info("   - Vendor: " . ($po->company_name ?? 'N/A'));
        
        try {
            // วิธีที่ 1: ทดสอบส่งโดยตรง
            $this->info("\n🚀 Method 1: Direct email sending...");
            
            Mail::to($recipientEmail)->send(
                new PurchaseOrderApprovedMail($po, $approver)
            );
            
            $this->info("✅ Direct email sent successfully!");
            
            // วิธีที่ 2: ทดสอบผ่าน Event (จะส่งไปหลายคนตาม listener)
            if ($this->confirm('Do you want to test via Event dispatch as well? (Will send to vendor + committee)')) {
                $this->info("\n🎯 Method 2: Event dispatch testing...");
                
                // อัพเดท PO ให้มีข้อมูล vendor email สำหรับทดสอบ
                if (!$po->contact_email && !$po->vendor_contact) {
                    $po->update(['contact_email' => $recipientEmail]);
                    $this->info("📝 Updated PO with test vendor email: {$recipientEmail}");
                }
                
                event(new PurchaseOrderApproved($po, $approver));
                
                $this->info("✅ Event dispatched! Check emails for:");
                $this->info("   - PO Creator: " . ($po->creator?->email ?? 'N/A'));
                $this->info("   - Vendor: " . ($po->contact_email ?? $po->vendor_contact ?? 'N/A'));
                $this->info("   - Committee: " . ($po->inspectionCommittee?->email ?? 'N/A'));
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to send email: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
        }
        
        $this->info("\n📋 Email Test Summary:");
        $this->info("✅ Mail config: " . config('mail.default'));
        $this->info("✅ From address: " . config('mail.from.address'));
        $this->info("✅ Test completed!");
    }
}