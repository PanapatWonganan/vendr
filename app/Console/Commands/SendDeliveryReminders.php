<?php

namespace App\Console\Commands;

use App\Mail\DeliveryReminderMail;
use App\Models\PurchaseOrder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDeliveryReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delivery:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send delivery reminder emails for upcoming PO deliveries';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to send delivery reminders...');

        // Get POs that are due in 7, 3, and 1 days
        $reminderDays = [7, 3, 1];

        foreach ($reminderDays as $days) {
            $targetDate = Carbon::now()->addDays($days)->startOfDay();
            $targetDateEnd = Carbon::now()->addDays($days)->endOfDay();

            $purchaseOrders = PurchaseOrder::whereBetween('expected_delivery_date', [$targetDate, $targetDateEnd])
                ->whereIn('status', ['approved', 'in_progress'])
                ->with(['vendor', 'company', 'purchaseRequisition.user'])
                ->get();

            foreach ($purchaseOrders as $po) {
                $this->sendReminders($po, $days);
            }

            $this->info("Sent reminders for POs due in {$days} days: {$purchaseOrders->count()} POs");
        }

        // Check for overdue POs
        $overduePOs = PurchaseOrder::where('expected_delivery_date', '<', Carbon::now()->startOfDay())
            ->whereIn('status', ['approved', 'in_progress'])
            ->with(['vendor', 'company', 'purchaseRequisition.user'])
            ->get();

        foreach ($overduePOs as $po) {
            $daysOverdue = Carbon::now()->diffInDays($po->expected_delivery_date);
            $this->sendReminders($po, -$daysOverdue);
        }

        $this->info("Sent reminders for overdue POs: {$overduePOs->count()} POs");
        $this->info('Delivery reminders sent successfully!');

        return Command::SUCCESS;
    }

    private function sendReminders(PurchaseOrder $po, int $days)
    {
        $recipients = collect();

        // Add PR creator
        if ($po->purchaseRequisition && $po->purchaseRequisition->user) {
            $recipients->push($po->purchaseRequisition->user);
        }

        // Add procurement team - simplified query
        try {
            $procurementUsers = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['procurement', 'super_admin']);
            })->get();
            $recipients = $recipients->merge($procurementUsers);
        } catch (\Exception $e) {
            $this->warn("Could not fetch procurement users: " . $e->getMessage());
        }

        // Remove duplicates
        $recipients = $recipients->unique('id');

        // If no recipients found, try to send to admin
        if ($recipients->isEmpty()) {
            $adminUser = User::where('email', 'admin@innobic.com')->first();
            if ($adminUser) {
                $recipients->push($adminUser);
            }
        }

        foreach ($recipients as $user) {
            try {
                Mail::to($user->email)->send(new DeliveryReminderMail($po, $user, $days, 'purchase_order'));
                $this->info("Reminder sent to: {$user->email} for PO: {$po->po_number}");
            } catch (\Exception $e) {
                $this->error("Failed to send reminder to {$user->email}: " . $e->getMessage());
            }
        }
    }
}