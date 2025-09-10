<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Mail\DeliveryReminderMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDeliveryReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delivery:send-reminders 
                            {--days=15 : Number of days before delivery date to send reminder}
                            {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send delivery reminder emails 15 days before expected delivery date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        
        $this->info("Checking for deliveries due in {$days} days...");
        
        $targetDate = now()->addDays($days)->startOfDay();
        $this->line("Target date: " . $targetDate->format('Y-m-d'));
        
        // Check Purchase Orders
        $pos = PurchaseOrder::whereNotNull('expected_delivery_date')
            ->whereDate('expected_delivery_date', $targetDate->toDateString())
            ->where('status', 'approved')
            ->with(['vendor', 'purchaseRequisition.requester', 'purchaseRequisition.inspectionCommittee'])
            ->get();
            
        $this->info("Found " . $pos->count() . " PO(s) with delivery due in {$days} days");
        
        foreach ($pos as $po) {
            $this->line("- PO: {$po->po_number} (Expected: {$po->expected_delivery_date->format('d/m/Y')})");
            
            if (!$dryRun) {
                $this->sendPOReminder($po, $days);
            }
        }
        
        // Check Purchase Requisitions (for direct purchases)
        $prs = PurchaseRequisition::whereNotNull('required_date')
            ->whereDate('required_date', $targetDate->toDateString())
            ->whereIn('status', ['approved', 'pending'])
            ->whereNull('approved_at') // Not yet converted to PO
            ->with(['requester', 'inspectionCommittee'])
            ->get();
            
        $this->info("Found " . $prs->count() . " PR(s) with required date due in {$days} days");
        
        foreach ($prs as $pr) {
            $this->line("- PR: {$pr->pr_number} (Required: {$pr->required_date->format('d/m/Y')})");
            
            if (!$dryRun) {
                $this->sendPRReminder($pr, $days);
            }
        }
        
        if ($dryRun) {
            $this->warn("This was a dry run. No emails were sent.");
        } else {
            $this->info("Delivery reminders sent successfully!");
        }
        
        return 0;
    }
    
    private function sendPOReminder(PurchaseOrder $po, int $days)
    {
        $recipients = collect();
        
        // Add PO creator/requester
        if ($po->purchaseRequisition && $po->purchaseRequisition->requester) {
            $recipients->push($po->purchaseRequisition->requester);
        }
        
        // Add inspection committee
        if ($po->purchaseRequisition && $po->purchaseRequisition->inspectionCommittee) {
            $recipients->push($po->purchaseRequisition->inspectionCommittee);
        }
        
        // Send to unique recipients
        $recipients->unique('email')->each(function ($user) use ($po, $days) {
            try {
                Mail::to($user->email)->send(
                    new DeliveryReminderMail($po, $user, $days, 'purchase_order')
                );
                $this->line("  → Sent to: {$user->name} ({$user->email})");
            } catch (\Exception $e) {
                $this->error("  → Failed to send to {$user->email}: " . $e->getMessage());
            }
        });
    }
    
    private function sendPRReminder(PurchaseRequisition $pr, int $days)
    {
        $recipients = collect();
        
        // Add PR requester
        if ($pr->requester) {
            $recipients->push($pr->requester);
        }
        
        // Add inspection committee
        if ($pr->inspectionCommittee) {
            $recipients->push($pr->inspectionCommittee);
        }
        
        // Send to unique recipients
        $recipients->unique('email')->each(function ($user) use ($pr, $days) {
            try {
                Mail::to($user->email)->send(
                    new DeliveryReminderMail($pr, $user, $days, 'purchase_requisition')
                );
                $this->line("  → Sent to: {$user->name} ({$user->email})");
            } catch (\Exception $e) {
                $this->error("  → Failed to send to {$user->email}: " . $e->getMessage());
            }
        });
    }
}