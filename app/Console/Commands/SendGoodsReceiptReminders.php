<?php

namespace App\Console\Commands;

use App\Mail\GoodsReceiptReminderMail;
use App\Models\GoodsReceipt;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendGoodsReceiptReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gr:send-reminders {--days=15 : Number of days before delivery to send reminder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder emails to inspection committee for goods receipts due in specified days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $reminderDays = (int) $this->option('days');
        $reminderDate = Carbon::now()->addDays($reminderDays)->format('Y-m-d');
        
        $this->info("กำลังตรวจสอบ GR ที่ครบกำหนดในอีก {$reminderDays} วัน ({$reminderDate})...");

        // หา GR ที่ยังไม่เสร็จและใกล้ครบกำหนดแล้ว
        $goodsReceipts = GoodsReceipt::with([
            'inspectionCommittee', 
            'createdBy', 
            'purchaseOrder', 
            'supplier'
        ])
        ->whereHas('purchaseOrder', function ($query) use ($reminderDate) {
            $query->whereDate('expected_delivery_date', $reminderDate);
        })
        ->whereIn('status', ['draft', 'pending'])
        ->whereIn('inspection_status', ['pending'])
        ->where(function ($query) {
            // ยังไม่ได้ส่งการแจ้งเตือนวันนี้
            $query->whereNull('reminder_sent_at')
                  ->orWhere('reminder_sent_at', '<', Carbon::now()->startOfDay());
        })
        ->get();

        if ($goodsReceipts->isEmpty()) {
            $this->info('ไม่พบ GR ที่ต้องส่งการแจ้งเตือน');
            return 0;
        }

        $this->info("พบ {$goodsReceipts->count()} รายการที่ต้องส่งการแจ้งเตือน");

        $successCount = 0;
        $errorCount = 0;

        foreach ($goodsReceipts as $gr) {
            try {
                if (!$gr->inspectionCommittee || !$gr->inspectionCommittee->email) {
                    $this->warn("GR {$gr->gr_number}: ไม่มีคณะกรรมการตรวจสอบหรืออีเมล");
                    continue;
                }

                if (!$gr->createdBy) {
                    $this->warn("GR {$gr->gr_number}: ไม่พบข้อมูลผู้สร้าง");
                    continue;
                }

                // ส่งอีเมลแจ้งเตือน
                Mail::to($gr->inspectionCommittee->email)
                    ->send(new GoodsReceiptReminderMail($gr, $gr->createdBy, $reminderDays));

                // อัปเดตเวลาที่ส่งการแจ้งเตือน
                $gr->update(['reminder_sent_at' => Carbon::now()]);

                $this->info("✅ GR {$gr->gr_number}: ส่งการแจ้งเตือนแล้ว ({$gr->inspectionCommittee->email})");
                
                Log::info("GR reminder sent", [
                    'gr_id' => $gr->id,
                    'gr_number' => $gr->gr_number,
                    'recipient' => $gr->inspectionCommittee->email,
                    'days_until_delivery' => $reminderDays,
                    'expected_delivery_date' => $gr->purchaseOrder->expected_delivery_date
                ]);

                $successCount++;
                
            } catch (\Exception $e) {
                $this->error("❌ GR {$gr->gr_number}: เกิดข้อผิดพลาด - " . $e->getMessage());
                
                Log::error("Failed to send GR reminder", [
                    'gr_id' => $gr->id,
                    'gr_number' => $gr->gr_number,
                    'error' => $e->getMessage()
                ]);

                $errorCount++;
            }

            // หน่วงเวลาเล็กน้อยเพื่อไม่ให้ระบบอีเมลโหลดหนัก
            usleep(250000); // 0.25 วินาที
        }

        $this->info("\n=== สรุปผลการส่งการแจ้งเตือน ===");
        $this->info("✅ สำเร็จ: {$successCount} รายการ");
        if ($errorCount > 0) {
            $this->error("❌ ล้มเหลว: {$errorCount} รายการ");
        }

        return 0;
    }
}
