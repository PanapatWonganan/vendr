<?php

namespace App\Services;

use App\Models\Company;
use App\Models\ContractApproval;
use App\Models\Department;
use App\Models\GoodsReceipt;
use App\Models\PaymentMilestone;
use App\Models\ProcurementAnomaly;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\SlaTracking;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorAssessment;
use App\Models\VendorScore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TelegramBotService
{
    protected string $token;
    protected string $apiUrl;

    public function __construct()
    {
        $this->token = config('telegram.bot_token');
        $this->apiUrl = "https://api.telegram.org/bot{$this->token}";
    }

    // ==========================================
    // Core API Methods
    // ==========================================

    /**
     * Register bot commands with Telegram (command menu)
     */
    public function registerCommands(): array
    {
        $commands = [
            ['command' => 'start', 'description' => 'เริ่มต้นใช้งาน'],
            ['command' => 'register', 'description' => 'เชื่อมต่อ Telegram กับ VENDR'],
            ['command' => 'verify', 'description' => 'ยืนยันรหัส OTP'],
            ['command' => 'me', 'description' => 'ดูข้อมูลบัญชี'],
            ['command' => 'newpr', 'description' => 'สร้างใบ PR ใหม่'],
            ['command' => 'mypr', 'description' => 'ดูใบ PR ของฉัน'],
            ['command' => 'status', 'description' => 'ดูใบ PR รออนุมัติ'],
            ['command' => 'allpr', 'description' => 'ดู PR ทั้งหมด'],
            ['command' => 'searchpr', 'description' => 'ค้นหา PR'],
            ['command' => 'vendor', 'description' => 'ค้นหา Vendor'],
            ['command' => 'pipeline', 'description' => 'Procurement Pipeline'],
            ['command' => 'po', 'description' => 'จัดการ Purchase Order'],
            ['command' => 'gr', 'description' => 'ตรวจรับงาน Goods Receipt'],
            ['command' => 'contract', 'description' => 'จัดการสัญญา'],
            ['command' => 'payment', 'description' => 'ติดตามงวดชำระเงิน'],
            ['command' => 'calendar', 'description' => 'ปฏิทินส่งมอบ & Milestone'],
            ['command' => 'dashboard', 'description' => 'สรุปภาพรวมระบบ'],
            ['command' => 'spending', 'description' => 'วิเคราะห์ค่าใช้จ่าย'],
            ['command' => 'overdue', 'description' => 'รายการเกินกำหนด'],
            ['command' => 'team', 'description' => 'สรุปงานทีมจัดซื้อ'],
            ['command' => 'anomaly', 'description' => 'ตรวจจับความผิดปกติ'],
            ['command' => 'vendorscore', 'description' => 'คะแนน Vendor'],
            ['command' => 'sla', 'description' => 'SLA Performance Report'],
            ['command' => 'report', 'description' => 'สร้างรายงานด่วน'],
            ['command' => 'weeklydigest', 'description' => 'สรุปงานประจำสัปดาห์'],
            ['command' => 'ask', 'description' => 'AI ถามเรื่องจัดซื้อ'],
            ['command' => 'notify', 'description' => 'ตั้งค่าการแจ้งเตือน'],
            ['command' => 'help', 'description' => 'แสดงคำสั่งทั้งหมด'],
        ];

        return $this->apiRequest('setMyCommands', ['commands' => json_encode($commands)]);
    }

    public function sendMessage(string $chatId, string $text, ?array $replyMarkup = null): array
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        if ($replyMarkup) {
            $params['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->apiRequest('sendMessage', $params);
    }

    public function editMessage(string $chatId, int $messageId, string $text, ?array $replyMarkup = null): array
    {
        $params = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        if ($replyMarkup) {
            $params['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->apiRequest('editMessageText', $params);
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text = ''): array
    {
        return $this->apiRequest('answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
        ]);
    }

    protected function apiRequest(string $method, array $params): array
    {
        try {
            $response = Http::post("{$this->apiUrl}/{$method}", $params);
            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error("Telegram API error: {$e->getMessage()}");
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    // Handle Incoming Updates
    // ==========================================

    public function handleUpdate(array $update): void
    {
        if (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
            return;
        }

        if (isset($update['message']['text'])) {
            $this->handleMessage($update['message']);
        }
    }

    protected function handleMessage(array $message): void
    {
        $chatId = (string) $message['chat']['id'];
        $text = trim($message['text']);
        $username = $message['from']['username'] ?? null;

        // Check if user is in a conversation flow
        $flow = Cache::get("tg_flow:{$chatId}");
        if ($flow) {
            $this->handleFlowInput($chatId, $text, $flow);
            return;
        }

        // Parse commands
        if (str_starts_with($text, '/')) {
            $parts = explode(' ', $text, 2);
            $command = strtolower($parts[0]);
            $args = $parts[1] ?? '';

            match ($command) {
                '/start' => $this->handleStart($chatId, $username),
                '/register' => $this->handleRegister($chatId, $args, $username),
                '/verify' => $this->handleVerify($chatId, $args),
                '/me' => $this->handleMe($chatId),
                '/newpr' => $this->handleNewPR($chatId),
                '/mypr' => $this->handleMyPR($chatId),
                '/status' => $this->handlePendingApprovals($chatId),
                '/allpr' => $this->handleAllPR($chatId),
                '/searchpr' => $this->handleSearchPR($chatId, $args),
                '/vendor' => $this->handleVendorSearch($chatId, $args),
                '/pipeline' => $this->handlePipeline($chatId),
                '/dashboard' => $this->handleDashboard($chatId),
                '/spending' => $this->handleSpending($chatId),
                '/overdue' => $this->handleOverdue($chatId),
                '/team' => $this->handleTeam($chatId),
                '/anomaly' => $this->handleAnomaly($chatId),
                '/po' => $this->handlePO($chatId, $args),
                '/gr' => $this->handleGR($chatId, $args),
                '/contract' => $this->handleContract($chatId, $args),
                '/payment' => $this->handlePayment($chatId, $args),
                '/calendar' => $this->handleCalendar($chatId, $args),
                '/vendorscore' => $this->handleVendorScore($chatId, $args),
                '/sla' => $this->handleSla($chatId),
                '/report' => $this->handleReport($chatId, $args),
                '/ask' => $this->handleAsk($chatId, $args),
                '/notify' => $this->handleNotify($chatId, $args),
                '/weeklydigest' => $this->handleWeeklyDigest($chatId),
                '/help' => $this->handleHelp($chatId),
                default => $this->sendMessage($chatId, "ไม่รู้จักคำสั่ง {$command}\nพิมพ์ /help เพื่อดูคำสั่งทั้งหมด"),
            };
        } else {
            // Chat Mode: Natural language conversation (read-only)
            $this->handleNaturalChat($chatId, $text);
        }
    }

    // ==========================================
    // Command Handlers
    // ==========================================

    protected function handleStart(string $chatId, ?string $username): void
    {
        $user = User::where('telegram_chat_id', $chatId)->first();

        if ($user) {
            $this->sendMessage($chatId,
                "สวัสดีคุณ <b>{$user->name}</b> 👋\n" .
                "ยินดีต้อนรับกลับสู่ VENDR Bot!\n\n" .
                "พิมพ์ /help เพื่อดูคำสั่งทั้งหมด"
            );
            return;
        }

        $this->sendMessage($chatId,
            "🏢 <b>VENDR - Procurement Bot</b>\n\n" .
            "สวัสดี! ผมเป็น Bot สำหรับระบบจัดซื้อจัดจ้าง VENDR\n\n" .
            "📋 สิ่งที่ทำได้:\n" .
            "• สร้างใบ PR ผ่าน Chat\n" .
            "• รับแจ้งเตือน Approve/Reject\n" .
            "• ดู Status ใบ PR\n" .
            "• Approve/Reject ใบ PR\n\n" .
            "🔗 เริ่มต้นใช้งาน:\n" .
            "<code>/register your-email@company.com</code>\n\n" .
            "ระบุ email ที่ลงทะเบียนในระบบ VENDR"
        );
    }

    protected function handleRegister(string $chatId, string $email, ?string $username): void
    {
        $email = trim($email);

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->sendMessage($chatId,
                "กรุณาระบุ email ที่ถูกต้อง\n" .
                "ตัวอย่าง: <code>/register email@company.com</code>"
            );
            return;
        }

        // Check if this chat is already linked
        $existingLink = User::where('telegram_chat_id', $chatId)->first();
        if ($existingLink) {
            $this->sendMessage($chatId,
                "Telegram นี้เชื่อมต่อกับ <b>{$existingLink->email}</b> อยู่แล้ว\n" .
                "พิมพ์ /me เพื่อดูข้อมูล"
            );
            return;
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->sendMessage($chatId, "ไม่พบ email นี้ในระบบ VENDR\nกรุณาตรวจสอบ email อีกครั้ง");
            return;
        }

        if ($user->telegram_chat_id) {
            $this->sendMessage($chatId, "Email นี้เชื่อมต่อกับ Telegram อื่นอยู่แล้ว\nกรุณาติดต่อ Admin");
            return;
        }

        // Generate OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->update([
            'telegram_otp' => $otp,
            'telegram_otp_expires_at' => now()->addMinutes(10),
        ]);

        // Send OTP via email
        try {
            Mail::raw(
                "รหัส OTP สำหรับเชื่อมต่อ Telegram กับ VENDR: {$otp}\n\nรหัสนี้จะหมดอายุใน 10 นาที",
                function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('VENDR - รหัส OTP สำหรับเชื่อมต่อ Telegram');
                }
            );
        } catch (\Exception $e) {
            Log::error("Failed to send OTP email: {$e->getMessage()}");
        }

        $this->sendMessage($chatId,
            "📧 ส่งรหัส OTP ไปที่ <b>{$email}</b> แล้ว\n\n" .
            "กรุณาเช็ค email แล้วพิมพ์:\n" .
            "<code>/verify 123456</code>\n\n" .
            "⏰ รหัสหมดอายุใน 10 นาที"
        );
    }

    protected function handleVerify(string $chatId, string $otp): void
    {
        $otp = trim($otp);

        if (empty($otp)) {
            $this->sendMessage($chatId, "กรุณาระบุรหัส OTP\nตัวอย่าง: <code>/verify 123456</code>");
            return;
        }

        $user = User::where('telegram_otp', $otp)
            ->where('telegram_otp_expires_at', '>', now())
            ->whereNull('telegram_chat_id')
            ->first();

        if (!$user) {
            $this->sendMessage($chatId, "รหัส OTP ไม่ถูกต้องหรือหมดอายุแล้ว\nกรุณาลอง /register ใหม่");
            return;
        }

        $username = null;
        // Try to get username from cache or message context
        $cachedUsername = Cache::get("tg_username:{$chatId}");
        if ($cachedUsername) {
            $username = $cachedUsername;
        }

        $user->update([
            'telegram_chat_id' => $chatId,
            'telegram_username' => $username,
            'telegram_otp' => null,
            'telegram_otp_expires_at' => null,
            'telegram_linked_at' => now(),
        ]);

        $this->sendMessage($chatId,
            "✅ เชื่อมต่อสำเร็จ!\n\n" .
            "👤 ชื่อ: <b>{$user->name}</b>\n" .
            "📧 Email: {$user->email}\n" .
            "🏢 แผนก: " . ($user->department->name ?? '-') . "\n\n" .
            "พิมพ์ /help เพื่อดูคำสั่งทั้งหมด"
        );
    }

    protected function handleMe(string $chatId): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        $roles = $user->activeRoles()->pluck('display_name')->implode(', ') ?: '-';

        $this->sendMessage($chatId,
            "👤 <b>ข้อมูลของคุณ</b>\n\n" .
            "ชื่อ: {$user->name}\n" .
            "Email: {$user->email}\n" .
            "แผนก: " . ($user->department->name ?? '-') . "\n" .
            "Role: {$roles}\n" .
            "เชื่อมต่อเมื่อ: " . ($user->telegram_linked_at?->format('d/m/Y H:i') ?? '-')
        );
    }

    protected function handleHelp(string $chatId): void
    {
        $user = User::where('telegram_chat_id', $chatId)->first();

        $lines = [];
        $lines[] = "📖 <b>คำสั่งทั้งหมด</b>\n";
        $lines[] = "🔗 <b>บัญชี</b>";
        $lines[] = "/register email - เชื่อมต่อ Telegram กับ VENDR";
        $lines[] = "/verify OTP - ยืนยันรหัส OTP";
        $lines[] = "/me - ดูข้อมูลบัญชี\n";
        $lines[] = "📋 <b>ใบ PR</b>";
        $lines[] = "/newpr - สร้างใบ PR ใหม่";
        $lines[] = "/mypr - ดูใบ PR ของฉัน\n";
        $lines[] = "✅ <b>อนุมัติ</b>";
        $lines[] = "/status - ดูใบ PR ที่รออนุมัติ";

        // Procurement Officer+ commands
        if ($user && $this->isProcurementRole($user)) {
            $lines[] = "\n🔧 <b>จัดซื้อ (Procurement)</b>";
            $lines[] = "/allpr - ดู PR ทั้งหมดในระบบ";
            $lines[] = "/searchpr keyword - ค้นหา PR";
            $lines[] = "/vendor keyword - ค้นหา Vendor";
            $lines[] = "/pipeline - ดู Procurement Pipeline";
            $lines[] = "/po - จัดการ Purchase Order";
            $lines[] = "/gr - ตรวจรับงาน (Goods Receipt)";
            $lines[] = "/contract - จัดการสัญญา";
            $lines[] = "/payment - ติดตามงวดการชำระเงิน";
        }

        // Procurement Manager+ commands
        if ($user && $this->isManagerRole($user)) {
            $lines[] = "\n📊 <b>ผู้จัดการ (Manager)</b>";
            $lines[] = "/dashboard - สรุปภาพรวมระบบ";
            $lines[] = "/spending - วิเคราะห์ค่าใช้จ่ายเดือนนี้";
            $lines[] = "/overdue - PR/PO ที่เกินกำหนด";
            $lines[] = "/team - สรุปงานทีมจัดซื้อ";
            $lines[] = "/anomaly - ตรวจจับความผิดปกติ";
            $lines[] = "/vendorscore keyword - ดูคะแนน Vendor";
            $lines[] = "/sla - รายงาน SLA Performance";
            $lines[] = "/report type - สร้างรายงานด่วน";
            $lines[] = "/weeklydigest - สรุปงานประจำสัปดาห์";
        }

        $lines[] = "\n📅 <b>ปฏิทินและการแจ้งเตือน</b>";
        $lines[] = "/calendar - ปฏิทินส่งมอบและ Milestone";
        $lines[] = "/ask คำถาม - ถาม AI เรื่องจัดซื้อ";
        $lines[] = "/notify - ตั้งค่าการแจ้งเตือน";

        $lines[] = "\n❓ /help - แสดงคำสั่งทั้งหมด";

        $this->sendMessage($chatId, implode("\n", $lines));
    }

    // ==========================================
    // Weekly Digest (on-demand via /weeklydigest)
    // ==========================================

    protected function handleWeeklyDigest(string $chatId): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        if (!$this->isManagerRole($user)) {
            $this->sendMessage($chatId, "⛔ คำสั่งนี้สำหรับผู้จัดการจัดซื้อขึ้นไป");
            return;
        }

        $this->sendMessage($chatId, "⏳ กำลังสร้างสรุปประจำสัปดาห์...");

        try {
            \Illuminate\Support\Facades\Artisan::call('telegram:weekly-digest', [
                '--user' => $user->id,
            ]);

            // The command itself sends the message, no need to send again
        } catch (\Exception $e) {
            Log::error("Weekly digest on-demand error: {$e->getMessage()}");
            $this->sendMessage($chatId, "❌ เกิดข้อผิดพลาด: {$e->getMessage()}");
        }
    }

    // ==========================================
    // Role Check Helpers
    // ==========================================

    protected function isProcurementRole(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'procurement_officer', 'procurement_manager']);
    }

    protected function isManagerRole(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'procurement_manager']);
    }

    // ==========================================
    // Procurement Officer Commands
    // ==========================================

    protected function handleAllPR(string $chatId): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        if (!$this->isProcurementRole($user)) {
            $this->sendMessage($chatId, "⛔ คำสั่งนี้สำหรับเจ้าหน้าที่จัดซื้อขึ้นไป");
            return;
        }

        $statusIcons = [
            'draft' => '📝', 'pending_approval' => '⏳', 'approved' => '✅',
            'rejected' => '❌', 'in_process' => '🔄', 'completed' => '🏁', 'cancelled' => '🚫',
        ];

        $prs = PurchaseRequisition::orderBy('created_at', 'desc')->limit(15)->get();

        if ($prs->isEmpty()) {
            $this->sendMessage($chatId, "ไม่พบใบ PR ในระบบ");
            return;
        }

        $total = PurchaseRequisition::count();
        $text = "📋 <b>PR ทั้งหมดในระบบ</b> ({$total} ใบ)\n\n";

        foreach ($prs as $pr) {
            $icon = $statusIcons[$pr->status] ?? '📄';
            $requester = $pr->requester->name ?? 'N/A';
            $amount = $pr->total_amount ? number_format($pr->total_amount, 2) : '-';
            $text .= "{$icon} <b>{$pr->pr_number}</b>\n";
            $text .= "   {$pr->title}\n";
            $text .= "   👤 {$requester} | 💰 {$amount} THB | {$pr->status}\n\n";
        }

        if ($total > 15) {
            $text .= "แสดง 15 รายการล่าสุด\n";
            $text .= "🔍 ค้นหาเพิ่ม: /searchpr keyword";
        }

        $this->sendMessage($chatId, $text);
    }

    protected function handleSearchPR(string $chatId, string $keyword): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        if (!$this->isProcurementRole($user)) {
            $this->sendMessage($chatId, "⛔ คำสั่งนี้สำหรับเจ้าหน้าที่จัดซื้อขึ้นไป");
            return;
        }

        $keyword = trim($keyword);
        if (empty($keyword)) {
            $this->sendMessage($chatId, "กรุณาระบุคำค้นหา\nตัวอย่าง: <code>/searchpr ปากกา</code>");
            return;
        }

        $prs = PurchaseRequisition::where(function ($q) use ($keyword) {
            $q->where('pr_number', 'LIKE', "%{$keyword}%")
              ->orWhere('title', 'LIKE', "%{$keyword}%")
              ->orWhere('description', 'LIKE', "%{$keyword}%");
        })->orderBy('created_at', 'desc')->limit(10)->get();

        if ($prs->isEmpty()) {
            $this->sendMessage($chatId, "🔍 ไม่พบ PR ที่ตรงกับ \"<b>{$keyword}</b>\"");
            return;
        }

        $statusIcons = [
            'draft' => '📝', 'pending_approval' => '⏳', 'approved' => '✅',
            'rejected' => '❌', 'in_process' => '🔄', 'completed' => '🏁', 'cancelled' => '🚫',
        ];

        $text = "🔍 <b>ผลค้นหา \"{$keyword}\"</b> ({$prs->count()} รายการ)\n\n";

        foreach ($prs as $pr) {
            $icon = $statusIcons[$pr->status] ?? '📄';
            $requester = $pr->requester->name ?? 'N/A';
            $amount = $pr->total_amount ? number_format($pr->total_amount, 2) : '-';
            $dept = $pr->department->name ?? '-';
            $text .= "{$icon} <b>{$pr->pr_number}</b>\n";
            $text .= "   📝 {$pr->title}\n";
            $text .= "   👤 {$requester} | 🏢 {$dept}\n";
            $text .= "   💰 {$amount} THB | สถานะ: {$pr->status}\n\n";
        }

        $this->sendMessage($chatId, $text);
    }

    protected function handleVendorSearch(string $chatId, string $keyword): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        if (!$this->isProcurementRole($user)) {
            $this->sendMessage($chatId, "⛔ คำสั่งนี้สำหรับเจ้าหน้าที่จัดซื้อขึ้นไป");
            return;
        }

        $keyword = trim($keyword);
        if (empty($keyword)) {
            // Show all vendors summary
            $vendors = Vendor::orderBy('company_name')->limit(15)->get();
            if ($vendors->isEmpty()) {
                $this->sendMessage($chatId, "ไม่พบ Vendor ในระบบ");
                return;
            }

            $total = Vendor::count();
            $text = "🏪 <b>Vendor ทั้งหมด</b> ({$total} ราย)\n\n";

            $statusIcons = ['approved' => '✅', 'pending' => '⏳', 'rejected' => '❌', 'suspended' => '🚫'];

            foreach ($vendors as $v) {
                $icon = $statusIcons[$v->status] ?? '📄';
                $poCount = $v->purchaseOrders()->count();
                $text .= "{$icon} <b>{$v->company_name}</b>\n";
                $text .= "   📧 {$v->contact_email} | 📞 {$v->contact_phone}\n";
                $text .= "   📋 PO: {$poCount} รายการ\n\n";
            }

            $text .= "🔍 ค้นหา: <code>/vendor ชื่อบริษัท</code>";
            $this->sendMessage($chatId, $text);
            return;
        }

        $vendors = Vendor::where(function ($q) use ($keyword) {
            $q->where('company_name', 'LIKE', "%{$keyword}%")
              ->orWhere('contact_name', 'LIKE', "%{$keyword}%")
              ->orWhere('contact_email', 'LIKE', "%{$keyword}%")
              ->orWhere('tax_id', 'LIKE', "%{$keyword}%");
        })->limit(10)->get();

        if ($vendors->isEmpty()) {
            $this->sendMessage($chatId, "🔍 ไม่พบ Vendor ที่ตรงกับ \"<b>{$keyword}</b>\"");
            return;
        }

        $text = "🔍 <b>ผลค้นหา Vendor \"{$keyword}\"</b>\n\n";

        foreach ($vendors as $v) {
            $poCount = $v->purchaseOrders()->count();
            $totalPOAmount = $v->purchaseOrders()->sum('total_amount');
            $text .= "🏪 <b>{$v->company_name}</b>\n";
            $text .= "   👤 ติดต่อ: {$v->contact_name}\n";
            $text .= "   📧 {$v->contact_email} | 📞 {$v->contact_phone}\n";
            $text .= "   📋 PO: {$poCount} รายการ | 💰 " . number_format($totalPOAmount, 2) . " THB\n";
            $text .= "   สถานะ: {$v->status}\n\n";
        }

        $this->sendMessage($chatId, $text);
    }

    protected function handlePipeline(string $chatId): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        if (!$this->isProcurementRole($user)) {
            $this->sendMessage($chatId, "⛔ คำสั่งนี้สำหรับเจ้าหน้าที่จัดซื้อขึ้นไป");
            return;
        }

        // PR Pipeline
        $prDraft = PurchaseRequisition::where('status', 'draft')->count();
        $prPending = PurchaseRequisition::where('status', 'pending_approval')->count();
        $prApproved = PurchaseRequisition::where('status', 'approved')->count();
        $prRejected = PurchaseRequisition::where('status', 'rejected')->count();
        $prInProcess = PurchaseRequisition::where('status', 'in_process')->count();
        $prCompleted = PurchaseRequisition::where('status', 'completed')->count();

        // PO Pipeline
        $poDraft = PurchaseOrder::where('status', 'draft')->count();
        $poPending = PurchaseOrder::where('status', 'pending_approval')->count();
        $poApproved = PurchaseOrder::where('status', 'approved')->count();
        $poSent = PurchaseOrder::where('status', 'sent_to_supplier')->count();
        $poPartial = PurchaseOrder::where('status', 'partially_received')->count();
        $poReceived = PurchaseOrder::where('status', 'fully_received')->count();
        $poClosed = PurchaseOrder::where('status', 'closed')->count();

        $text = "📊 <b>Procurement Pipeline</b>\n\n";

        $text .= "📋 <b>Purchase Requisition (PR)</b>\n";
        $text .= "   📝 Draft: {$prDraft}\n";
        $text .= "   ⏳ รออนุมัติ: {$prPending}\n";
        $text .= "   ✅ อนุมัติ: {$prApproved}\n";
        $text .= "   ❌ ปฏิเสธ: {$prRejected}\n";
        $text .= "   🔄 ดำเนินการ: {$prInProcess}\n";
        $text .= "   🏁 เสร็จสิ้น: {$prCompleted}\n\n";

        $text .= "📦 <b>Purchase Order (PO)</b>\n";
        $text .= "   📝 Draft: {$poDraft}\n";
        $text .= "   ⏳ รออนุมัติ: {$poPending}\n";
        $text .= "   ✅ อนุมัติ: {$poApproved}\n";
        $text .= "   📤 ส่ง Vendor: {$poSent}\n";
        $text .= "   📦 รับบางส่วน: {$poPartial}\n";
        $text .= "   ✅ รับครบ: {$poReceived}\n";
        $text .= "   🔒 ปิดงาน: {$poClosed}\n";

        // Bottleneck warning
        if ($prPending >= 5 || $poPending >= 5) {
            $text .= "\n⚠️ <b>Bottleneck:</b>\n";
            if ($prPending >= 5) $text .= "   PR รออนุมัติค้าง {$prPending} ใบ\n";
            if ($poPending >= 5) $text .= "   PO รออนุมัติค้าง {$poPending} ใบ\n";
        }

        $this->sendMessage($chatId, $text);
    }

    // ==========================================
    // Procurement Manager Commands
    // ==========================================

    protected function handleDashboard(string $chatId): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        if (!$this->isManagerRole($user)) {
            $this->sendMessage($chatId, "⛔ คำสั่งนี้สำหรับผู้จัดการจัดซื้อขึ้นไป");
            return;
        }

        $now = now();

        // PR Stats
        $prTotal = PurchaseRequisition::count();
        $prThisMonth = PurchaseRequisition::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)->count();
        $prPending = PurchaseRequisition::where('status', 'pending_approval')->count();
        $prUrgent = PurchaseRequisition::where('status', 'pending_approval')
            ->where('priority', 'urgent')->count();

        // PO Stats
        $poTotal = PurchaseOrder::count();
        $poThisMonth = PurchaseOrder::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)->count();
        $poActive = PurchaseOrder::whereIn('status', ['approved', 'sent_to_supplier', 'partially_received'])->count();

        // Financial
        $prAmountMonth = PurchaseRequisition::whereIn('status', ['approved', 'in_process', 'completed'])
            ->whereMonth('approved_at', $now->month)
            ->whereYear('approved_at', $now->year)
            ->sum('total_amount');

        $poAmountMonth = PurchaseOrder::whereIn('status', ['approved', 'sent_to_supplier', 'partially_received', 'fully_received', 'closed'])
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->sum('total_amount');

        // Vendor Stats
        $vendorTotal = Vendor::count();
        $vendorActive = Vendor::where('status', 'approved')->count();

        $text = "📊 <b>Dashboard - ภาพรวมระบบ</b>\n";
        $text .= "📅 " . $now->locale('th')->translatedFormat('F Y') . "\n\n";

        $text .= "📋 <b>Purchase Requisition</b>\n";
        $text .= "   ทั้งหมด: {$prTotal} | เดือนนี้: {$prThisMonth}\n";
        $text .= "   ⏳ รออนุมัติ: {$prPending}";
        if ($prUrgent > 0) $text .= " (🔴 เร่งด่วน {$prUrgent})";
        $text .= "\n   💰 มูลค่า PR อนุมัติเดือนนี้: " . number_format($prAmountMonth, 2) . " THB\n\n";

        $text .= "📦 <b>Purchase Order</b>\n";
        $text .= "   ทั้งหมด: {$poTotal} | เดือนนี้: {$poThisMonth}\n";
        $text .= "   🔄 กำลังดำเนินการ: {$poActive}\n";
        $text .= "   💰 มูลค่า PO เดือนนี้: " . number_format($poAmountMonth, 2) . " THB\n\n";

        $text .= "🏪 <b>Vendor</b>\n";
        $text .= "   ทั้งหมด: {$vendorTotal} | Active: {$vendorActive}\n";

        $this->sendMessage($chatId, $text);
    }

    protected function handleSpending(string $chatId): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        if (!$this->isManagerRole($user)) {
            $this->sendMessage($chatId, "⛔ คำสั่งนี้สำหรับผู้จัดการจัดซื้อขึ้นไป");
            return;
        }

        $now = now();

        $departments = Department::active()->get();
        $text = "💰 <b>วิเคราะห์ค่าใช้จ่าย</b>\n";
        $text .= "📅 " . $now->locale('th')->translatedFormat('F Y') . "\n\n";

        $grandTotal = 0;
        $deptData = [];

        foreach ($departments as $dept) {
            $spent = PurchaseRequisition::where('department_id', $dept->id)
                ->whereIn('status', ['approved', 'in_process', 'completed'])
                ->whereMonth('approved_at', $now->month)
                ->whereYear('approved_at', $now->year)
                ->sum('total_amount');

            $prCount = PurchaseRequisition::where('department_id', $dept->id)
                ->whereIn('status', ['approved', 'in_process', 'completed'])
                ->whereMonth('approved_at', $now->month)
                ->whereYear('approved_at', $now->year)
                ->count();

            if ($spent > 0 || $prCount > 0) {
                $deptData[] = ['dept' => $dept, 'spent' => $spent, 'count' => $prCount];
                $grandTotal += $spent;
            }
        }

        // Sort by spending desc
        usort($deptData, fn($a, $b) => $b['spent'] <=> $a['spent']);

        if (empty($deptData)) {
            $text .= "ไม่พบข้อมูลค่าใช้จ่ายเดือนนี้";
            $this->sendMessage($chatId, $text);
            return;
        }

        foreach ($deptData as $d) {
            $dept = $d['dept'];
            $spent = $d['spent'];
            $count = $d['count'];
            $percent = $grandTotal > 0 ? round(($spent / $grandTotal) * 100, 1) : 0;

            $budgetInfo = '';
            if ($dept->monthly_budget > 0) {
                $budgetPercent = round(($spent / $dept->monthly_budget) * 100, 1);
                $icon = $budgetPercent >= 90 ? '🔴' : ($budgetPercent >= 70 ? '🟡' : '🟢');
                $budgetInfo = " {$icon} {$budgetPercent}% ของงบ";
            }

            $text .= "🏢 <b>{$dept->name}</b>\n";
            $text .= "   💰 " . number_format($spent, 2) . " THB ({$percent}%){$budgetInfo}\n";
            $text .= "   📋 PR: {$count} ใบ\n\n";
        }

        $text .= "━━━━━━━━━━━━━━━\n";
        $text .= "💰 <b>รวมทั้งหมด: " . number_format($grandTotal, 2) . " THB</b>";

        $this->sendMessage($chatId, $text);
    }

    protected function handleOverdue(string $chatId): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        if (!$this->isManagerRole($user)) {
            $this->sendMessage($chatId, "⛔ คำสั่งนี้สำหรับผู้จัดการจัดซื้อขึ้นไป");
            return;
        }

        $text = "⏰ <b>รายการเกินกำหนด</b>\n\n";
        $hasContent = false;

        // PR overdue: pending_approval > 3 days
        $overduePRs = PurchaseRequisition::where('status', 'pending_approval')
            ->where(function ($q) {
                $q->where('submitted_at', '<=', now()->subDays(3))
                  ->orWhere(function ($q2) {
                      $q2->whereNull('submitted_at')
                          ->where('updated_at', '<=', now()->subDays(3));
                  });
            })
            ->orderBy('submitted_at', 'asc')
            ->limit(10)
            ->get();

        if ($overduePRs->isNotEmpty()) {
            $hasContent = true;
            $text .= "📋 <b>PR รออนุมัตินานเกิน 3 วัน</b> ({$overduePRs->count()} ใบ)\n\n";
            foreach ($overduePRs as $pr) {
                $days = $pr->submitted_at ? now()->diffInDays($pr->submitted_at) : '?';
                $priorityIcons = ['urgent' => '🔴', 'high' => '🟠', 'medium' => '🟡', 'low' => '🟢'];
                $icon = $priorityIcons[$pr->priority] ?? '📄';
                $text .= "   {$icon} {$pr->pr_number} - {$pr->title} (รอ {$days} วัน)\n";
            }
            $text .= "\n";
        }

        // PO overdue: expected_delivery_date passed
        $overduePOs = PurchaseOrder::whereIn('status', ['approved', 'sent_to_supplier', 'partially_received'])
            ->whereNotNull('expected_delivery_date')
            ->where('expected_delivery_date', '<', now())
            ->orderBy('expected_delivery_date', 'asc')
            ->limit(10)
            ->get();

        if ($overduePOs->isNotEmpty()) {
            $hasContent = true;
            $text .= "📦 <b>PO เกินกำหนดส่งของ</b> ({$overduePOs->count()} ใบ)\n\n";
            foreach ($overduePOs as $po) {
                $daysLate = now()->diffInDays($po->expected_delivery_date);
                $vendor = $po->vendor->company_name ?? $po->company_name ?? 'N/A';
                $text .= "   🔴 {$po->po_number} - {$vendor} (เลย {$daysLate} วัน)\n";
                $text .= "      กำหนดส่ง: {$po->expected_delivery_date->format('d/m/Y')}\n";
            }
            $text .= "\n";
        }

        // PR approaching required_date
        $approachingPRs = PurchaseRequisition::whereIn('status', ['draft', 'pending_approval', 'approved'])
            ->whereNotNull('required_date')
            ->whereBetween('required_date', [now(), now()->addDays(3)])
            ->orderBy('required_date', 'asc')
            ->limit(10)
            ->get();

        if ($approachingPRs->isNotEmpty()) {
            $hasContent = true;
            $text .= "⚠️ <b>PR ใกล้ถึงวันต้องการ (3 วัน)</b>\n\n";
            foreach ($approachingPRs as $pr) {
                $daysLeft = now()->diffInDays($pr->required_date);
                $text .= "   🟡 {$pr->pr_number} - {$pr->title} (เหลือ {$daysLeft} วัน)\n";
            }
        }

        if (!$hasContent) {
            $text .= "✅ ไม่มีรายการเกินกำหนด";
        }

        $this->sendMessage($chatId, $text);
    }

    protected function handleTeam(string $chatId): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        if (!$this->isManagerRole($user)) {
            $this->sendMessage($chatId, "⛔ คำสั่งนี้สำหรับผู้จัดการจัดซื้อขึ้นไป");
            return;
        }

        $now = now();

        // Get procurement team members
        $teamMembers = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['procurement_officer', 'procurement_manager']);
        })->get();

        $text = "👥 <b>สรุปงานทีมจัดซื้อ</b>\n";
        $text .= "📅 " . $now->locale('th')->translatedFormat('F Y') . "\n\n";

        if ($teamMembers->isEmpty()) {
            $text .= "ไม่พบสมาชิกทีมจัดซื้อ";
            $this->sendMessage($chatId, $text);
            return;
        }

        foreach ($teamMembers as $member) {
            $roles = $member->activeRoles()->pluck('display_name')->implode(', ');

            // POs created this month
            $poCreated = PurchaseOrder::where('created_by', $member->id)
                ->whereMonth('created_at', $now->month)
                ->whereYear('created_at', $now->year)
                ->count();

            $poAmount = PurchaseOrder::where('created_by', $member->id)
                ->whereMonth('created_at', $now->month)
                ->whereYear('created_at', $now->year)
                ->sum('total_amount');

            // PRs approved this month (if approver)
            $prApproved = PurchaseRequisition::where('approved_by', $member->id)
                ->whereMonth('approved_at', $now->month)
                ->whereYear('approved_at', $now->year)
                ->count();

            $linked = $member->telegram_chat_id ? '🟢' : '⚪';

            $text .= "{$linked} <b>{$member->name}</b>\n";
            $text .= "   📌 {$roles}\n";
            $text .= "   📦 PO สร้าง: {$poCreated} ใบ (" . number_format($poAmount, 2) . " THB)\n";
            if ($prApproved > 0) {
                $text .= "   ✅ PR อนุมัติ: {$prApproved} ใบ\n";
            }
            $text .= "\n";
        }

        $text .= "🟢 = เชื่อมต่อ Telegram | ⚪ = ยังไม่เชื่อมต่อ";

        $this->sendMessage($chatId, $text);
    }

    protected function handleAnomaly(string $chatId): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        if (!$this->isManagerRole($user)) {
            $this->sendMessage($chatId, "⛔ คำสั่งนี้สำหรับผู้จัดการจัดซื้อขึ้นไป");
            return;
        }

        $this->sendMessage($chatId, "🔍 กำลังสแกนหาความผิดปกติ... กรุณารอสักครู่");

        try {
            $service = app(AnomalyDetectionService::class);

            // Scan all active companies
            $allResults = $service->scanAll();
            $companies = Company::where('is_active', true)->get()->keyBy('id');

            // Collect all new anomalies + aggregated summary
            $allNewAnomalies = [];
            $totalOpen = 0;
            $totalUnresolved = 0;
            $totalCritical = 0;
            $totalResolved = 0;
            $aggregatedByType = [];
            $aggregatedBySeverity = [];

            foreach ($allResults as $companyId => $newAnomalies) {
                $allNewAnomalies = array_merge($allNewAnomalies, $newAnomalies);

                $summary = $service->getSummary($companyId);
                $totalOpen += $summary['total_open'] ?? 0;
                $totalUnresolved += $summary['total_unresolved'] ?? 0;
                $totalCritical += $summary['critical_open'] ?? 0;
                $totalResolved += $summary['resolved_this_month'] ?? 0;

                foreach ($summary['by_type'] ?? [] as $type => $count) {
                    $aggregatedByType[$type] = ($aggregatedByType[$type] ?? 0) + $count;
                }
                foreach ($summary['by_severity'] ?? [] as $sev => $count) {
                    $aggregatedBySeverity[$sev] = ($aggregatedBySeverity[$sev] ?? 0) + $count;
                }
            }

            // Build summary message
            $text = "🛡️ <b>Anomaly Detection Report</b>\n";
            $text .= "📅 " . now()->locale('th')->translatedFormat('d F Y H:i') . "\n\n";

            // Summary stats
            $text .= "📊 <b>สรุปภาพรวม (ทุกบริษัท)</b>\n";
            $text .= "🔴 วิกฤต: {$totalCritical} รายการ\n";
            $text .= "🟡 เตือน: " . ($aggregatedBySeverity['warning'] ?? 0) . " รายการ\n";
            $text .= "ℹ️ แจ้งเตือน: " . ($aggregatedBySeverity['info'] ?? 0) . " รายการ\n";
            $text .= "📂 รอตรวจสอบ: {$totalOpen} รายการ\n";
            $text .= "✅ แก้ไขแล้วเดือนนี้: {$totalResolved} รายการ\n\n";

            // New anomalies from this scan
            $newCount = count($allNewAnomalies);
            if ($newCount > 0) {
                $text .= "🆕 <b>พบใหม่จากการสแกน: {$newCount} รายการ</b>\n\n";

                foreach (array_slice($allNewAnomalies, 0, 10) as $anomaly) {
                    $icon = match ($anomaly->severity) {
                        'critical' => '🔴',
                        'warning' => '🟡',
                        default => 'ℹ️',
                    };
                    $companyName = $companies[$anomaly->company_id]->display_name ?? "#{$anomaly->company_id}";
                    $text .= "{$icon} <b>{$anomaly->type_label}</b>\n";
                    $text .= "   🏢 {$companyName}\n";
                    $text .= "   {$anomaly->title}\n";
                    $text .= "   <i>" . mb_substr($anomaly->description, 0, 100) . "</i>\n\n";
                }

                if ($newCount > 10) {
                    $text .= "... และอีก " . ($newCount - 10) . " รายการ\n\n";
                }
            } else {
                $text .= "✅ <b>ไม่พบความผิดปกติใหม่จากการสแกน</b>\n\n";
            }

            // Type breakdown
            if (!empty($aggregatedByType)) {
                $text .= "📋 <b>แยกตามประเภท (ยังไม่แก้ไข)</b>\n";
                $typeLabels = [
                    'price_anomaly' => '💰 ราคาผิดปกติ',
                    'split_pr' => '✂️ แยก PR หลีกเลี่ยงวงเงิน',
                    'budget_overrun' => '📈 งบประมาณเกิน',
                    'vendor_concentration' => '🏪 Vendor กระจุกตัว',
                    'approval_delay' => '⏰ อนุมัติล่าช้า',
                ];
                foreach ($aggregatedByType as $type => $count) {
                    $label = $typeLabels[$type] ?? $type;
                    $text .= "   {$label}: {$count}\n";
                }
                $text .= "\n";
            }

            $text .= "🔗 ดูรายละเอียดเพิ่มเติมที่ Admin > Anomaly Detection";

            $this->sendMessage($chatId, $text);

        } catch (\Exception $e) {
            Log::error("Telegram anomaly scan error: {$e->getMessage()}");
            $this->sendMessage($chatId, "❌ เกิดข้อผิดพลาดในการสแกน: " . mb_substr($e->getMessage(), 0, 200));
        }
    }

    // ==========================================
    // #1 /po — Purchase Order Management
    // ==========================================

    protected function handlePO(string $chatId, string $args): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        if (!$this->isProcurementRole($user)) {
            $this->sendMessage($chatId, "⛔ คำสั่งนี้สำหรับเจ้าหน้าที่จัดซื้อขึ้นไป");
            return;
        }

        $args = trim($args);

        // /po <number> — view specific PO
        if (!empty($args)) {
            $po = PurchaseOrder::where('po_number', 'LIKE', "%{$args}%")
                ->orWhere('sap_po_number', 'LIKE', "%{$args}%")
                ->first();

            if (!$po) {
                $this->sendMessage($chatId, "🔍 ไม่พบ PO ที่ตรงกับ \"<b>{$args}</b>\"");
                return;
            }

            $this->sendPODetail($chatId, $po);
            return;
        }

        // /po — show summary + recent POs
        $statusIcons = [
            'draft' => '📝', 'pending_approval' => '⏳', 'approved' => '✅',
            'rejected' => '❌', 'sent_to_supplier' => '📤', 'acknowledged' => '👁',
            'partially_received' => '📦', 'fully_received' => '✅', 'closed' => '🔒', 'cancelled' => '🚫',
        ];

        $statusCounts = PurchaseOrder::select('status', DB::raw('count(*) as cnt'))
            ->groupBy('status')->pluck('cnt', 'status')->toArray();

        $text = "📦 <b>Purchase Order Management</b>\n\n";
        $text .= "📊 <b>สรุปสถานะ PO</b>\n";
        $totalPO = array_sum($statusCounts);
        $text .= "ทั้งหมด: {$totalPO} ใบ\n";

        foreach ($statusCounts as $status => $cnt) {
            $icon = $statusIcons[$status] ?? '📄';
            $label = PurchaseOrder::find(PurchaseOrder::where('status', $status)->value('id'))?->status_text ?? $status;
            $text .= "  {$icon} {$label}: {$cnt}\n";
        }

        // Active POs
        $activePOs = PurchaseOrder::whereIn('status', ['approved', 'sent_to_supplier', 'partially_received'])
            ->with(['vendor', 'creator'])
            ->orderBy('expected_delivery_date', 'asc')
            ->limit(10)->get();

        if ($activePOs->isNotEmpty()) {
            $text .= "\n🔄 <b>PO ที่กำลังดำเนินการ</b>\n\n";
            foreach ($activePOs as $po) {
                $vendor = $po->vendor->company_name ?? $po->company_name ?? 'N/A';
                $amount = number_format($po->total_amount ?? 0, 2);
                $delivery = $po->expected_delivery_date?->format('d/m/Y') ?? '-';
                $daysLeft = $po->expected_delivery_date ? now()->diffInDays($po->expected_delivery_date, false) : null;
                $daysText = '';
                if ($daysLeft !== null) {
                    $daysText = $daysLeft < 0 ? " 🔴 เลย " . abs($daysLeft) . " วัน" : " (เหลือ {$daysLeft} วัน)";
                }

                $icon = $statusIcons[$po->status] ?? '📄';
                $text .= "{$icon} <b>{$po->po_number}</b>\n";
                $text .= "   🏪 {$vendor}\n";
                $text .= "   💰 {$amount} THB | 📅 ส่ง: {$delivery}{$daysText}\n\n";
            }
        }

        $text .= "🔍 ดู PO เฉพาะ: <code>/po เลข-PO</code>";
        $this->sendMessage($chatId, $text);
    }

    protected function sendPODetail(string $chatId, PurchaseOrder $po): void
    {
        $vendor = $po->vendor->company_name ?? $po->company_name ?? 'N/A';
        $creator = $po->creator->name ?? 'N/A';
        $approver = $po->approver->name ?? '-';
        $amount = number_format($po->total_amount ?? 0, 2);
        $pr = $po->purchaseRequisition ?? $po->purchaseRequisitionByPrId;

        $text = "📦 <b>รายละเอียด PO</b>\n\n";
        $text .= "📋 เลขที่: <b>{$po->po_number}</b>\n";
        if ($po->sap_po_number) {
            $text .= "🔗 SAP: {$po->sap_po_number}\n";
        }
        $text .= "📌 สถานะ: {$po->status_text}\n";
        $text .= "⚡ ความเร่งด่วน: {$po->priority_text}\n";
        $text .= "🏪 Vendor: {$vendor}\n";
        $text .= "💰 มูลค่า: {$amount} {$po->currency}\n";

        if ($pr) {
            $text .= "📋 PR: {$pr->pr_number}\n";
        }

        $text .= "👤 สร้างโดย: {$creator}\n";
        if ($approver !== '-') {
            $text .= "✅ อนุมัติโดย: {$approver}\n";
        }

        $text .= "📅 วันสั่งซื้อ: " . ($po->order_date?->format('d/m/Y') ?? '-') . "\n";
        $text .= "📅 กำหนดส่ง: " . ($po->expected_delivery_date?->format('d/m/Y') ?? '-') . "\n";

        if ($po->delivery_schedule) {
            $text .= "🚚 กำหนดส่งมอบ: {$po->delivery_schedule}\n";
        }
        if ($po->payment_terms) {
            $text .= "💳 เงื่อนไขชำระ: {$po->payment_terms}\n";
        }

        // Items
        $items = $po->items;
        if ($items && $items->count() > 0) {
            $text .= "\n📦 <b>รายการสินค้า ({$items->count()} รายการ)</b>\n";
            foreach ($items->take(5) as $i => $item) {
                $text .= "  " . ($i + 1) . ". {$item->description}\n";
                $text .= "     {$item->quantity} {$item->unit_of_measure} × " . number_format($item->unit_price ?? 0, 2) . " = " . number_format($item->total_price ?? 0, 2) . "\n";
            }
            if ($items->count() > 5) {
                $text .= "  ... และอีก " . ($items->count() - 5) . " รายการ\n";
            }
        }

        // GR Status
        $grs = $po->goodsReceipts;
        if ($grs && $grs->count() > 0) {
            $text .= "\n📋 <b>การตรวจรับ (GR)</b>\n";
            foreach ($grs as $gr) {
                $text .= "  📄 {$gr->gr_number} - {$gr->status_label} ({$gr->inspection_status_label})\n";
            }
        }

        // Payment milestones
        $milestones = $po->paymentMilestones;
        if ($milestones && $milestones->count() > 0) {
            $text .= "\n💳 <b>งวดชำระเงิน</b>\n";
            foreach ($milestones as $ms) {
                $icon = match ($ms->status) {
                    'paid' => '✅', 'overdue' => '🔴', 'due' => '🟡', default => '⏳',
                };
                $text .= "  {$icon} งวด {$ms->milestone_number}: " . number_format($ms->amount ?? 0, 2) . " ({$ms->status_label})\n";
            }
        }

        // Action buttons for managers
        $buttons = [];
        if ($po->canApprove()) {
            $buttons[] = [
                ['text' => '✅ Approve PO', 'callback_data' => "po_approve:{$po->id}"],
                ['text' => '❌ Reject PO', 'callback_data' => "po_reject:{$po->id}"],
            ];
        }

        $this->sendMessage($chatId, $text, !empty($buttons) ? ['inline_keyboard' => $buttons] : null);
    }

    // ==========================================
    // #2 /gr — Goods Receipt Management
    // ==========================================

    protected function handleGR(string $chatId, string $args): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        if (!$this->isProcurementRole($user)) {
            $this->sendMessage($chatId, "⛔ คำสั่งนี้สำหรับเจ้าหน้าที่จัดซื้อขึ้นไป");
            return;
        }

        $args = trim($args);

        // /gr <number> — view specific GR
        if (!empty($args)) {
            $gr = GoodsReceipt::where('gr_number', 'LIKE', "%{$args}%")
                ->orWhere('receipt_number', 'LIKE', "%{$args}%")
                ->first();

            if (!$gr) {
                $this->sendMessage($chatId, "🔍 ไม่พบ GR ที่ตรงกับ \"<b>{$args}</b>\"");
                return;
            }

            $this->sendGRDetail($chatId, $gr);
            return;
        }

        // /gr — show summary
        $text = "📋 <b>Goods Receipt (ตรวจรับงาน)</b>\n\n";

        $statusCounts = GoodsReceipt::select('status', DB::raw('count(*) as cnt'))
            ->groupBy('status')->pluck('cnt', 'status')->toArray();

        $inspectionCounts = GoodsReceipt::select('inspection_status', DB::raw('count(*) as cnt'))
            ->groupBy('inspection_status')->pluck('cnt', 'status')->toArray();

        $totalGR = array_sum($statusCounts);
        $text .= "📊 <b>สรุปสถานะ</b> (ทั้งหมด {$totalGR} ใบ)\n";

        $grIcons = ['draft' => '📝', 'completed' => '✅', 'returned' => '🔄', 'partially_returned' => '📦', 'cancelled' => '🚫'];
        foreach ($statusCounts as $status => $cnt) {
            $icon = $grIcons[$status] ?? '📄';
            $text .= "  {$icon} {$status}: {$cnt}\n";
        }

        // Pending inspection
        $pendingInspection = GoodsReceipt::where('inspection_status', 'pending')
            ->with(['purchaseOrder', 'vendor'])
            ->orderBy('receipt_date', 'desc')
            ->limit(10)->get();

        if ($pendingInspection->isNotEmpty()) {
            $text .= "\n⏳ <b>รอตรวจสอบ (Pending Inspection)</b>\n\n";
            foreach ($pendingInspection as $gr) {
                $poNumber = $gr->purchaseOrder->po_number ?? '-';
                $vendor = $gr->vendor->company_name ?? 'N/A';
                $text .= "📄 <b>{$gr->gr_number}</b>\n";
                $text .= "   📦 PO: {$poNumber} | 🏪 {$vendor}\n";
                $text .= "   📅 รับเมื่อ: " . ($gr->receipt_date?->format('d/m/Y') ?? '-') . "\n\n";
            }
        }

        // Recent completed
        $recentCompleted = GoodsReceipt::where('status', GoodsReceipt::STATUS_COMPLETED)
            ->orderBy('updated_at', 'desc')
            ->limit(5)->get();

        if ($recentCompleted->isNotEmpty()) {
            $text .= "✅ <b>ตรวจรับเสร็จล่าสุด</b>\n";
            foreach ($recentCompleted as $gr) {
                $text .= "  ✅ {$gr->gr_number} - {$gr->inspection_status_label}\n";
            }
        }

        $text .= "\n🔍 ดู GR เฉพาะ: <code>/gr เลข-GR</code>";
        $this->sendMessage($chatId, $text);
    }

    protected function sendGRDetail(string $chatId, GoodsReceipt $gr): void
    {
        $po = $gr->purchaseOrder;
        $vendor = $gr->vendor->company_name ?? 'N/A';
        $receiver = $gr->receivedBy->name ?? 'N/A';

        $text = "📋 <b>รายละเอียด GR</b>\n\n";
        $text .= "📄 เลขที่: <b>{$gr->gr_number}</b>\n";
        $text .= "📌 สถานะ: {$gr->status_label}\n";
        $text .= "🔍 ผลตรวจ: {$gr->inspection_status_label}\n";
        $text .= "📦 PO: " . ($po->po_number ?? '-') . "\n";
        $text .= "🏪 Vendor: {$vendor}\n";
        $text .= "👤 ผู้รับ: {$receiver}\n";
        $text .= "📅 วันรับ: " . ($gr->receipt_date?->format('d/m/Y') ?? '-') . "\n";

        if ($gr->delivery_milestone) {
            $text .= "📍 งวดส่ง: {$gr->delivery_milestone}\n";
        }
        if ($gr->milestone_percentage) {
            $text .= "📊 ความคืบหน้า: {$gr->milestone_percentage}%\n";
        }
        if ($gr->inspection_notes) {
            $text .= "📝 หมายเหตุตรวจ: {$gr->inspection_notes}\n";
        }
        if ($gr->carrier) {
            $text .= "🚚 ผู้ส่ง: {$gr->carrier}\n";
        }
        if ($gr->tracking_number) {
            $text .= "🔗 Tracking: {$gr->tracking_number}\n";
        }

        // Items
        $items = $gr->items;
        if ($items && $items->count() > 0) {
            $text .= "\n📦 <b>รายการ ({$items->count()} รายการ)</b>\n";
            foreach ($items->take(5) as $i => $item) {
                $text .= "  " . ($i + 1) . ". {$item->description}\n";
                $text .= "     รับ: {$item->received_quantity} / สั่ง: {$item->ordered_quantity}\n";
            }
        }

        $this->sendMessage($chatId, $text);
    }

    // ==========================================
    // #3 /contract — Contract Management
    // ==========================================

    protected function handleContract(string $chatId, string $args): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        if (!$this->isProcurementRole($user)) {
            $this->sendMessage($chatId, "⛔ คำสั่งนี้สำหรับเจ้าหน้าที่จัดซื้อขึ้นไป");
            return;
        }

        $args = trim($args);

        // /contract <number> — view specific contract
        if (!empty($args)) {
            $contract = ContractApproval::where('contract_number', 'LIKE', "%{$args}%")
                ->orWhere('contract_title', 'LIKE', "%{$args}%")
                ->first();

            if (!$contract) {
                $this->sendMessage($chatId, "🔍 ไม่พบสัญญาที่ตรงกับ \"<b>{$args}</b>\"");
                return;
            }

            $this->sendContractDetail($chatId, $contract);
            return;
        }

        // /contract — summary
        $text = "📑 <b>Contract Management</b>\n\n";

        $statusCounts = ContractApproval::select('status', DB::raw('count(*) as cnt'))
            ->groupBy('status')->pluck('cnt', 'status')->toArray();

        $totalContracts = array_sum($statusCounts);
        $totalValue = ContractApproval::sum('contract_value');
        $text .= "📊 <b>สรุป</b> (ทั้งหมด {$totalContracts} สัญญา)\n";
        $text .= "💰 มูลค่ารวม: " . number_format($totalValue, 2) . " THB\n\n";

        $contractIcons = ['pending' => '⏳', 'under_review' => '🔍', 'approved' => '✅', 'rejected' => '❌', 'cancelled' => '🚫'];
        foreach ($statusCounts as $status => $cnt) {
            $icon = $contractIcons[$status] ?? '📄';
            $text .= "  {$icon} {$status}: {$cnt}\n";
        }

        // Pending/Under review contracts
        $pendingContracts = ContractApproval::whereIn('status', ['pending', 'under_review'])
            ->orderBy('created_at', 'desc')
            ->limit(10)->get();

        if ($pendingContracts->isNotEmpty()) {
            $text .= "\n⏳ <b>สัญญารอดำเนินการ</b>\n\n";
            foreach ($pendingContracts as $c) {
                $text .= "📄 <b>{$c->contract_number}</b>\n";
                $text .= "   📝 {$c->contract_title}\n";
                $text .= "   🏪 {$c->vendor_name} | 💰 " . number_format($c->contract_value ?? 0, 2) . " THB\n";
                $text .= "   📌 {$c->status_text} | {$c->priority_text}\n\n";
            }
        }

        // Expiring contracts (within 30 days)
        $expiringContracts = ContractApproval::where('status', 'approved')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now(), now()->addDays(30)])
            ->orderBy('end_date', 'asc')
            ->limit(5)->get();

        if ($expiringContracts->isNotEmpty()) {
            $text .= "⚠️ <b>สัญญาใกล้หมดอายุ (30 วัน)</b>\n";
            foreach ($expiringContracts as $c) {
                $daysLeft = now()->diffInDays($c->end_date);
                $text .= "  🟡 {$c->contract_number} - {$c->contract_title} (เหลือ {$daysLeft} วัน)\n";
            }
        }

        $text .= "\n🔍 ดูสัญญา: <code>/contract เลขสัญญา</code>";
        $this->sendMessage($chatId, $text);
    }

    protected function sendContractDetail(string $chatId, ContractApproval $contract): void
    {
        $dept = $contract->department->name ?? '-';
        $uploader = $contract->uploader->name ?? '-';
        $reviewer = $contract->reviewer->name ?? '-';

        $text = "📑 <b>รายละเอียดสัญญา</b>\n\n";
        $text .= "📋 เลขที่: <b>{$contract->contract_number}</b>\n";
        $text .= "📝 ชื่อ: {$contract->contract_title}\n";
        $text .= "📌 สถานะ: {$contract->status_text}\n";
        $text .= "📂 ประเภท: {$contract->contract_type_text}\n";
        $text .= "⚡ ความเร่งด่วน: {$contract->priority_text}\n";
        $text .= "🏪 คู่สัญญา: {$contract->vendor_name}\n";
        $text .= "💰 มูลค่า: " . number_format($contract->contract_value ?? 0, 2) . " {$contract->currency}\n";
        $text .= "🏢 แผนก: {$dept}\n";
        $text .= "👤 ผู้อัพโหลด: {$uploader}\n";

        if ($reviewer !== '-') {
            $text .= "👤 ผู้ตรวจสอบ: {$reviewer}\n";
        }

        $text .= "📅 วันสัญญา: " . ($contract->contract_date?->format('d/m/Y') ?? '-') . "\n";
        $text .= "📅 เริ่ม: " . ($contract->start_date?->format('d/m/Y') ?? '-') . "\n";
        $text .= "📅 สิ้นสุด: " . ($contract->end_date?->format('d/m/Y') ?? '-') . "\n";

        if ($contract->end_date && $contract->end_date->isFuture()) {
            $daysLeft = now()->diffInDays($contract->end_date);
            $text .= "⏳ เหลือ: {$daysLeft} วัน\n";
        }

        if ($contract->description) {
            $text .= "\n📄 รายละเอียด: " . mb_substr($contract->description, 0, 200) . "\n";
        }

        if ($contract->budget_code) {
            $text .= "🔖 รหัสงบ: {$contract->budget_code}\n";
        }
        if ($contract->project_code) {
            $text .= "🏗 รหัสโครงการ: {$contract->project_code}\n";
        }

        $this->sendMessage($chatId, $text);
    }

    // ==========================================
    // #4 /payment — Payment Milestone Tracking
    // ==========================================

    protected function handlePayment(string $chatId, string $args): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        if (!$this->isProcurementRole($user)) {
            $this->sendMessage($chatId, "⛔ คำสั่งนี้สำหรับเจ้าหน้าที่จัดซื้อขึ้นไป");
            return;
        }

        $text = "💳 <b>Payment Milestone Tracking</b>\n\n";

        // Summary
        $totalPending = PaymentMilestone::where('status', PaymentMilestone::STATUS_PENDING)->count();
        $totalOverdue = PaymentMilestone::where('status', PaymentMilestone::STATUS_PENDING)
            ->where('due_date', '<', now())->count();
        $totalDueSoon = PaymentMilestone::dueSoon(15)->count();
        $totalPaid = PaymentMilestone::where('status', PaymentMilestone::STATUS_PAID)->count();

        $pendingAmount = PaymentMilestone::where('status', PaymentMilestone::STATUS_PENDING)->sum('amount');
        $overdueAmount = PaymentMilestone::where('status', PaymentMilestone::STATUS_PENDING)
            ->where('due_date', '<', now())->sum('amount');

        $text .= "📊 <b>สรุป</b>\n";
        $text .= "  ⏳ รอชำระ: {$totalPending} งวด (" . number_format($pendingAmount, 2) . " THB)\n";
        $text .= "  🔴 เกินกำหนด: {$totalOverdue} งวด (" . number_format($overdueAmount, 2) . " THB)\n";
        $text .= "  🟡 ใกล้ถึงกำหนด: {$totalDueSoon} งวด\n";
        $text .= "  ✅ ชำระแล้ว: {$totalPaid} งวด\n\n";

        // Overdue payments
        $overduePayments = PaymentMilestone::where('status', PaymentMilestone::STATUS_PENDING)
            ->where('due_date', '<', now())
            ->with(['purchaseOrder'])
            ->orderBy('due_date', 'asc')
            ->limit(10)->get();

        if ($overduePayments->isNotEmpty()) {
            $text .= "🔴 <b>เกินกำหนดชำระ</b>\n\n";
            foreach ($overduePayments as $pm) {
                $poNumber = $pm->purchaseOrder->po_number ?? '-';
                $daysLate = now()->diffInDays($pm->due_date);
                $text .= "  🔴 <b>{$pm->milestone_title}</b>\n";
                $text .= "     PO: {$poNumber} | งวด {$pm->milestone_number}\n";
                $text .= "     💰 " . number_format($pm->amount ?? 0, 2) . " THB | เลย {$daysLate} วัน\n\n";
            }
        }

        // Due soon (next 15 days)
        $dueSoonPayments = PaymentMilestone::dueSoon(15)
            ->with(['purchaseOrder'])
            ->orderBy('due_date', 'asc')
            ->limit(10)->get();

        if ($dueSoonPayments->isNotEmpty()) {
            $text .= "🟡 <b>ใกล้ถึงกำหนด (15 วัน)</b>\n\n";
            foreach ($dueSoonPayments as $pm) {
                $poNumber = $pm->purchaseOrder->po_number ?? '-';
                $daysLeft = now()->diffInDays($pm->due_date);
                $text .= "  🟡 <b>{$pm->milestone_title}</b>\n";
                $text .= "     PO: {$poNumber} | งวด {$pm->milestone_number}\n";
                $text .= "     💰 " . number_format($pm->amount ?? 0, 2) . " THB | เหลือ {$daysLeft} วัน ({$pm->due_date->format('d/m/Y')})\n\n";
            }
        }

        // Monthly summary
        $paidThisMonth = PaymentMilestone::where('status', PaymentMilestone::STATUS_PAID)
            ->whereMonth('paid_date', now()->month)
            ->whereYear('paid_date', now()->year)
            ->sum('paid_amount');

        if ($paidThisMonth > 0) {
            $text .= "📅 <b>ยอดชำระเดือนนี้:</b> " . number_format($paidThisMonth, 2) . " THB\n";
        }

        $this->sendMessage($chatId, $text);
    }

    // ==========================================
    // #5 /calendar — Delivery & Milestone Calendar
    // ==========================================

    protected function handleCalendar(string $chatId, string $args): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        $args = trim($args);
        $days = 7;
        if ($args === 'month' || $args === '30') {
            $days = 30;
        } elseif (is_numeric($args) && (int)$args > 0) {
            $days = min((int)$args, 90);
        }

        $startDate = now()->startOfDay();
        $endDate = now()->addDays($days)->endOfDay();

        $text = "📅 <b>ปฏิทินส่งมอบ & Milestone</b>\n";
        $text .= "📆 " . $startDate->format('d/m/Y') . " - " . $endDate->format('d/m/Y') . " ({$days} วัน)\n\n";

        $hasContent = false;

        // PO Deliveries
        $deliveries = PurchaseOrder::whereIn('status', ['approved', 'sent_to_supplier', 'partially_received'])
            ->whereBetween('expected_delivery_date', [$startDate, $endDate])
            ->with(['vendor'])
            ->orderBy('expected_delivery_date', 'asc')
            ->get();

        if ($deliveries->isNotEmpty()) {
            $hasContent = true;
            $text .= "🚚 <b>กำหนดส่งมอบ PO ({$deliveries->count()} รายการ)</b>\n\n";
            foreach ($deliveries as $po) {
                $vendor = $po->vendor->company_name ?? $po->company_name ?? 'N/A';
                $date = $po->expected_delivery_date->format('d/m/Y');
                $daysLeft = now()->diffInDays($po->expected_delivery_date, false);
                $icon = $daysLeft <= 1 ? '🔴' : ($daysLeft <= 3 ? '🟡' : '🟢');
                $text .= "  {$icon} {$date} - <b>{$po->po_number}</b>\n";
                $text .= "     🏪 {$vendor} | 💰 " . number_format($po->total_amount ?? 0, 2) . "\n\n";
            }
        }

        // Payment milestones
        $payments = PaymentMilestone::where('status', PaymentMilestone::STATUS_PENDING)
            ->whereBetween('due_date', [$startDate, $endDate])
            ->with(['purchaseOrder'])
            ->orderBy('due_date', 'asc')
            ->get();

        if ($payments->isNotEmpty()) {
            $hasContent = true;
            $text .= "💳 <b>กำหนดชำระเงิน ({$payments->count()} งวด)</b>\n\n";
            foreach ($payments as $pm) {
                $poNumber = $pm->purchaseOrder->po_number ?? '-';
                $date = $pm->due_date->format('d/m/Y');
                $daysLeft = now()->diffInDays($pm->due_date, false);
                $icon = $daysLeft < 0 ? '🔴' : ($daysLeft <= 3 ? '🟡' : '🟢');
                $text .= "  {$icon} {$date} - <b>{$pm->milestone_title}</b>\n";
                $text .= "     PO: {$poNumber} | 💰 " . number_format($pm->amount ?? 0, 2) . "\n\n";
            }
        }

        // Contract expirations
        $contracts = ContractApproval::where('status', 'approved')
            ->whereBetween('end_date', [$startDate, $endDate])
            ->orderBy('end_date', 'asc')
            ->get();

        if ($contracts->isNotEmpty()) {
            $hasContent = true;
            $text .= "📑 <b>สัญญาหมดอายุ ({$contracts->count()} สัญญา)</b>\n\n";
            foreach ($contracts as $c) {
                $date = $c->end_date->format('d/m/Y');
                $text .= "  ⚠️ {$date} - <b>{$c->contract_number}</b>\n";
                $text .= "     {$c->contract_title} | {$c->vendor_name}\n\n";
            }
        }

        // PR Required dates
        $prDeadlines = PurchaseRequisition::whereIn('status', ['draft', 'pending_approval', 'approved'])
            ->whereBetween('required_date', [$startDate, $endDate])
            ->orderBy('required_date', 'asc')
            ->limit(10)->get();

        if ($prDeadlines->isNotEmpty()) {
            $hasContent = true;
            $text .= "📋 <b>PR ต้องการภายใน ({$prDeadlines->count()} ใบ)</b>\n\n";
            foreach ($prDeadlines as $pr) {
                $date = $pr->required_date->format('d/m/Y');
                $text .= "  📋 {$date} - {$pr->pr_number} ({$pr->title})\n";
            }
        }

        // Overdue items
        $overduePO = PurchaseOrder::whereIn('status', ['approved', 'sent_to_supplier', 'partially_received'])
            ->where('expected_delivery_date', '<', $startDate)
            ->count();
        $overduePayment = PaymentMilestone::where('status', PaymentMilestone::STATUS_PENDING)
            ->where('due_date', '<', $startDate)->count();

        if ($overduePO > 0 || $overduePayment > 0) {
            $hasContent = true;
            $text .= "\n🔴 <b>เกินกำหนด</b>\n";
            if ($overduePO > 0) $text .= "  📦 PO เลยกำหนดส่ง: {$overduePO} ใบ\n";
            if ($overduePayment > 0) $text .= "  💳 เลยกำหนดชำระ: {$overduePayment} งวด\n";
        }

        if (!$hasContent) {
            $text .= "✅ ไม่มีรายการในช่วงเวลานี้";
        }

        $text .= "\n\n💡 ดู 30 วัน: <code>/calendar 30</code>";
        $this->sendMessage($chatId, $text);
    }

    // ==========================================
    // #6 /vendorscore — Vendor Performance & Risk
    // ==========================================

    protected function handleVendorScore(string $chatId, string $args): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        if (!$this->isManagerRole($user)) {
            $this->sendMessage($chatId, "⛔ คำสั่งนี้สำหรับผู้จัดการจัดซื้อขึ้นไป");
            return;
        }

        $args = trim($args);
        $now = now();
        $year = $now->year;
        $quarter = ceil($now->month / 3);

        // /vendorscore <keyword> — search specific vendor
        if (!empty($args)) {
            $vendor = Vendor::where('company_name', 'LIKE', "%{$args}%")
                ->orWhere('tax_id', 'LIKE', "%{$args}%")
                ->first();

            if (!$vendor) {
                $this->sendMessage($chatId, "🔍 ไม่พบ Vendor \"<b>{$args}</b>\"");
                return;
            }

            $this->sendVendorScoreDetail($chatId, $vendor);
            return;
        }

        // /vendorscore — overview
        $text = "📊 <b>Vendor Performance & Risk</b>\n";
        $text .= "📅 Q{$quarter}/{$year}\n\n";

        // Top performers
        $topScores = VendorScore::forYear($year)
            ->topPerformers(5)
            ->with('vendor')
            ->get();

        if ($topScores->isNotEmpty()) {
            $text .= "🏆 <b>Top Vendors</b>\n\n";
            $rank = 1;
            foreach ($topScores as $score) {
                $vendorName = $score->vendor->company_name ?? 'N/A';
                $grade = $score->current_grade;
                $gradeDesc = $score->grade_description;
                $trendIcon = $score->trend_icon;
                $text .= "  {$rank}. <b>{$vendorName}</b>\n";
                $text .= "     Grade: {$grade} ({$gradeDesc}) {$trendIcon} | {$score->formatted_score}\n\n";
                $rank++;
            }
        }

        // Needs improvement
        $needsImprove = VendorScore::forYear($year)
            ->needImprovement()
            ->with('vendor')
            ->limit(5)->get();

        if ($needsImprove->isNotEmpty()) {
            $text .= "⚠️ <b>ต้องปรับปรุง</b>\n\n";
            foreach ($needsImprove as $score) {
                $vendorName = $score->vendor->company_name ?? 'N/A';
                $text .= "  ⚠️ <b>{$vendorName}</b>\n";
                $text .= "     Grade: {$score->current_grade} ({$score->grade_description}) | {$score->formatted_score}\n\n";
            }
        }

        // Recent assessments
        $recentAssessments = VendorAssessment::completed()
            ->latestFirst()
            ->with('vendor')
            ->limit(5)->get();

        if ($recentAssessments->isNotEmpty()) {
            $text .= "🔍 <b>การประเมินล่าสุด</b>\n";
            foreach ($recentAssessments as $a) {
                $vendorName = $a->vendor->company_name ?? 'N/A';
                $riskIcon = match ($a->overall_risk_level ?? $a->risk_level) {
                    'low' => '🟢', 'medium' => '🟡', 'high' => '🟠', 'critical' => '🔴', default => '⚪',
                };
                $text .= "  {$riskIcon} {$vendorName} - {$a->risk_level_label}\n";
            }
        }

        $text .= "\n🔍 ดู Vendor: <code>/vendorscore ชื่อบริษัท</code>";
        $this->sendMessage($chatId, $text);
    }

    protected function sendVendorScoreDetail(string $chatId, Vendor $vendor): void
    {
        $year = now()->year;

        $text = "📊 <b>Vendor Performance</b>\n\n";
        $text .= "🏪 <b>{$vendor->company_name}</b>\n";
        $text .= "📧 {$vendor->contact_email} | 📞 {$vendor->contact_phone}\n";
        $text .= "🔖 Tax ID: {$vendor->tax_id}\n\n";

        // Score
        $score = VendorScore::where('vendor_id', $vendor->id)
            ->forYear($year)
            ->orderBy('month', 'desc')
            ->first();

        if ($score) {
            $text .= "📈 <b>คะแนน (ปี {$year})</b>\n";
            $text .= "  Grade: <b>{$score->current_grade}</b> ({$score->grade_description})\n";
            $text .= "  Score: {$score->formatted_score}\n";
            $text .= "  Trend: {$score->trend_icon} ({$score->trend})\n";

            // Category breakdown
            if ($score->category_scores) {
                $text .= "\n📋 <b>คะแนนรายหมวด</b>\n";
                foreach ($score->category_scores as $cat => $catScore) {
                    $text .= "  • {$cat}: " . number_format($catScore, 2) . "/4.00\n";
                }
            }
            $text .= "\n";
        } else {
            $text .= "📈 ยังไม่มีคะแนนสำหรับปี {$year}\n\n";
        }

        // PO Statistics
        $poCount = $vendor->purchaseOrders()->count();
        $poTotal = $vendor->purchaseOrders()->sum('total_amount');
        $poActive = $vendor->purchaseOrders()->whereIn('status', ['approved', 'sent_to_supplier', 'partially_received'])->count();

        $text .= "📦 <b>สถิติ PO</b>\n";
        $text .= "  ทั้งหมด: {$poCount} ใบ | Active: {$poActive}\n";
        $text .= "  มูลค่ารวม: " . number_format($poTotal, 2) . " THB\n\n";

        // Latest risk assessment
        $assessment = VendorAssessment::forVendor($vendor->id)
            ->completed()
            ->latestFirst()
            ->first();

        if ($assessment) {
            $text .= "🛡️ <b>Risk Assessment</b>\n";
            $riskIcon = match ($assessment->overall_risk_level ?? $assessment->risk_level) {
                'low' => '🟢', 'medium' => '🟡', 'high' => '🟠', 'critical' => '🔴', default => '⚪',
            };
            $text .= "  {$riskIcon} Risk Level: {$assessment->risk_level_label}\n";
            $text .= "  Risk Score: {$assessment->overall_risk_score}/100\n";

            if ($assessment->ai_summary) {
                $text .= "  AI สรุป: " . mb_substr($assessment->ai_summary, 0, 200) . "\n";
            }
            if ($assessment->dbd_company_age) {
                $text .= "  อายุบริษัท: {$assessment->dbd_company_age}\n";
            }
        }

        $this->sendMessage($chatId, $text);
    }

    // ==========================================
    // #7 /sla — SLA Performance Report
    // ==========================================

    protected function handleSla(string $chatId): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        if (!$this->isManagerRole($user)) {
            $this->sendMessage($chatId, "⛔ คำสั่งนี้สำหรับผู้จัดการจัดซื้อขึ้นไป");
            return;
        }

        $text = "📊 <b>SLA Performance Report</b>\n";
        $text .= "📅 " . now()->locale('th')->translatedFormat('F Y') . "\n\n";

        // Grade distribution
        $gradeDistribution = SlaTracking::select('sla_grade', DB::raw('count(*) as cnt'))
            ->whereNotNull('sla_grade')
            ->groupBy('sla_grade')
            ->pluck('cnt', 'sla_grade')
            ->toArray();

        $totalTracked = array_sum($gradeDistribution);
        $text .= "📋 <b>สรุปเกรด SLA</b> (ทั้งหมด {$totalTracked} รายการ)\n";

        $gradeIcons = ['S' => '🌟', 'A' => '💚', 'B' => '💙', 'C' => '💛', 'D' => '🧡', 'F' => '❤️'];
        $gradeLabels = ['S' => 'Excellent', 'A' => 'Very Good', 'B' => 'Good', 'C' => 'Average', 'D' => 'Below Avg', 'F' => 'Fail'];

        foreach (['S', 'A', 'B', 'C', 'D', 'F'] as $grade) {
            $cnt = $gradeDistribution[$grade] ?? 0;
            if ($cnt > 0) {
                $icon = $gradeIcons[$grade];
                $label = $gradeLabels[$grade];
                $pct = $totalTracked > 0 ? round(($cnt / $totalTracked) * 100, 1) : 0;
                $text .= "  {$icon} Grade {$grade} ({$label}): {$cnt} ({$pct}%)\n";
            }
        }

        // Pass rate
        $passCount = ($gradeDistribution['S'] ?? 0) + ($gradeDistribution['A'] ?? 0) + ($gradeDistribution['B'] ?? 0);
        $passRate = $totalTracked > 0 ? round(($passCount / $totalTracked) * 100, 1) : 0;
        $text .= "\n✅ Pass Rate (S/A/B): <b>{$passRate}%</b>\n\n";

        // By stage breakdown
        $stages = SlaTracking::select('stage', DB::raw('count(*) as cnt'), DB::raw('AVG(sla_percentage) as avg_pct'), DB::raw('AVG(actual_working_days) as avg_days'))
            ->groupBy('stage')->get();

        if ($stages->isNotEmpty()) {
            $text .= "📊 <b>แยกตาม Stage</b>\n\n";
            foreach ($stages as $s) {
                $avgPct = round($s->avg_pct ?? 0, 1);
                $avgDays = round($s->avg_days ?? 0, 1);
                $icon = $avgPct <= 100 ? '🟢' : '🔴';
                $stageName = str_replace('_', ' ', ucfirst($s->stage));
                $text .= "  {$icon} <b>{$stageName}</b>\n";
                $text .= "     จำนวน: {$s->cnt} | เฉลี่ย: {$avgDays} วัน | SLA: {$avgPct}%\n\n";
            }
        }

        // At-risk items (SLA > 80%)
        $atRisk = SlaTracking::where('sla_percentage', '>', 80)
            ->whereNull('end_date')
            ->with(['purchaseRequisition', 'purchaseOrder'])
            ->limit(5)->get();

        if ($atRisk->isNotEmpty()) {
            $text .= "⚠️ <b>เสี่ยงเกิน SLA (>80%)</b>\n";
            foreach ($atRisk as $sla) {
                $ref = $sla->purchaseOrder?->po_number ?? $sla->purchaseRequisition?->pr_number ?? '-';
                $text .= "  🟡 {$ref} - {$sla->stage} ({$sla->sla_percentage}%)\n";
            }
        }

        $this->sendMessage($chatId, $text);
    }

    // ==========================================
    // #8 /report — Quick Report Generator
    // ==========================================

    protected function handleReport(string $chatId, string $args): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        if (!$this->isManagerRole($user)) {
            $this->sendMessage($chatId, "⛔ คำสั่งนี้สำหรับผู้จัดการจัดซื้อขึ้นไป");
            return;
        }

        $args = strtolower(trim($args));

        $validTypes = ['pr', 'po', 'payment', 'vendor', 'sla', 'monthly'];

        if (empty($args) || !in_array($args, $validTypes)) {
            $text = "📊 <b>Quick Report Generator</b>\n\n";
            $text .= "เลือกประเภทรายงาน:\n\n";
            $text .= "<code>/report pr</code> - สรุป PR ประจำเดือน\n";
            $text .= "<code>/report po</code> - สรุป PO ประจำเดือน\n";
            $text .= "<code>/report payment</code> - สรุปการชำระเงิน\n";
            $text .= "<code>/report vendor</code> - สรุปคะแนน Vendor\n";
            $text .= "<code>/report sla</code> - สรุป SLA\n";
            $text .= "<code>/report monthly</code> - สรุปรวมประจำเดือน\n";
            $this->sendMessage($chatId, $text);
            return;
        }

        $now = now();
        $monthLabel = $now->locale('th')->translatedFormat('F Y');

        match ($args) {
            'pr' => $this->generatePRReport($chatId, $now, $monthLabel),
            'po' => $this->generatePOReport($chatId, $now, $monthLabel),
            'payment' => $this->generatePaymentReport($chatId, $now, $monthLabel),
            'vendor' => $this->generateVendorReport($chatId, $now),
            'sla' => $this->handleSla($chatId),
            'monthly' => $this->generateMonthlyReport($chatId, $now, $monthLabel),
        };
    }

    protected function generatePRReport(string $chatId, $now, string $monthLabel): void
    {
        $text = "📊 <b>PR Report — {$monthLabel}</b>\n\n";

        $created = PurchaseRequisition::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count();
        $approved = PurchaseRequisition::where('status', 'approved')->whereMonth('approved_at', $now->month)->whereYear('approved_at', $now->year)->count();
        $rejected = PurchaseRequisition::where('status', 'rejected')->whereMonth('rejected_at', $now->month)->whereYear('rejected_at', $now->year)->count();
        $pending = PurchaseRequisition::where('status', 'pending_approval')->count();
        $totalAmount = PurchaseRequisition::whereIn('status', ['approved', 'in_process', 'completed'])
            ->whereMonth('approved_at', $now->month)->whereYear('approved_at', $now->year)->sum('total_amount');

        $text .= "📝 สร้างใหม่: {$created} ใบ\n";
        $text .= "✅ อนุมัติ: {$approved} ใบ\n";
        $text .= "❌ ปฏิเสธ: {$rejected} ใบ\n";
        $text .= "⏳ รออนุมัติ (ค้าง): {$pending} ใบ\n";
        $text .= "💰 มูลค่าอนุมัติ: " . number_format($totalAmount, 2) . " THB\n\n";

        // By priority
        $byPriority = PurchaseRequisition::select('priority', DB::raw('count(*) as cnt'))
            ->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)
            ->groupBy('priority')->pluck('cnt', 'priority')->toArray();

        if (!empty($byPriority)) {
            $text .= "⚡ <b>ตามความเร่งด่วน</b>\n";
            $priorityIcons = ['urgent' => '🔴', 'high' => '🟠', 'medium' => '🟡', 'low' => '🟢'];
            foreach ($byPriority as $p => $cnt) {
                $icon = $priorityIcons[$p] ?? '📄';
                $text .= "  {$icon} {$p}: {$cnt}\n";
            }
            $text .= "\n";
        }

        // By department
        $byDept = PurchaseRequisition::select('department_id', DB::raw('count(*) as cnt'), DB::raw('SUM(total_amount) as total'))
            ->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)
            ->groupBy('department_id')
            ->with('department')
            ->orderByDesc('total')
            ->limit(5)->get();

        if ($byDept->isNotEmpty()) {
            $text .= "🏢 <b>Top แผนก</b>\n";
            foreach ($byDept as $d) {
                $deptName = $d->department->name ?? 'N/A';
                $text .= "  🏢 {$deptName}: {$d->cnt} ใบ (" . number_format($d->total ?? 0, 2) . ")\n";
            }
        }

        $this->sendMessage($chatId, $text);
    }

    protected function generatePOReport(string $chatId, $now, string $monthLabel): void
    {
        $text = "📊 <b>PO Report — {$monthLabel}</b>\n\n";

        $created = PurchaseOrder::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count();
        $approved = PurchaseOrder::where('status', 'approved')->whereMonth('approved_at', $now->month)->whereYear('approved_at', $now->year)->count();
        $sent = PurchaseOrder::whereIn('status', ['sent_to_supplier', 'acknowledged'])->count();
        $received = PurchaseOrder::whereIn('status', ['fully_received', 'closed'])
            ->whereMonth('updated_at', $now->month)->whereYear('updated_at', $now->year)->count();
        $totalAmount = PurchaseOrder::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->sum('total_amount');

        $text .= "📝 สร้างใหม่: {$created} ใบ\n";
        $text .= "✅ อนุมัติ: {$approved} ใบ\n";
        $text .= "📤 ส่ง Vendor: {$sent} ใบ\n";
        $text .= "📦 ได้รับแล้ว: {$received} ใบ\n";
        $text .= "💰 มูลค่า: " . number_format($totalAmount, 2) . " THB\n\n";

        // Top vendors by amount
        $topVendors = PurchaseOrder::select('vendor_id', DB::raw('count(*) as cnt'), DB::raw('SUM(total_amount) as total'))
            ->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)
            ->whereNotNull('vendor_id')
            ->groupBy('vendor_id')
            ->orderByDesc('total')
            ->limit(5)->get();

        if ($topVendors->isNotEmpty()) {
            $text .= "🏪 <b>Top Vendors</b>\n";
            foreach ($topVendors as $v) {
                $vendor = Vendor::find($v->vendor_id);
                $vendorName = $vendor->company_name ?? 'N/A';
                $text .= "  🏪 {$vendorName}: {$v->cnt} PO (" . number_format($v->total ?? 0, 2) . ")\n";
            }
        }

        $this->sendMessage($chatId, $text);
    }

    protected function generatePaymentReport(string $chatId, $now, string $monthLabel): void
    {
        $text = "📊 <b>Payment Report — {$monthLabel}</b>\n\n";

        $totalPaid = PaymentMilestone::where('status', PaymentMilestone::STATUS_PAID)
            ->whereMonth('paid_date', $now->month)->whereYear('paid_date', $now->year)->count();
        $paidAmount = PaymentMilestone::where('status', PaymentMilestone::STATUS_PAID)
            ->whereMonth('paid_date', $now->month)->whereYear('paid_date', $now->year)->sum('paid_amount');
        $pendingCount = PaymentMilestone::where('status', PaymentMilestone::STATUS_PENDING)->count();
        $pendingAmount = PaymentMilestone::where('status', PaymentMilestone::STATUS_PENDING)->sum('amount');
        $overdueCount = PaymentMilestone::where('status', PaymentMilestone::STATUS_PENDING)->where('due_date', '<', now())->count();
        $overdueAmount = PaymentMilestone::where('status', PaymentMilestone::STATUS_PENDING)->where('due_date', '<', now())->sum('amount');

        $text .= "✅ ชำระเดือนนี้: {$totalPaid} งวด (" . number_format($paidAmount, 2) . " THB)\n";
        $text .= "⏳ รอชำระ: {$pendingCount} งวด (" . number_format($pendingAmount, 2) . " THB)\n";
        $text .= "🔴 เกินกำหนด: {$overdueCount} งวด (" . number_format($overdueAmount, 2) . " THB)\n";

        $this->sendMessage($chatId, $text);
    }

    protected function generateVendorReport(string $chatId, $now): void
    {
        $text = "📊 <b>Vendor Performance Report</b>\n\n";

        $totalVendors = Vendor::count();
        $activeVendors = Vendor::where('status', 'approved')->count();

        $text .= "🏪 Vendor ทั้งหมด: {$totalVendors} | Active: {$activeVendors}\n\n";

        // Grade distribution
        $gradeDistribution = VendorScore::forYear($now->year)
            ->select('weighted_grade', DB::raw('count(DISTINCT vendor_id) as cnt'))
            ->whereNotNull('weighted_grade')
            ->groupBy('weighted_grade')
            ->pluck('cnt', 'weighted_grade')->toArray();

        if (!empty($gradeDistribution)) {
            $text .= "📊 <b>การกระจายเกรด</b>\n";
            foreach (['A', 'B', 'C', 'D'] as $g) {
                $cnt = $gradeDistribution[$g] ?? 0;
                if ($cnt > 0) {
                    $icon = match ($g) { 'A' => '💚', 'B' => '💙', 'C' => '💛', 'D' => '🧡' };
                    $text .= "  {$icon} Grade {$g}: {$cnt} ราย\n";
                }
            }
            $text .= "\n";
        }

        // Recent assessments
        $recentCount = VendorAssessment::completed()
            ->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)
            ->count();

        $highRisk = VendorAssessment::completed()
            ->whereIn('overall_risk_level', ['high', 'critical'])
            ->count();

        $text .= "🔍 ประเมินเดือนนี้: {$recentCount} ราย\n";
        $text .= "🔴 ความเสี่ยงสูง/วิกฤต: {$highRisk} ราย\n";

        $this->sendMessage($chatId, $text);
    }

    protected function generateMonthlyReport(string $chatId, $now, string $monthLabel): void
    {
        $text = "📊 <b>สรุปรวมประจำเดือน — {$monthLabel}</b>\n\n";

        // PR
        $prCreated = PurchaseRequisition::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count();
        $prApproved = PurchaseRequisition::where('status', 'approved')->whereMonth('approved_at', $now->month)->whereYear('approved_at', $now->year)->count();
        $prAmount = PurchaseRequisition::whereIn('status', ['approved', 'in_process', 'completed'])
            ->whereMonth('approved_at', $now->month)->whereYear('approved_at', $now->year)->sum('total_amount');

        $text .= "📋 <b>PR</b>\n";
        $text .= "  สร้าง: {$prCreated} | อนุมัติ: {$prApproved}\n";
        $text .= "  มูลค่า: " . number_format($prAmount, 2) . " THB\n\n";

        // PO
        $poCreated = PurchaseOrder::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count();
        $poAmount = PurchaseOrder::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->sum('total_amount');

        $text .= "📦 <b>PO</b>\n";
        $text .= "  สร้าง: {$poCreated} ใบ\n";
        $text .= "  มูลค่า: " . number_format($poAmount, 2) . " THB\n\n";

        // GR
        $grCompleted = GoodsReceipt::where('status', GoodsReceipt::STATUS_COMPLETED)
            ->whereMonth('updated_at', $now->month)->whereYear('updated_at', $now->year)->count();
        $grPending = GoodsReceipt::where('inspection_status', GoodsReceipt::INSPECTION_PENDING)->count();

        $text .= "📋 <b>GR</b>\n";
        $text .= "  ตรวจรับเสร็จ: {$grCompleted} | รอตรวจ: {$grPending}\n\n";

        // Payment
        $paidAmount = PaymentMilestone::where('status', PaymentMilestone::STATUS_PAID)
            ->whereMonth('paid_date', $now->month)->whereYear('paid_date', $now->year)->sum('paid_amount');
        $overdueCount = PaymentMilestone::where('status', PaymentMilestone::STATUS_PENDING)->where('due_date', '<', now())->count();

        $text .= "💳 <b>การชำระเงิน</b>\n";
        $text .= "  ชำระเดือนนี้: " . number_format($paidAmount, 2) . " THB\n";
        $text .= "  เกินกำหนด: {$overdueCount} งวด\n\n";

        // Contracts
        $activeContracts = ContractApproval::where('status', 'approved')->count();
        $expiringContracts = ContractApproval::where('status', 'approved')
            ->whereBetween('end_date', [now(), now()->addDays(30)])->count();

        $text .= "📑 <b>สัญญา</b>\n";
        $text .= "  Active: {$activeContracts} | ใกล้หมดอายุ: {$expiringContracts}\n\n";

        // SLA
        $passCount = SlaTracking::whereIn('sla_grade', ['S', 'A', 'B'])->count();
        $totalSla = SlaTracking::whereNotNull('sla_grade')->count();
        $passRate = $totalSla > 0 ? round(($passCount / $totalSla) * 100, 1) : 0;

        $text .= "📊 <b>SLA</b>\n";
        $text .= "  Pass Rate: {$passRate}%\n\n";

        // Anomalies
        $openAnomalies = ProcurementAnomaly::where('status', 'open')->count();
        $criticalAnomalies = ProcurementAnomaly::where('status', 'open')->where('severity', 'critical')->count();

        $text .= "🛡️ <b>Anomaly</b>\n";
        $text .= "  Open: {$openAnomalies} | Critical: {$criticalAnomalies}\n";

        $this->sendMessage($chatId, $text);
    }

    // ==========================================
    // #9 /ask — AI Procurement Assistant
    // ==========================================

    protected function handleAsk(string $chatId, string $args): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        $question = trim($args);

        if (empty($question)) {
            $text = "🤖 <b>AI Procurement Assistant</b>\n\n";
            $text .= "ถามคำถามเกี่ยวกับข้อมูลจัดซื้อได้เลย\n\n";
            $text .= "ตัวอย่าง:\n";
            $text .= "<code>/ask PR ที่รออนุมัตินานสุดคืออะไร</code>\n";
            $text .= "<code>/ask สรุปค่าใช้จ่ายเดือนนี้</code>\n";
            $text .= "<code>/ask vendor ไหนมี PO มากสุด</code>\n";
            $text .= "<code>/ask PO ที่ใกล้ครบกำหนดส่ง</code>\n";
            $text .= "<code>/ask มีกี่ PR ที่ rejected</code>\n";
            $this->sendMessage($chatId, $text);
            return;
        }

        $this->sendMessage($chatId, "🤖 กำลังวิเคราะห์คำถาม...");

        // Simple keyword-based NLP to route to the right data
        $answer = $this->processAskQuery($question, $user);
        $this->sendMessage($chatId, $answer);
    }

    protected function processAskQuery(string $question, User $user): string
    {
        $q = mb_strtolower($question);
        $now = now();

        // PR-related queries
        if (Str::contains($q, ['pr', 'ใบขอ', 'purchase requisition'])) {
            if (Str::contains($q, ['รอ', 'pending', 'อนุมัติ', 'ค้าง'])) {
                $pending = PurchaseRequisition::where('status', 'pending_approval')
                    ->orderBy('submitted_at', 'asc')->get();

                if ($pending->isEmpty()) return "✅ ไม่มี PR ที่รออนุมัติ";

                $oldest = $pending->first();
                $oldestDays = $oldest->submitted_at ? now()->diffInDays($oldest->submitted_at) : '?';
                $text = "📋 <b>PR รออนุมัติ: {$pending->count()} ใบ</b>\n\n";
                $text .= "⏳ PR ค้างนานสุด: {$oldest->pr_number} ({$oldestDays} วัน)\n\n";
                foreach ($pending->take(5) as $pr) {
                    $days = $pr->submitted_at ? now()->diffInDays($pr->submitted_at) : '?';
                    $text .= "  📋 {$pr->pr_number} - {$pr->title} (รอ {$days} วัน)\n";
                }
                return $text;
            }

            if (Str::contains($q, ['reject', 'ปฏิเสธ', 'ไม่อนุมัติ'])) {
                $rejected = PurchaseRequisition::where('status', 'rejected')
                    ->orderBy('rejected_at', 'desc')->limit(10)->get();
                $text = "❌ <b>PR ที่ถูกปฏิเสธ: {$rejected->count()} ใบ (ล่าสุด 10)</b>\n\n";
                foreach ($rejected as $pr) {
                    $text .= "  ❌ {$pr->pr_number} - {$pr->title}\n";
                    if ($pr->rejection_reason) $text .= "     เหตุผล: " . mb_substr($pr->rejection_reason, 0, 80) . "\n";
                }
                return $text;
            }

            if (Str::contains($q, ['กี่', 'จำนวน', 'count', 'เท่าไหร่'])) {
                $total = PurchaseRequisition::count();
                $thisMonth = PurchaseRequisition::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count();
                return "📋 PR ทั้งหมด: {$total} ใบ\nเดือนนี้: {$thisMonth} ใบ";
            }
        }

        // PO-related queries
        if (Str::contains($q, ['po', 'purchase order', 'ใบสั่งซื้อ'])) {
            if (Str::contains($q, ['ครบกำหนด', 'ส่ง', 'delivery', 'ใกล้'])) {
                $upcoming = PurchaseOrder::whereIn('status', ['approved', 'sent_to_supplier', 'partially_received'])
                    ->whereNotNull('expected_delivery_date')
                    ->whereBetween('expected_delivery_date', [now(), now()->addDays(7)])
                    ->orderBy('expected_delivery_date')->get();

                if ($upcoming->isEmpty()) return "✅ ไม่มี PO ที่ใกล้ครบกำหนดส่ง (7 วัน)";

                $text = "📦 <b>PO ใกล้ครบกำหนดส่ง (7 วัน): {$upcoming->count()} ใบ</b>\n\n";
                foreach ($upcoming as $po) {
                    $vendor = $po->vendor->company_name ?? 'N/A';
                    $days = now()->diffInDays($po->expected_delivery_date);
                    $text .= "  📦 {$po->po_number} - {$vendor} (เหลือ {$days} วัน)\n";
                }
                return $text;
            }
        }

        // Vendor queries
        if (Str::contains($q, ['vendor', 'ผู้ขาย', 'ซัพพลาย', 'supplier'])) {
            if (Str::contains($q, ['มากสุด', 'top', 'อันดับ'])) {
                $topVendors = PurchaseOrder::select('vendor_id', DB::raw('count(*) as cnt'), DB::raw('SUM(total_amount) as total'))
                    ->whereNotNull('vendor_id')
                    ->groupBy('vendor_id')
                    ->orderByDesc('total')
                    ->limit(5)->get();

                $text = "🏪 <b>Top Vendor (มูลค่า PO)</b>\n\n";
                $rank = 1;
                foreach ($topVendors as $v) {
                    $vendor = Vendor::find($v->vendor_id);
                    $text .= "  {$rank}. {$vendor->company_name}\n";
                    $text .= "     PO: {$v->cnt} ใบ | " . number_format($v->total ?? 0, 2) . " THB\n\n";
                    $rank++;
                }
                return $text;
            }
        }

        // Spending / budget queries
        if (Str::contains($q, ['ค่าใช้จ่าย', 'spending', 'งบ', 'budget', 'เงิน', 'สรุป'])) {
            $prAmount = PurchaseRequisition::whereIn('status', ['approved', 'in_process', 'completed'])
                ->whereMonth('approved_at', $now->month)->whereYear('approved_at', $now->year)
                ->sum('total_amount');
            $poAmount = PurchaseOrder::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)
                ->sum('total_amount');
            $paidAmount = PaymentMilestone::where('status', PaymentMilestone::STATUS_PAID)
                ->whereMonth('paid_date', $now->month)->whereYear('paid_date', $now->year)
                ->sum('paid_amount');

            $text = "💰 <b>สรุปค่าใช้จ่ายเดือนนี้</b>\n\n";
            $text .= "📋 มูลค่า PR อนุมัติ: " . number_format($prAmount, 2) . " THB\n";
            $text .= "📦 มูลค่า PO: " . number_format($poAmount, 2) . " THB\n";
            $text .= "💳 ชำระแล้ว: " . number_format($paidAmount, 2) . " THB\n";
            return $text;
        }

        // Overdue queries
        if (Str::contains($q, ['เกินกำหนด', 'overdue', 'ล่าช้า', 'late'])) {
            $overduePR = PurchaseRequisition::where('status', 'pending_approval')
                ->where('submitted_at', '<=', now()->subDays(3))->count();
            $overduePO = PurchaseOrder::whereIn('status', ['approved', 'sent_to_supplier', 'partially_received'])
                ->where('expected_delivery_date', '<', now())->count();
            $overduePay = PaymentMilestone::where('status', PaymentMilestone::STATUS_PENDING)
                ->where('due_date', '<', now())->count();

            $text = "⏰ <b>สรุปรายการเกินกำหนด</b>\n\n";
            $text .= "📋 PR รออนุมัติ >3 วัน: {$overduePR} ใบ\n";
            $text .= "📦 PO เลยกำหนดส่ง: {$overduePO} ใบ\n";
            $text .= "💳 เลยกำหนดชำระ: {$overduePay} งวด\n";
            return $text;
        }

        // Default: quick stats
        $text = "🤖 <b>สรุปข้อมูลเบื้องต้น</b>\n\n";
        $text .= "📋 PR ทั้งหมด: " . PurchaseRequisition::count() . " ใบ\n";
        $text .= "📦 PO ทั้งหมด: " . PurchaseOrder::count() . " ใบ\n";
        $text .= "🏪 Vendor: " . Vendor::count() . " ราย\n";
        $text .= "⏳ PR รออนุมัติ: " . PurchaseRequisition::where('status', 'pending_approval')->count() . " ใบ\n\n";
        $text .= "💡 ลองถามให้เจาะจงกว่านี้:\n";
        $text .= "  • <code>/ask PR ที่รออนุมัตินานสุด</code>\n";
        $text .= "  • <code>/ask vendor ไหนมี PO มากสุด</code>\n";
        $text .= "  • <code>/ask มี PO เกินกำหนดส่งกี่ใบ</code>\n";
        return $text;
    }

    // ==========================================
    // #10 /notify — Smart Notification Preferences
    // ==========================================

    protected function handleNotify(string $chatId, string $args): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        $args = strtolower(trim($args));

        // Get current preferences
        $prefs = Cache::get("tg_notify_prefs:{$user->id}", [
            'pr_submitted' => true,
            'pr_approved' => true,
            'pr_rejected' => true,
            'po_delivery' => true,
            'payment_due' => true,
            'anomaly_alert' => true,
            'daily_digest' => false,
            'quiet_hours' => false,
        ]);

        if (empty($args)) {
            $text = "🔔 <b>การตั้งค่าแจ้งเตือน</b>\n\n";
            $text .= "สถานะปัจจุบัน:\n\n";

            $labels = [
                'pr_submitted' => '📋 PR ส่งอนุมัติ',
                'pr_approved' => '✅ PR อนุมัติแล้ว',
                'pr_rejected' => '❌ PR ปฏิเสธ',
                'po_delivery' => '🚚 PO ใกล้ครบกำหนดส่ง',
                'payment_due' => '💳 ใกล้ถึงกำหนดชำระ',
                'anomaly_alert' => '🛡️ แจ้งเตือนความผิดปกติ',
                'daily_digest' => '📊 สรุปรายวัน',
                'quiet_hours' => '🌙 Quiet Hours (22:00-07:00)',
            ];

            foreach ($prefs as $key => $enabled) {
                $icon = $enabled ? '✅' : '❌';
                $label = $labels[$key] ?? $key;
                $text .= "  {$icon} {$label}\n";
            }

            $text .= "\n<b>เปลี่ยนค่า:</b>\n";
            $text .= "<code>/notify on pr_submitted</code> - เปิด\n";
            $text .= "<code>/notify off daily_digest</code> - ปิด\n";
            $text .= "<code>/notify all</code> - เปิดทั้งหมด\n";
            $text .= "<code>/notify mute</code> - ปิดทั้งหมด\n";

            $this->sendMessage($chatId, $text);
            return;
        }

        // Parse command
        $parts = explode(' ', $args, 2);
        $action = $parts[0];
        $target = $parts[1] ?? '';

        if ($action === 'all') {
            foreach ($prefs as $key => &$val) { $val = true; }
            Cache::put("tg_notify_prefs:{$user->id}", $prefs, 86400 * 365);
            $this->sendMessage($chatId, "✅ เปิดการแจ้งเตือนทั้งหมดแล้ว");
            return;
        }

        if ($action === 'mute') {
            foreach ($prefs as $key => &$val) { $val = false; }
            Cache::put("tg_notify_prefs:{$user->id}", $prefs, 86400 * 365);
            $this->sendMessage($chatId, "🔇 ปิดการแจ้งเตือนทั้งหมดแล้ว\n\nพิมพ์ <code>/notify all</code> เพื่อเปิดใหม่");
            return;
        }

        if (in_array($action, ['on', 'off']) && !empty($target)) {
            if (!array_key_exists($target, $prefs)) {
                $this->sendMessage($chatId, "❌ ไม่รู้จักตัวเลือก \"{$target}\"\n\nพิมพ์ /notify เพื่อดูตัวเลือกทั้งหมด");
                return;
            }

            $prefs[$target] = ($action === 'on');
            Cache::put("tg_notify_prefs:{$user->id}", $prefs, 86400 * 365);

            $status = $action === 'on' ? '✅ เปิด' : '❌ ปิด';
            $this->sendMessage($chatId, "{$status} การแจ้งเตือน \"{$target}\" แล้ว");
            return;
        }

        $this->sendMessage($chatId, "❓ คำสั่งไม่ถูกต้อง\n\nพิมพ์ /notify เพื่อดูวิธีใช้");
    }

    // ==========================================
    // Chat Mode — Natural Language (Read-Only)
    // ==========================================

    protected function handleNaturalChat(string $chatId, string $text): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        // Detect intent and route to the right handler
        $q = mb_strtolower($text);

        // Greetings
        if ($this->isGreeting($q)) {
            $hour = (int) now()->format('H');
            $greeting = match (true) {
                $hour < 12 => 'สวัสดีตอนเช้า',
                $hour < 17 => 'สวัสดีตอนบ่าย',
                default => 'สวัสดีตอนเย็น',
            };

            $pendingPR = PurchaseRequisition::where('status', 'pending_approval')->count();
            $overduePO = PurchaseOrder::whereIn('status', ['approved', 'sent_to_supplier', 'partially_received'])
                ->where('expected_delivery_date', '<', now())->count();
            $overduePayment = PaymentMilestone::where('status', PaymentMilestone::STATUS_PENDING)
                ->where('due_date', '<', now())->count();

            $text = "👋 {$greeting}คุณ <b>{$user->name}</b>!\n\n";
            $text .= "📊 <b>สถานการณ์ตอนนี้:</b>\n";
            $text .= "  ⏳ PR รออนุมัติ: {$pendingPR} ใบ\n";
            if ($overduePO > 0) $text .= "  🔴 PO เลยกำหนดส่ง: {$overduePO} ใบ\n";
            if ($overduePayment > 0) $text .= "  🔴 เลยกำหนดชำระ: {$overduePayment} งวด\n";
            if ($overduePO === 0 && $overduePayment === 0) $text .= "  ✅ ไม่มีรายการเกินกำหนด\n";

            $text .= "\n💬 ถามอะไรได้เลยครับ หรือพิมพ์ /help ดูคำสั่ง";
            $this->sendMessage($chatId, $text);
            return;
        }

        // Thanks
        if ($this->isThanks($q)) {
            $this->sendMessage($chatId, "ยินดีครับ! มีอะไรให้ช่วยอีก ถามได้เลย 😊");
            return;
        }

        // Process wants to create/edit/delete → redirect to proper commands
        if ($this->isWriteIntent($q)) {
            $this->handleWriteIntentRedirect($chatId, $q);
            return;
        }

        // Summary / Today / Overview
        if ($this->isSummaryIntent($q)) {
            $this->chatDailySummary($chatId, $user);
            return;
        }

        // Recommendations / Advice
        if ($this->isAdviceIntent($q)) {
            $this->chatRecommendations($chatId, $user);
            return;
        }

        // Process explanation / How-to
        if ($this->isHowToIntent($q)) {
            $this->chatProcessExplain($chatId, $q);
            return;
        }

        // Comparison / Analytics
        if ($this->isCompareIntent($q)) {
            $this->chatComparison($chatId, $q);
            return;
        }

        // Fall through to expanded query processor
        $answer = $this->processNaturalQuery($q, $user);
        $this->sendMessage($chatId, $answer);
    }

    // ==========================================
    // Chat Intent Detection
    // ==========================================

    protected function isGreeting(string $q): bool
    {
        $greetings = ['สวัสดี', 'หวัดดี', 'ดีครับ', 'ดีค่ะ', 'hello', 'hi', 'hey',
            'อรุณสวัสดิ์', 'สวัสดีตอนเช้า', 'ดี', 'เฮ้', 'เฮลโล', 'ว่าไง'];
        foreach ($greetings as $g) {
            if (Str::contains($q, $g)) return true;
        }
        return false;
    }

    protected function isThanks(string $q): bool
    {
        $thanks = ['ขอบคุณ', 'ขอบใจ', 'thanks', 'thank you', 'thx', 'เยี่ยม', 'ดีมาก', 'เจ๋ง'];
        foreach ($thanks as $t) {
            if (Str::contains($q, $t)) return true;
        }
        return false;
    }

    protected function isWriteIntent(string $q): bool
    {
        $writeWords = ['สร้าง', 'เพิ่ม', 'แก้ไข', 'ลบ', 'อนุมัติ', 'ปฏิเสธ',
            'create', 'add', 'edit', 'delete', 'approve', 'reject',
            'ทำใบ', 'ออกใบ', 'เปิดใบ'];
        foreach ($writeWords as $w) {
            if (Str::contains($q, $w)) return true;
        }
        return false;
    }

    protected function isSummaryIntent(string $q): bool
    {
        $summaryWords = ['สรุป', 'ภาพรวม', 'overview', 'summary', 'วันนี้',
            'ตอนนี้', 'สถานการณ์', 'เป็นยังไง', 'เป็นไง', 'update',
            'อัพเดต', 'status', 'มีอะไร', 'ต้องทำอะไร', 'งานวันนี้',
            'today', 'สถานะ'];
        foreach ($summaryWords as $w) {
            if (Str::contains($q, $w)) return true;
        }
        return false;
    }

    protected function isAdviceIntent(string $q): bool
    {
        $adviceWords = ['แนะนำ', 'ควร', 'น่าจะ', 'ช่วยดู', 'วิเคราะห์',
            'suggest', 'recommend', 'advice', 'เร่ง', 'ระวัง',
            'น่าเป็นห่วง', 'prioritize', 'priority', 'จัดลำดับ'];
        foreach ($adviceWords as $w) {
            if (Str::contains($q, $w)) return true;
        }
        return false;
    }

    protected function isHowToIntent(string $q): bool
    {
        $howToWords = ['ยังไง', 'อย่างไร', 'ขั้นตอน', 'วิธี', 'how',
            'คืออะไร', 'หมายถึง', 'what is', 'อธิบาย', 'explain',
            'คำนวณ', 'ทำงานยังไง', 'process', 'flow'];
        foreach ($howToWords as $w) {
            if (Str::contains($q, $w)) return true;
        }
        return false;
    }

    protected function isCompareIntent(string $q): bool
    {
        $compareWords = ['เทียบ', 'เปรียบเทียบ', 'compare', 'vs', 'กับ',
            'ต่างกัน', 'มากกว่า', 'น้อยกว่า', 'สูงสุด', 'ต่ำสุด',
            'อันดับ', 'ranking', 'top', 'bottom'];
        foreach ($compareWords as $w) {
            if (Str::contains($q, $w)) return true;
        }
        return false;
    }

    // ==========================================
    // Chat Handlers (Read-Only)
    // ==========================================

    protected function handleWriteIntentRedirect(string $chatId, string $q): void
    {
        $text = "📝 <b>ต้องการดำเนินการ?</b>\n\n";
        $text .= "เพื่อความถูกต้องของข้อมูล กรุณาใช้คำสั่งเฉพาะ:\n\n";

        if (Str::contains($q, ['pr', 'ใบขอ', 'purchase requisition', 'ใบ pr'])) {
            $text .= "📋 สร้างใบ PR: /newpr\n";
            $text .= "📋 ดู PR ของฉัน: /mypr\n";
        } elseif (Str::contains($q, ['po', 'ใบสั่งซื้อ', 'purchase order'])) {
            $text .= "📦 จัดการ PO: /po\n";
        } elseif (Str::contains($q, ['gr', 'ตรวจรับ', 'goods receipt', 'รับของ'])) {
            $text .= "📋 ตรวจรับ: /gr\n";
        } elseif (Str::contains($q, ['อนุมัติ', 'approve', 'reject', 'ปฏิเสธ'])) {
            $text .= "✅ ดูรายการรออนุมัติ: /status\n";
        } elseif (Str::contains($q, ['สัญญา', 'contract'])) {
            $text .= "📑 จัดการสัญญา: /contract\n";
        } else {
            $text .= "📋 สร้างใบ PR: /newpr\n";
            $text .= "✅ อนุมัติ PR: /status\n";
            $text .= "📦 จัดการ PO: /po\n";
        }

        $text .= "\n💬 ถ้าแค่อยากดูข้อมูล ถามได้เลยครับ!";
        $this->sendMessage($chatId, $text);
    }

    protected function chatDailySummary(string $chatId, User $user): void
    {
        $now = now();
        $text = "📊 <b>สรุปสถานการณ์วันนี้</b>\n";
        $text .= "📅 " . $now->locale('th')->translatedFormat('l d F Y') . "\n\n";

        // Urgent matters
        $urgentItems = [];

        // Overdue PRs
        $overduePRs = PurchaseRequisition::where('status', 'pending_approval')
            ->where(function ($q) {
                $q->where('submitted_at', '<=', now()->subDays(3))
                  ->orWhere(function ($q2) {
                      $q2->whereNull('submitted_at')
                          ->where('updated_at', '<=', now()->subDays(3));
                  });
            })->count();
        if ($overduePRs > 0) $urgentItems[] = "🔴 PR รออนุมัติ >3 วัน: {$overduePRs} ใบ";

        // Overdue POs
        $overduePOs = PurchaseOrder::whereIn('status', ['approved', 'sent_to_supplier', 'partially_received'])
            ->where('expected_delivery_date', '<', now())->count();
        if ($overduePOs > 0) $urgentItems[] = "🔴 PO เลยกำหนดส่ง: {$overduePOs} ใบ";

        // Overdue payments
        $overduePayments = PaymentMilestone::where('status', PaymentMilestone::STATUS_PENDING)
            ->where('due_date', '<', now())->count();
        if ($overduePayments > 0) $urgentItems[] = "🔴 เลยกำหนดชำระ: {$overduePayments} งวด";

        // Expiring contracts
        $expiringContracts = ContractApproval::where('status', 'approved')
            ->whereBetween('end_date', [now(), now()->addDays(7)])->count();
        if ($expiringContracts > 0) $urgentItems[] = "🟡 สัญญาหมดอายุใน 7 วัน: {$expiringContracts}";

        if (!empty($urgentItems)) {
            $text .= "🚨 <b>ต้องดำเนินการ</b>\n";
            foreach ($urgentItems as $item) {
                $text .= "  {$item}\n";
            }
            $text .= "\n";
        } else {
            $text .= "✅ <b>ไม่มีรายการเร่งด่วน</b>\n\n";
        }

        // Today's numbers
        $prPending = PurchaseRequisition::where('status', 'pending_approval')->count();
        $prToday = PurchaseRequisition::whereDate('created_at', now())->count();
        $poActive = PurchaseOrder::whereIn('status', ['approved', 'sent_to_supplier', 'partially_received'])->count();

        $text .= "📋 <b>ตัวเลขวันนี้</b>\n";
        $text .= "  📋 PR รออนุมัติ: {$prPending}\n";
        $text .= "  📝 PR สร้างวันนี้: {$prToday}\n";
        $text .= "  📦 PO Active: {$poActive}\n\n";

        // Upcoming this week
        $deliveriesThisWeek = PurchaseOrder::whereIn('status', ['approved', 'sent_to_supplier', 'partially_received'])
            ->whereBetween('expected_delivery_date', [now(), now()->addDays(7)])->count();
        $paymentsThisWeek = PaymentMilestone::where('status', PaymentMilestone::STATUS_PENDING)
            ->whereBetween('due_date', [now(), now()->addDays(7)])->count();

        $text .= "📅 <b>สัปดาห์นี้</b>\n";
        $text .= "  🚚 PO ครบกำหนดส่ง: {$deliveriesThisWeek}\n";
        $text .= "  💳 งวดชำระ: {$paymentsThisWeek}\n\n";

        // Financial this month
        $monthlySpend = PurchaseRequisition::whereIn('status', ['approved', 'in_process', 'completed'])
            ->whereMonth('approved_at', $now->month)->whereYear('approved_at', $now->year)
            ->sum('total_amount');

        $text .= "💰 <b>งบเดือนนี้</b>\n";
        $text .= "  มูลค่า PR อนุมัติ: " . number_format($monthlySpend, 2) . " THB\n";

        // Open anomalies
        $openAnomalies = ProcurementAnomaly::where('status', 'open')->count();
        if ($openAnomalies > 0) {
            $text .= "\n🛡️ Anomaly ยังไม่แก้ไข: {$openAnomalies} รายการ\n";
        }

        $text .= "\n💬 ถามรายละเอียดเพิ่มเติมได้เลยครับ";
        $this->sendMessage($chatId, $text);
    }

    protected function chatRecommendations(string $chatId, User $user): void
    {
        $text = "💡 <b>คำแนะนำจาก VENDR Bot</b>\n\n";
        $recommendations = [];

        // 1. Long-pending PRs
        $longPending = PurchaseRequisition::where('status', 'pending_approval')
            ->where(function ($q) {
                $q->where('submitted_at', '<=', now()->subDays(3))
                  ->orWhere(function ($q2) {
                      $q2->whereNull('submitted_at')
                          ->where('updated_at', '<=', now()->subDays(3));
                  });
            })->orderBy('submitted_at', 'asc')->limit(3)->get();

        if ($longPending->isNotEmpty()) {
            $rec = "🔴 <b>เร่ง Approve PR ที่ค้าง:</b>\n";
            foreach ($longPending as $pr) {
                $days = $pr->submitted_at ? now()->diffInDays($pr->submitted_at) : '?';
                $rec .= "  • {$pr->pr_number} ({$pr->title}) - ค้าง {$days} วัน\n";
            }
            $recommendations[] = $rec;
        }

        // 2. Overdue PO deliveries
        $overduePOs = PurchaseOrder::whereIn('status', ['approved', 'sent_to_supplier', 'partially_received'])
            ->where('expected_delivery_date', '<', now())
            ->with('vendor')
            ->orderBy('expected_delivery_date', 'asc')->limit(3)->get();

        if ($overduePOs->isNotEmpty()) {
            $rec = "🔴 <b>ติดตาม PO ที่เลยกำหนด:</b>\n";
            foreach ($overduePOs as $po) {
                $vendor = $po->vendor->company_name ?? 'N/A';
                $daysLate = now()->diffInDays($po->expected_delivery_date);
                $rec .= "  • {$po->po_number} ({$vendor}) - เลย {$daysLate} วัน\n";
            }
            $recommendations[] = $rec;
        }

        // 3. Overdue payments
        $overduePayments = PaymentMilestone::where('status', PaymentMilestone::STATUS_PENDING)
            ->where('due_date', '<', now())
            ->with('purchaseOrder')
            ->orderBy('due_date', 'asc')->limit(3)->get();

        if ($overduePayments->isNotEmpty()) {
            $rec = "🔴 <b>เร่งชำระเงินที่เกินกำหนด:</b>\n";
            foreach ($overduePayments as $pm) {
                $poNumber = $pm->purchaseOrder->po_number ?? '-';
                $daysLate = now()->diffInDays($pm->due_date);
                $rec .= "  • {$pm->milestone_title} (PO: {$poNumber}) - เลย {$daysLate} วัน, " . number_format($pm->amount ?? 0, 2) . " THB\n";
            }
            $recommendations[] = $rec;
        }

        // 4. High-spending departments
        $topDept = PurchaseRequisition::select('department_id', DB::raw('SUM(total_amount) as total'))
            ->whereIn('status', ['approved', 'in_process', 'completed'])
            ->whereMonth('approved_at', now()->month)->whereYear('approved_at', now()->year)
            ->groupBy('department_id')
            ->orderByDesc('total')
            ->first();

        if ($topDept) {
            $dept = Department::find($topDept->department_id);
            if ($dept && $dept->monthly_budget > 0) {
                $pct = round(($topDept->total / $dept->monthly_budget) * 100, 1);
                if ($pct >= 80) {
                    $rec = "🟡 <b>งบใกล้เต็ม:</b>\n";
                    $rec .= "  • {$dept->name}: ใช้ไป {$pct}% (" . number_format($topDept->total, 2) . " / " . number_format($dept->monthly_budget, 2) . ")\n";
                    $recommendations[] = $rec;
                }
            }
        }

        // 5. Vendor with low score
        $lowVendors = VendorScore::forYear(now()->year)
            ->needImprovement()
            ->with('vendor')
            ->limit(2)->get();

        if ($lowVendors->isNotEmpty()) {
            $rec = "⚠️ <b>Vendor ที่ควรระวัง:</b>\n";
            foreach ($lowVendors as $vs) {
                $vendorName = $vs->vendor->company_name ?? 'N/A';
                $rec .= "  • {$vendorName} (Grade: {$vs->current_grade}, Score: {$vs->formatted_score})\n";
            }
            $recommendations[] = $rec;
        }

        // 6. Critical anomalies
        $criticalAnomalies = ProcurementAnomaly::where('status', 'open')
            ->where('severity', 'critical')
            ->limit(2)->get();

        if ($criticalAnomalies->isNotEmpty()) {
            $rec = "🛡️ <b>ความผิดปกติวิกฤต:</b>\n";
            foreach ($criticalAnomalies as $a) {
                $rec .= "  • {$a->title}\n";
            }
            $recommendations[] = $rec;
        }

        if (empty($recommendations)) {
            $text .= "✅ ทุกอย่างดูดีครับ! ไม่มีเรื่องเร่งด่วน\n\n";
            $text .= "💬 ถามข้อมูลเพิ่มเติมได้ตลอดครับ";
        } else {
            foreach ($recommendations as $i => $rec) {
                $text .= ($i + 1) . ". {$rec}\n";
            }
            $text .= "━━━━━━━━━━━━━━━\n";
            $text .= "💬 ต้องการรายละเอียดข้อไหน ถามได้เลยครับ";
        }

        $this->sendMessage($chatId, $text);
    }

    protected function chatProcessExplain(string $chatId, string $q): void
    {
        $text = '';

        if (Str::contains($q, ['pr', 'purchase requisition', 'ใบขอ'])) {
            $text = "📋 <b>กระบวนการ PR (Purchase Requisition)</b>\n\n";
            $text .= "1️⃣ <b>สร้าง PR</b> — ผู้ขอสร้างใบ PR ระบุรายการ, จำนวน, ราคา, งบประมาณ\n";
            $text .= "2️⃣ <b>ส่งอนุมัติ</b> — Draft → Pending Approval\n";
            $text .= "3️⃣ <b>อนุมัติ</b> — หัวหน้าแผนก/ผู้จัดการตรวจสอบ\n";
            $text .= "4️⃣ <b>สร้าง PO</b> — จัดซื้อสร้าง PO จาก PR ที่อนุมัติ\n";
            $text .= "5️⃣ <b>ตรวจรับ (GR)</b> — คณะกรรมการตรวจรับของ\n";
            $text .= "6️⃣ <b>ชำระเงิน</b> — ดำเนินการจ่ายตามงวด\n\n";
            $text .= "📌 สถานะ: Draft → Pending → Approved → In Process → Completed\n";
            $text .= "💡 สร้าง PR: /newpr | ดู PR: /mypr";
        } elseif (Str::contains($q, ['po', 'purchase order', 'ใบสั่งซื้อ'])) {
            $text = "📦 <b>กระบวนการ PO (Purchase Order)</b>\n\n";
            $text .= "1️⃣ <b>สร้าง PO</b> — จากใบ PR ที่อนุมัติแล้ว\n";
            $text .= "2️⃣ <b>อนุมัติ PO</b> — ผู้จัดการจัดซื้อตรวจสอบ\n";
            $text .= "3️⃣ <b>ส่ง Vendor</b> — แจ้ง Vendor เพื่อจัดส่ง\n";
            $text .= "4️⃣ <b>รับของ (GR)</b> — บันทึกการรับ, ตรวจสอบคุณภาพ\n";
            $text .= "5️⃣ <b>ปิดงาน</b> — รับครบ → ปิด PO\n\n";
            $text .= "📌 สถานะ: Draft → Pending → Approved → Sent → Received → Closed\n";
            $text .= "💡 ดู PO: /po";
        } elseif (Str::contains($q, ['gr', 'ตรวจรับ', 'goods receipt', 'รับของ'])) {
            $text = "📋 <b>กระบวนการ GR (Goods Receipt)</b>\n\n";
            $text .= "1️⃣ <b>สร้าง GR</b> — บันทึกการรับของจาก PO\n";
            $text .= "2️⃣ <b>ตรวจสอบ</b> — คณะกรรมการตรวจสอบคุณภาพ\n";
            $text .= "3️⃣ <b>ผลตรวจ</b> — Passed / Failed / Partial\n";
            $text .= "4️⃣ <b>ดำเนินการ</b> — ถ้าผ่าน → Complete / ไม่ผ่าน → Return\n\n";
            $text .= "📌 Inspection: Pending → Passed/Failed/Partial\n";
            $text .= "💡 ดู GR: /gr";
        } elseif (Str::contains($q, ['sla', 'เกรด', 'grade', 'ประเมิน'])) {
            $text = "📊 <b>SLA Grade คำนวณอย่างไร</b>\n\n";
            $text .= "วัดจากระยะเวลาจริง เทียบกับมาตรฐาน SLA แต่ละ stage:\n\n";
            $text .= "🌟 Grade S (Excellent) — เร็วมาก\n";
            $text .= "💚 Grade A (Very Good) — เร็ว\n";
            $text .= "💙 Grade B (Good) — ตามเวลา\n";
            $text .= "💛 Grade C (Average) — เกินเล็กน้อย\n";
            $text .= "🧡 Grade D (Below Avg) — ล่าช้า\n";
            $text .= "❤️ Grade F (Fail) — ล่าช้ามาก\n\n";
            $text .= "📌 Stage: PR→Approve → PR→PO → PO→Approve → Full Cycle\n";
            $text .= "💡 ดู SLA: /sla";
        } elseif (Str::contains($q, ['anomaly', 'ผิดปกติ', 'ตรวจจับ'])) {
            $text = "🛡️ <b>Anomaly Detection ตรวจจับอะไรบ้าง</b>\n\n";
            $text .= "1️⃣ <b>💰 ราคาผิดปกติ</b> — ราคาสูงกว่าค่าเฉลี่ยมาก\n";
            $text .= "2️⃣ <b>✂️ แยก PR</b> — แตก PR เพื่อหลีกเลี่ยงวงเงิน\n";
            $text .= "3️⃣ <b>📈 งบเกิน</b> — แผนกใช้งบเกิน threshold\n";
            $text .= "4️⃣ <b>🏪 Vendor กระจุก</b> — ใช้ Vendor เดียวเยอะเกิน\n";
            $text .= "5️⃣ <b>⏰ อนุมัติช้า</b> — PR ค้างนานผิดปกติ\n\n";
            $text .= "ระดับความรุนแรง: 🔴 Critical | 🟡 Warning | ℹ️ Info\n";
            $text .= "💡 สแกน: /anomaly";
        } elseif (Str::contains($q, ['vendor', 'ผู้ขาย', 'คะแนน', 'score'])) {
            $text = "📊 <b>ระบบคะแนน Vendor</b>\n\n";
            $text .= "💚 Grade A (≥3.50) — ดีมาก\n";
            $text .= "💙 Grade B (≥2.50) — ดี\n";
            $text .= "💛 Grade C (≥1.50) — พอใช้\n";
            $text .= "🧡 Grade D (<1.50) — ต้องปรับปรุง\n\n";
            $text .= "คะแนน 0-4 จากหมวด: คุณภาพ, การส่งมอบ, ราคา, บริการ\n";
            $text .= "Trend: ↑ ดีขึ้น | → คงที่ | ↓ แย่ลง\n";
            $text .= "💡 ดูคะแนน: /vendorscore";
        } elseif (Str::contains($q, ['payment', 'ชำระ', 'จ่ายเงิน', 'งวด'])) {
            $text = "💳 <b>ระบบ Payment Milestone</b>\n\n";
            $text .= "แต่ละ PO แบ่งเป็นงวดชำระ:\n\n";
            $text .= "⏳ Pending — ยังไม่ถึงกำหนด\n";
            $text .= "🟡 Due — ถึงกำหนดแล้ว\n";
            $text .= "✅ Paid — ชำระแล้ว\n";
            $text .= "🔴 Overdue — เลยกำหนด\n\n";
            $text .= "ระบบจะแจ้งเตือนล่วงหน้า 15 วัน\n";
            $text .= "💡 ดูงวดชำระ: /payment";
        } else {
            $text = "📖 <b>ระบบ VENDR — Procurement Management</b>\n\n";
            $text .= "ระบบจัดซื้อจัดจ้างครบวงจร:\n\n";
            $text .= "📋 <b>PR</b> — ใบขอจัดซื้อ (/newpr, /mypr)\n";
            $text .= "📦 <b>PO</b> — ใบสั่งซื้อ (/po)\n";
            $text .= "📋 <b>GR</b> — ตรวจรับงาน (/gr)\n";
            $text .= "📑 <b>Contract</b> — จัดการสัญญา (/contract)\n";
            $text .= "💳 <b>Payment</b> — งวดชำระเงิน (/payment)\n";
            $text .= "📊 <b>SLA</b> — วัดประสิทธิภาพ (/sla)\n";
            $text .= "🏪 <b>Vendor</b> — คะแนนผู้ขาย (/vendorscore)\n";
            $text .= "🛡️ <b>Anomaly</b> — ตรวจจับผิดปกติ (/anomaly)\n\n";
            $text .= "💬 ถามเรื่องไหนเจาะจง ได้เลยครับ!";
        }

        $this->sendMessage($chatId, $text);
    }

    protected function chatComparison(string $chatId, string $q): void
    {
        $now = now();
        $text = '';

        // Department comparison
        if (Str::contains($q, ['แผนก', 'department', 'dept'])) {
            $text = "📊 <b>เปรียบเทียบแผนก (เดือนนี้)</b>\n\n";

            $depts = Department::active()->get();
            $deptData = [];

            foreach ($depts as $dept) {
                $spent = PurchaseRequisition::where('department_id', $dept->id)
                    ->whereIn('status', ['approved', 'in_process', 'completed'])
                    ->whereMonth('approved_at', $now->month)->whereYear('approved_at', $now->year)
                    ->sum('total_amount');
                $prCount = PurchaseRequisition::where('department_id', $dept->id)
                    ->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)
                    ->count();

                if ($spent > 0 || $prCount > 0) {
                    $deptData[] = ['name' => $dept->name, 'spent' => $spent, 'count' => $prCount, 'budget' => $dept->monthly_budget ?? 0];
                }
            }

            usort($deptData, fn($a, $b) => $b['spent'] <=> $a['spent']);

            if (empty($deptData)) {
                $text .= "ไม่มีข้อมูลเดือนนี้";
            } else {
                $rank = 1;
                foreach ($deptData as $d) {
                    $budgetInfo = '';
                    if ($d['budget'] > 0) {
                        $pct = round(($d['spent'] / $d['budget']) * 100, 1);
                        $icon = $pct >= 90 ? '🔴' : ($pct >= 70 ? '🟡' : '🟢');
                        $budgetInfo = " {$icon} {$pct}% ของงบ";
                    }
                    $text .= "  {$rank}. <b>{$d['name']}</b>\n";
                    $text .= "     💰 " . number_format($d['spent'], 2) . " THB | PR: {$d['count']}{$budgetInfo}\n\n";
                    $rank++;
                }
            }

            $this->sendMessage($chatId, $text);
            return;
        }

        // Vendor comparison
        if (Str::contains($q, ['vendor', 'ผู้ขาย', 'supplier'])) {
            $text = "📊 <b>เปรียบเทียบ Vendor (มูลค่า PO)</b>\n\n";

            $topVendors = PurchaseOrder::select('vendor_id', DB::raw('count(*) as cnt'), DB::raw('SUM(total_amount) as total'))
                ->whereNotNull('vendor_id')
                ->groupBy('vendor_id')
                ->orderByDesc('total')
                ->limit(10)->get();

            $rank = 1;
            foreach ($topVendors as $v) {
                $vendor = Vendor::find($v->vendor_id);
                if (!$vendor) continue;

                // Get score
                $score = VendorScore::where('vendor_id', $v->vendor_id)
                    ->forYear($now->year)
                    ->orderBy('month', 'desc')->first();

                $gradeText = $score ? " | Grade: {$score->current_grade}" : "";

                $text .= "  {$rank}. <b>{$vendor->company_name}</b>\n";
                $text .= "     PO: {$v->cnt} ใบ | 💰 " . number_format($v->total ?? 0, 2) . "{$gradeText}\n\n";
                $rank++;
            }

            $this->sendMessage($chatId, $text);
            return;
        }

        // Month comparison (this vs last)
        $text = "📊 <b>เปรียบเทียบเดือนนี้ vs เดือนก่อน</b>\n\n";

        $thisMonth = $now->month;
        $thisYear = $now->year;
        $lastMonth = $now->copy()->subMonth()->month;
        $lastYear = $now->copy()->subMonth()->year;

        $thisMonthLabel = $now->locale('th')->translatedFormat('F');
        $lastMonthLabel = $now->copy()->subMonth()->locale('th')->translatedFormat('F');

        // PR counts
        $prThis = PurchaseRequisition::whereMonth('created_at', $thisMonth)->whereYear('created_at', $thisYear)->count();
        $prLast = PurchaseRequisition::whereMonth('created_at', $lastMonth)->whereYear('created_at', $lastYear)->count();

        // PR amounts
        $prAmtThis = PurchaseRequisition::whereIn('status', ['approved', 'in_process', 'completed'])
            ->whereMonth('approved_at', $thisMonth)->whereYear('approved_at', $thisYear)->sum('total_amount');
        $prAmtLast = PurchaseRequisition::whereIn('status', ['approved', 'in_process', 'completed'])
            ->whereMonth('approved_at', $lastMonth)->whereYear('approved_at', $lastYear)->sum('total_amount');

        // PO counts
        $poThis = PurchaseOrder::whereMonth('created_at', $thisMonth)->whereYear('created_at', $thisYear)->count();
        $poLast = PurchaseOrder::whereMonth('created_at', $lastMonth)->whereYear('created_at', $lastYear)->count();

        $poAmtThis = PurchaseOrder::whereMonth('created_at', $thisMonth)->whereYear('created_at', $thisYear)->sum('total_amount');
        $poAmtLast = PurchaseOrder::whereMonth('created_at', $lastMonth)->whereYear('created_at', $lastYear)->sum('total_amount');

        $text .= "📋 <b>PR</b>\n";
        $text .= "  {$lastMonthLabel}: {$prLast} ใบ (" . number_format($prAmtLast, 2) . ")\n";
        $text .= "  {$thisMonthLabel}: {$prThis} ใบ (" . number_format($prAmtThis, 2) . ")\n";
        $text .= "  " . $this->trendText($prAmtThis, $prAmtLast) . "\n\n";

        $text .= "📦 <b>PO</b>\n";
        $text .= "  {$lastMonthLabel}: {$poLast} ใบ (" . number_format($poAmtLast, 2) . ")\n";
        $text .= "  {$thisMonthLabel}: {$poThis} ใบ (" . number_format($poAmtThis, 2) . ")\n";
        $text .= "  " . $this->trendText($poAmtThis, $poAmtLast) . "\n";

        $this->sendMessage($chatId, $text);
    }

    protected function trendText(float $current, float $previous): string
    {
        if ($previous == 0) return '📊 ยังไม่มีข้อมูลเทียบ';
        $change = (($current - $previous) / $previous) * 100;
        $changeText = number_format(abs($change), 1);
        if ($change > 5) return "📈 เพิ่มขึ้น {$changeText}%";
        if ($change < -5) return "📉 ลดลง {$changeText}%";
        return "➡️ ใกล้เคียงกัน ({$changeText}%)";
    }

    // ==========================================
    // Expanded Natural Language Query Processor
    // ==========================================

    protected function processNaturalQuery(string $q, User $user): string
    {
        $now = now();

        // ===== PR queries =====
        if (Str::contains($q, ['pr', 'ใบขอ', 'ใบ pr', 'purchase requisition'])) {
            // My PRs
            if (Str::contains($q, ['ของฉัน', 'ของผม', 'ของดิฉัน', 'my', 'ฉัน', 'ผม'])) {
                $prs = PurchaseRequisition::where('requester_id', $user->id)
                    ->orderBy('created_at', 'desc')->limit(5)->get();

                if ($prs->isEmpty()) return "📋 คุณยังไม่มีใบ PR เลยครับ สร้างด้วย /newpr";

                $text = "📋 <b>PR ของคุณ (ล่าสุด)</b>\n\n";
                foreach ($prs as $pr) {
                    $amount = $pr->total_amount ? number_format($pr->total_amount, 2) : '-';
                    $text .= "  📋 {$pr->pr_number} - {$pr->title}\n";
                    $text .= "     สถานะ: {$pr->status} | 💰 {$amount}\n\n";
                }
                return $text;
            }

            // Pending / waiting
            if (Str::contains($q, ['รอ', 'pending', 'อนุมัติ', 'ค้าง', 'ยังไม่'])) {
                $pending = PurchaseRequisition::where('status', 'pending_approval')
                    ->orderBy('submitted_at', 'asc')->get();

                if ($pending->isEmpty()) return "✅ ไม่มี PR ที่รออนุมัติครับ";

                $oldest = $pending->first();
                $oldestDays = $oldest->submitted_at ? now()->diffInDays($oldest->submitted_at) : '?';
                $urgentCount = $pending->where('priority', 'urgent')->count();

                $text = "📋 <b>PR รออนุมัติ: {$pending->count()} ใบ</b>\n";
                if ($urgentCount > 0) $text .= "🔴 เร่งด่วน: {$urgentCount} ใบ\n";
                $text .= "⏳ ค้างนานสุด: {$oldest->pr_number} ({$oldestDays} วัน)\n\n";

                foreach ($pending->take(5) as $pr) {
                    $days = $pr->submitted_at ? now()->diffInDays($pr->submitted_at) : '?';
                    $icon = $pr->priority === 'urgent' ? '🔴' : ($pr->priority === 'high' ? '🟠' : '⏳');
                    $text .= "  {$icon} {$pr->pr_number} - {$pr->title} (รอ {$days} วัน)\n";
                }
                if ($pending->count() > 5) $text .= "\n  ... อีก " . ($pending->count() - 5) . " ใบ";
                return $text;
            }

            // Rejected
            if (Str::contains($q, ['reject', 'ปฏิเสธ', 'ไม่อนุมัติ', 'ไม่ผ่าน', 'ตีกลับ'])) {
                $rejected = PurchaseRequisition::where('status', 'rejected')
                    ->orderBy('rejected_at', 'desc')->limit(10)->get();
                $totalRejected = PurchaseRequisition::where('status', 'rejected')->count();

                $text = "❌ <b>PR ที่ถูกปฏิเสธ: {$totalRejected} ใบ</b>\n\n";
                foreach ($rejected as $pr) {
                    $text .= "  ❌ {$pr->pr_number} - {$pr->title}\n";
                    if ($pr->rejection_reason) $text .= "     เหตุผล: " . mb_substr($pr->rejection_reason, 0, 80) . "\n";
                    $text .= "\n";
                }
                return $text;
            }

            // Count / quantity
            if (Str::contains($q, ['กี่', 'จำนวน', 'count', 'เท่าไหร่', 'ทั้งหมด'])) {
                $total = PurchaseRequisition::count();
                $thisMonth = PurchaseRequisition::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count();
                $byStatus = PurchaseRequisition::select('status', DB::raw('count(*) as cnt'))
                    ->groupBy('status')->pluck('cnt', 'status')->toArray();

                $text = "📋 <b>จำนวน PR</b>\n\n";
                $text .= "ทั้งหมด: {$total} ใบ | เดือนนี้: {$thisMonth}\n\n";
                foreach ($byStatus as $status => $cnt) {
                    $text .= "  • {$status}: {$cnt}\n";
                }
                return $text;
            }
        }

        // ===== PO queries =====
        if (Str::contains($q, ['po', 'purchase order', 'ใบสั่งซื้อ', 'สั่งซื้อ'])) {
            if (Str::contains($q, ['ครบกำหนด', 'ส่ง', 'delivery', 'ใกล้', 'จะมา'])) {
                $upcoming = PurchaseOrder::whereIn('status', ['approved', 'sent_to_supplier', 'partially_received'])
                    ->whereNotNull('expected_delivery_date')
                    ->whereBetween('expected_delivery_date', [now(), now()->addDays(7)])
                    ->orderBy('expected_delivery_date')->get();

                if ($upcoming->isEmpty()) return "✅ ไม่มี PO ที่ใกล้ครบกำหนดส่งใน 7 วันครับ";

                $text = "📦 <b>PO ใกล้ครบกำหนดส่ง (7 วัน): {$upcoming->count()} ใบ</b>\n\n";
                foreach ($upcoming as $po) {
                    $vendor = $po->vendor->company_name ?? 'N/A';
                    $days = now()->diffInDays($po->expected_delivery_date);
                    $text .= "  📦 {$po->po_number} - {$vendor} (เหลือ {$days} วัน)\n";
                }
                return $text;
            }

            if (Str::contains($q, ['เกิน', 'overdue', 'ล่าช้า', 'late', 'เลย'])) {
                $overdue = PurchaseOrder::whereIn('status', ['approved', 'sent_to_supplier', 'partially_received'])
                    ->where('expected_delivery_date', '<', now())
                    ->with('vendor')
                    ->orderBy('expected_delivery_date')->limit(10)->get();

                if ($overdue->isEmpty()) return "✅ ไม่มี PO ที่เลยกำหนดส่งครับ";

                $text = "🔴 <b>PO เลยกำหนดส่ง: {$overdue->count()} ใบ</b>\n\n";
                foreach ($overdue as $po) {
                    $vendor = $po->vendor->company_name ?? 'N/A';
                    $daysLate = now()->diffInDays($po->expected_delivery_date);
                    $text .= "  🔴 {$po->po_number} ({$vendor}) - เลย {$daysLate} วัน\n";
                }
                return $text;
            }
        }

        // ===== Vendor queries =====
        if (Str::contains($q, ['vendor', 'ผู้ขาย', 'ซัพพลายเออร์', 'supplier'])) {
            if (Str::contains($q, ['มากสุด', 'top', 'อันดับ', 'ดีสุด', 'ดีที่สุด'])) {
                $topVendors = PurchaseOrder::select('vendor_id', DB::raw('count(*) as cnt'), DB::raw('SUM(total_amount) as total'))
                    ->whereNotNull('vendor_id')
                    ->groupBy('vendor_id')->orderByDesc('total')->limit(5)->get();

                $text = "🏪 <b>Top Vendor (มูลค่า PO)</b>\n\n";
                $rank = 1;
                foreach ($topVendors as $v) {
                    $vendor = Vendor::find($v->vendor_id);
                    if (!$vendor) continue;
                    $text .= "  {$rank}. {$vendor->company_name}\n     PO: {$v->cnt} | " . number_format($v->total ?? 0, 2) . " THB\n\n";
                    $rank++;
                }
                return $text;
            }

            if (Str::contains($q, ['ระวัง', 'แย่', 'ปรับปรุง', 'ต่ำ', 'risk'])) {
                $lowScores = VendorScore::forYear($now->year)->needImprovement()->with('vendor')->limit(5)->get();
                if ($lowScores->isEmpty()) return "✅ ไม่มี Vendor ที่ต้องปรับปรุงครับ";

                $text = "⚠️ <b>Vendor ที่ต้องปรับปรุง</b>\n\n";
                foreach ($lowScores as $vs) {
                    $vendorName = $vs->vendor->company_name ?? 'N/A';
                    $text .= "  ⚠️ {$vendorName} | Grade: {$vs->current_grade} | {$vs->formatted_score}\n";
                }
                return $text;
            }
        }

        // ===== Spending / Budget =====
        if (Str::contains($q, ['ค่าใช้จ่าย', 'spending', 'งบ', 'budget', 'เงิน', 'ใช้ไป', 'จ่าย'])) {
            $prAmount = PurchaseRequisition::whereIn('status', ['approved', 'in_process', 'completed'])
                ->whereMonth('approved_at', $now->month)->whereYear('approved_at', $now->year)->sum('total_amount');
            $poAmount = PurchaseOrder::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->sum('total_amount');
            $paidAmount = PaymentMilestone::where('status', PaymentMilestone::STATUS_PAID)
                ->whereMonth('paid_date', $now->month)->whereYear('paid_date', $now->year)->sum('paid_amount');

            $text = "💰 <b>สรุปค่าใช้จ่ายเดือนนี้</b>\n\n";
            $text .= "📋 มูลค่า PR อนุมัติ: " . number_format($prAmount, 2) . " THB\n";
            $text .= "📦 มูลค่า PO: " . number_format($poAmount, 2) . " THB\n";
            $text .= "💳 ชำระแล้ว: " . number_format($paidAmount, 2) . " THB\n";

            // Top department spending
            $topDept = PurchaseRequisition::select('department_id', DB::raw('SUM(total_amount) as total'))
                ->whereIn('status', ['approved', 'in_process', 'completed'])
                ->whereMonth('approved_at', $now->month)->whereYear('approved_at', $now->year)
                ->groupBy('department_id')->orderByDesc('total')->first();

            if ($topDept) {
                $dept = Department::find($topDept->department_id);
                if ($dept) $text .= "\n🏢 แผนกใช้เยอะสุด: {$dept->name} (" . number_format($topDept->total, 2) . ")";
            }
            return $text;
        }

        // ===== Overdue =====
        if (Str::contains($q, ['เกินกำหนด', 'overdue', 'ล่าช้า', 'late', 'เลยกำหนด'])) {
            $overduePR = PurchaseRequisition::where('status', 'pending_approval')
                ->where('submitted_at', '<=', now()->subDays(3))->count();
            $overduePO = PurchaseOrder::whereIn('status', ['approved', 'sent_to_supplier', 'partially_received'])
                ->where('expected_delivery_date', '<', now())->count();
            $overduePay = PaymentMilestone::where('status', PaymentMilestone::STATUS_PENDING)
                ->where('due_date', '<', now())->count();

            $text = "⏰ <b>สรุปรายการเกินกำหนด</b>\n\n";
            $text .= "📋 PR รออนุมัติ >3 วัน: {$overduePR} ใบ\n";
            $text .= "📦 PO เลยกำหนดส่ง: {$overduePO} ใบ\n";
            $text .= "💳 เลยกำหนดชำระ: {$overduePay} งวด\n";

            $total = $overduePR + $overduePO + $overduePay;
            $text .= "\nรวม: {$total} รายการ ";
            $text .= $total === 0 ? "✅ สบายใจได้!" : "⚠️ ควรตรวจสอบ";
            return $text;
        }

        // ===== Contract =====
        if (Str::contains($q, ['สัญญา', 'contract', 'หมดอายุ', 'expire'])) {
            $activeContracts = ContractApproval::where('status', 'approved')->count();
            $expiring = ContractApproval::where('status', 'approved')
                ->whereBetween('end_date', [now(), now()->addDays(30)])
                ->orderBy('end_date')->get();

            $text = "📑 <b>สถานะสัญญา</b>\n\n";
            $text .= "Active: {$activeContracts} สัญญา\n";

            if ($expiring->isNotEmpty()) {
                $text .= "\n⚠️ <b>หมดอายุใน 30 วัน:</b>\n";
                foreach ($expiring as $c) {
                    $days = now()->diffInDays($c->end_date);
                    $text .= "  ⚠️ {$c->contract_number} - {$c->contract_title} (เหลือ {$days} วัน)\n";
                }
            } else {
                $text .= "✅ ไม่มีสัญญาใกล้หมดอายุ";
            }
            return $text;
        }

        // ===== Payment =====
        if (Str::contains($q, ['ชำระ', 'payment', 'จ่าย', 'งวด', 'milestone'])) {
            $pendingCount = PaymentMilestone::where('status', PaymentMilestone::STATUS_PENDING)->count();
            $pendingAmount = PaymentMilestone::where('status', PaymentMilestone::STATUS_PENDING)->sum('amount');
            $overdueCount = PaymentMilestone::where('status', PaymentMilestone::STATUS_PENDING)
                ->where('due_date', '<', now())->count();

            $text = "💳 <b>สถานะการชำระเงิน</b>\n\n";
            $text .= "⏳ รอชำระ: {$pendingCount} งวด (" . number_format($pendingAmount, 2) . " THB)\n";
            $text .= "🔴 เกินกำหนด: {$overdueCount} งวด\n";

            $dueSoon = PaymentMilestone::dueSoon(7)->count();
            $text .= "🟡 ใกล้กำหนด (7 วัน): {$dueSoon} งวด";
            return $text;
        }

        // ===== Anomaly =====
        if (Str::contains($q, ['anomaly', 'ผิดปกติ', 'ทุจริต', 'แปลก', 'ไม่ชอบมาพากล'])) {
            $open = ProcurementAnomaly::where('status', 'open')->count();
            $critical = ProcurementAnomaly::where('status', 'open')->where('severity', 'critical')->count();

            $text = "🛡️ <b>Anomaly สถานะ</b>\n\n";
            $text .= "Open: {$open} | Critical: {$critical}\n\n";

            if ($critical > 0) {
                $criticals = ProcurementAnomaly::where('status', 'open')->where('severity', 'critical')->limit(3)->get();
                $text .= "🔴 <b>Critical:</b>\n";
                foreach ($criticals as $a) {
                    $text .= "  • {$a->title}\n";
                }
            }
            $text .= "\n💡 สแกนเพิ่ม: /anomaly";
            return $text;
        }

        // ===== Default: smart summary =====
        $text = "🤖 <b>VENDR Assistant</b>\n\n";
        $text .= "ผมเข้าใจคำถามไม่ชัด ลองถามแบบนี้ดูครับ:\n\n";
        $text .= "💬 <b>ถามข้อมูล:</b>\n";
        $text .= "  • \"PR ที่รออนุมัติมีกี่ใบ\"\n";
        $text .= "  • \"vendor ไหนมี PO เยอะสุด\"\n";
        $text .= "  • \"เดือนนี้ใช้เงินไปเท่าไหร่\"\n";
        $text .= "  • \"PO ไหนเลยกำหนดส่ง\"\n";
        $text .= "  • \"สัญญาไหนใกล้หมดอายุ\"\n\n";
        $text .= "📊 <b>สรุป:</b>\n";
        $text .= "  • \"สรุปวันนี้\" / \"ภาพรวม\"\n";
        $text .= "  • \"ควรเร่งอะไรก่อน\"\n";
        $text .= "  • \"เทียบเดือนนี้กับเดือนก่อน\"\n\n";
        $text .= "📖 <b>อธิบาย:</b>\n";
        $text .= "  • \"PR คืออะไร\" / \"SLA grade คำนวณยังไง\"\n";
        $text .= "  • \"anomaly detection ตรวจจับอะไรบ้าง\"\n\n";
        $text .= "หรือพิมพ์ /help ดูคำสั่งทั้งหมด";
        return $text;
    }

    // ==========================================
    // PO Approve/Reject Callbacks
    // ==========================================

    protected function handlePOCallback(string $chatId, int $messageId, string $data, string $callbackId): void
    {
        $user = User::where('telegram_chat_id', $chatId)->first();
        if (!$user) {
            $this->answerCallbackQuery($callbackId, 'กรุณา /register ก่อน');
            return;
        }

        if (!$this->isManagerRole($user)) {
            $this->answerCallbackQuery($callbackId, 'คุณไม่มีสิทธิ์ดำเนินการนี้');
            return;
        }

        if (str_starts_with($data, 'po_approve:')) {
            $poId = (int) str_replace('po_approve:', '', $data);
            $po = PurchaseOrder::find($poId);

            if (!$po || !$po->canApprove()) {
                $this->answerCallbackQuery($callbackId, 'ไม่สามารถอนุมัติ PO นี้ได้');
                return;
            }

            $po->approve($user->id);
            $this->answerCallbackQuery($callbackId, '✅ อนุมัติ PO แล้ว');
            $this->editMessage($chatId, $messageId,
                "✅ <b>อนุมัติ PO แล้ว</b>\n\n" .
                "📦 {$po->po_number}\n" .
                "👤 อนุมัติโดย: {$user->name}\n" .
                "🕐 " . now()->format('d/m/Y H:i')
            );
            return;
        }

        if (str_starts_with($data, 'po_reject:')) {
            $poId = (int) str_replace('po_reject:', '', $data);
            $po = PurchaseOrder::find($poId);

            if (!$po || !$po->canApprove()) {
                $this->answerCallbackQuery($callbackId, 'ไม่สามารถปฏิเสธ PO นี้ได้');
                return;
            }

            $po->reject($user->id, 'Rejected via Telegram');
            $this->answerCallbackQuery($callbackId, '❌ ปฏิเสธ PO แล้ว');
            $this->editMessage($chatId, $messageId,
                "❌ <b>ปฏิเสธ PO แล้ว</b>\n\n" .
                "📦 {$po->po_number}\n" .
                "👤 โดย: {$user->name}\n" .
                "🕐 " . now()->format('d/m/Y H:i')
            );
            return;
        }

        $this->answerCallbackQuery($callbackId);
    }

    // ==========================================
    // PR Creation Flow
    // ==========================================

    protected function handleNewPR(string $chatId): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        $companies = Company::where('is_active', true)->get();

        if ($companies->isEmpty()) {
            $this->sendMessage($chatId, "ไม่พบบริษัทที่เปิดใช้งาน");
            return;
        }

        $buttons = $companies->map(fn($c) => [
            ['text' => $c->display_name, 'callback_data' => "pr_company:{$c->id}"]
        ])->toArray();

        $buttons[] = [['text' => '❌ ยกเลิก', 'callback_data' => 'pr_cancel']];

        Cache::put("tg_flow:{$chatId}", [
            'step' => 'select_company',
            'user_id' => $user->id,
            'data' => [],
        ], 1800); // 30 min timeout

        $this->sendMessage($chatId,
            "📋 <b>สร้างใบ PR ใหม่</b>\n\nเลือกบริษัท:",
            ['inline_keyboard' => $buttons]
        );
    }

    protected function handleFlowInput(string $chatId, string $text, array $flow): void
    {
        $step = $flow['step'] ?? '';

        match ($step) {
            'enter_title'              => $this->flowSetTitle($chatId, $text, $flow),
            'enter_description'        => $this->flowSetDescription($chatId, $text, $flow),
            'enter_purpose'            => $this->flowSetPurpose($chatId, $text, $flow),
            'enter_budget_code'        => $this->flowSetBudgetCode($chatId, $text, $flow),
            'enter_project_code'       => $this->flowSetProjectCode($chatId, $text, $flow),
            'enter_procurement_budget' => $this->flowSetProcurementBudget($chatId, $text, $flow),
            'enter_delivery_schedule'  => $this->flowSetDeliverySchedule($chatId, $text, $flow),
            'enter_payment_schedule'   => $this->flowSetPaymentSchedule($chatId, $text, $flow),
            'enter_notes'              => $this->flowSetNotes($chatId, $text, $flow),
            'enter_item_description'   => $this->flowSetItemDescription($chatId, $text, $flow),
            'enter_item_quantity'      => $this->flowSetItemQuantity($chatId, $text, $flow),
            'enter_item_unit'          => $this->flowSetItemUnit($chatId, $text, $flow),
            'enter_item_unit_price'    => $this->flowSetItemUnitPrice($chatId, $text, $flow),
            'enter_item_spec'          => $this->flowSetItemSpec($chatId, $text, $flow),
            'enter_required_date'      => $this->flowSetCustomDate($chatId, $text, $flow),
            'enter_rejection_reason'   => $this->flowRejectPR($chatId, $text, $flow),
            default => $this->sendMessage($chatId, "กรุณาใช้ปุ่มเลือกด้านบน หรือพิมพ์ /newpr เพื่อเริ่มใหม่"),
        };
    }

    protected function flowSetTitle(string $chatId, string $text, array $flow): void
    {
        $flow['data']['title'] = $text;
        $flow['step'] = 'enter_description';
        Cache::put("tg_flow:{$chatId}", $flow, 1800);

        $this->sendMessage($chatId, "📝 กรุณาระบุรายละเอียด/วัตถุประสงค์:");
    }

    protected function flowSetDescription(string $chatId, string $text, array $flow): void
    {
        $flow['data']['description'] = $text;
        $flow['step'] = 'enter_purpose';
        Cache::put("tg_flow:{$chatId}", $flow, 1800);

        $this->sendMessage($chatId, "🎯 กรุณาระบุวัตถุประสงค์/เหตุผลในการจัดซื้อ:");
    }

    protected function flowSetPurpose(string $chatId, string $text, array $flow): void
    {
        $flow['data']['purpose'] = $text;
        $flow['step'] = 'select_priority';
        Cache::put("tg_flow:{$chatId}", $flow, 1800);

        $this->sendMessage($chatId, "⚡ เลือกความเร่งด่วน:", [
            'inline_keyboard' => [
                [
                    ['text' => '🟢 ต่ำ', 'callback_data' => 'pr_priority:low'],
                    ['text' => '🟡 ปกติ', 'callback_data' => 'pr_priority:medium'],
                ],
                [
                    ['text' => '🟠 สูง', 'callback_data' => 'pr_priority:high'],
                    ['text' => '🔴 เร่งด่วน', 'callback_data' => 'pr_priority:urgent'],
                ],
                [['text' => '❌ ยกเลิก', 'callback_data' => 'pr_cancel']],
            ]
        ]);
    }

    protected function flowSetCustomDate(string $chatId, string $text, array $flow): void
    {
        // Parse date: accept dd/mm/yyyy or yyyy-mm-dd
        $date = null;
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $text, $m)) {
            $date = \Carbon\Carbon::createFromDate($m[3], $m[2], $m[1]);
        } elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $text, $m)) {
            $date = \Carbon\Carbon::createFromDate($m[1], $m[2], $m[3]);
        }

        if (!$date || $date->lte(now())) {
            $this->sendMessage($chatId,
                "กรุณาระบุวันที่ให้ถูกต้อง (ต้องเป็นวันในอนาคต)\n" .
                "รูปแบบ: <code>dd/mm/yyyy</code>\n" .
                "ตัวอย่าง: <code>" . now()->addDays(7)->format('d/m/Y') . "</code>"
            );
            return;
        }

        $flow['data']['required_date'] = $date->format('Y-m-d');
        $flow['step'] = 'select_is_budgeted';
        Cache::put("tg_flow:{$chatId}", $flow, 1800);

        $this->sendMessage($chatId, "💼 การจัดซื้อนี้อยู่ในงบประมาณหรือไม่?", [
            'inline_keyboard' => [
                [
                    ['text' => '✅ อยู่ในงบประมาณ', 'callback_data' => 'pr_budgeted:yes'],
                    ['text' => '❌ นอกงบประมาณ', 'callback_data' => 'pr_budgeted:no'],
                ],
                [['text' => '❌ ยกเลิก', 'callback_data' => 'pr_cancel']],
            ]
        ]);
    }

    protected function flowSetBudgetCode(string $chatId, string $text, array $flow): void
    {
        $flow['data']['budget_code'] = $text;
        $flow['step'] = 'enter_project_code';
        Cache::put("tg_flow:{$chatId}", $flow, 1800);

        $this->sendMessage($chatId, "🏗 กรุณาระบุรหัสโครงการ (Project Code):\n\n💡 พิมพ์ <code>-</code> หากไม่มี");
    }

    protected function flowSetProjectCode(string $chatId, string $text, array $flow): void
    {
        $flow['data']['project_code'] = ($text === '-') ? null : $text;
        $flow['step'] = 'enter_procurement_budget';
        Cache::put("tg_flow:{$chatId}", $flow, 1800);

        $this->sendMessage($chatId, "💰 กรุณาระบุวงเงินงบประมาณจัดซื้อ (บาท):\nตัวอย่าง: <code>50000</code>\n\n💡 พิมพ์ <code>-</code> หากยังไม่ทราบ");
    }

    protected function flowSetProcurementBudget(string $chatId, string $text, array $flow): void
    {
        if ($text !== '-') {
            $budget = (float) str_replace(',', '', $text);
            if ($budget <= 0) {
                $this->sendMessage($chatId, "กรุณาระบุจำนวนเงินที่ถูกต้อง หรือพิมพ์ <code>-</code> หากยังไม่ทราบ");
                return;
            }
            $flow['data']['procurement_budget'] = $budget;
        }

        $flow['step'] = 'enter_delivery_schedule';
        Cache::put("tg_flow:{$chatId}", $flow, 1800);

        $this->sendMessage($chatId, "🚚 กรุณาระบุกำหนดการส่งมอบ:\nเช่น: <code>ส่งมอบภายใน 30 วันหลังลงนามสัญญา</code>\n\n💡 พิมพ์ <code>-</code> หากยังไม่ระบุ");
    }

    protected function flowSetDeliverySchedule(string $chatId, string $text, array $flow): void
    {
        $flow['data']['delivery_schedule'] = ($text === '-') ? null : $text;
        $flow['step'] = 'enter_payment_schedule';
        Cache::put("tg_flow:{$chatId}", $flow, 1800);

        $this->sendMessage($chatId, "💳 กรุณาระบุเงื่อนไขการชำระเงิน:\nเช่น: <code>ชำระภายใน 30 วันหลังตรวจรับ</code>\n\n💡 พิมพ์ <code>-</code> หากยังไม่ระบุ");
    }

    protected function flowSetPaymentSchedule(string $chatId, string $text, array $flow): void
    {
        $flow['data']['payment_schedule'] = ($text === '-') ? null : $text;
        $flow['step'] = 'enter_notes';
        Cache::put("tg_flow:{$chatId}", $flow, 1800);

        $this->sendMessage($chatId, "📝 หมายเหตุเพิ่มเติม:\n\n💡 พิมพ์ <code>-</code> หากไม่มี");
    }

    protected function flowSetNotes(string $chatId, string $text, array $flow): void
    {
        $flow['data']['notes'] = ($text === '-') ? null : $text;
        $flow['data']['items'] = [];
        $flow['step'] = 'enter_item_description';
        Cache::put("tg_flow:{$chatId}", $flow, 1800);

        $this->sendMessage($chatId,
            "📦 <b>เพิ่มรายการสินค้า/บริการ (รายการที่ 1)</b>\n\n" .
            "กรุณาระบุรายละเอียดสินค้า/บริการ:"
        );
    }

    // ==========================================
    // PR Items Flow
    // ==========================================

    protected function flowSetItemDescription(string $chatId, string $text, array $flow): void
    {
        $flow['data']['current_item'] = ['description' => $text];
        $flow['step'] = 'enter_item_quantity';
        Cache::put("tg_flow:{$chatId}", $flow, 1800);

        $this->sendMessage($chatId, "🔢 จำนวน:\nตัวอย่าง: <code>10</code>");
    }

    protected function flowSetItemQuantity(string $chatId, string $text, array $flow): void
    {
        $qty = (float) str_replace(',', '', $text);
        if ($qty <= 0) {
            $this->sendMessage($chatId, "กรุณาระบุจำนวนที่ถูกต้อง (เช่น 10):");
            return;
        }

        $flow['data']['current_item']['quantity'] = $qty;
        $flow['step'] = 'enter_item_unit';
        Cache::put("tg_flow:{$chatId}", $flow, 1800);

        $this->sendMessage($chatId, "📏 หน่วยนับ:\nเช่น: <code>ชิ้น</code>, <code>กล่อง</code>, <code>ชุด</code>, <code>เดือน</code>, <code>งาน</code>");
    }

    protected function flowSetItemUnit(string $chatId, string $text, array $flow): void
    {
        $flow['data']['current_item']['unit_of_measure'] = $text;
        $flow['step'] = 'enter_item_unit_price';
        Cache::put("tg_flow:{$chatId}", $flow, 1800);

        $this->sendMessage($chatId, "💲 ราคาต่อหน่วย (บาท):\nตัวอย่าง: <code>1500</code>");
    }

    protected function flowSetItemUnitPrice(string $chatId, string $text, array $flow): void
    {
        $price = (float) str_replace(',', '', $text);
        if ($price <= 0) {
            $this->sendMessage($chatId, "กรุณาระบุราคาที่ถูกต้อง (เช่น 1500):");
            return;
        }

        $item = $flow['data']['current_item'];
        $item['estimated_unit_price'] = $price;
        $item['estimated_amount'] = $item['quantity'] * $price;
        $flow['data']['current_item'] = $item;
        $flow['step'] = 'enter_item_spec';
        Cache::put("tg_flow:{$chatId}", $flow, 1800);

        $this->sendMessage($chatId,
            "📋 Specification/คุณสมบัติเพิ่มเติม:\n\n" .
            "💡 พิมพ์ <code>-</code> หากไม่มี"
        );
    }

    protected function flowSetItemSpec(string $chatId, string $text, array $flow): void
    {
        $item = $flow['data']['current_item'];
        $item['specification'] = ($text === '-') ? null : $text;

        // Finalize item
        $flow['data']['items'][] = $item;
        unset($flow['data']['current_item']);
        Cache::put("tg_flow:{$chatId}", $flow, 1800);

        $itemCount = count($flow['data']['items']);
        $totalAmount = array_sum(array_column($flow['data']['items'], 'estimated_amount'));

        $lastItem = $item;
        $itemSummary = "✅ <b>เพิ่มรายการที่ {$itemCount} แล้ว</b>\n";
        $itemSummary .= "📦 {$lastItem['description']}\n";
        $itemSummary .= "🔢 {$lastItem['quantity']} {$lastItem['unit_of_measure']} × " . number_format($lastItem['estimated_unit_price'], 2) . " = " . number_format($lastItem['estimated_amount'], 2) . " THB\n\n";
        $itemSummary .= "📊 รวม {$itemCount} รายการ = " . number_format($totalAmount, 2) . " THB\n\n";
        $itemSummary .= "ต้องการเพิ่มรายการอีกหรือไม่?";

        $flow['step'] = 'select_add_more_items';
        Cache::put("tg_flow:{$chatId}", $flow, 1800);

        $this->sendMessage($chatId, $itemSummary, [
            'inline_keyboard' => [
                [
                    ['text' => '➕ เพิ่มรายการ', 'callback_data' => 'pr_additem:yes'],
                    ['text' => '✅ เสร็จสิ้น', 'callback_data' => 'pr_additem:done'],
                ],
                [['text' => '❌ ยกเลิก', 'callback_data' => 'pr_cancel']],
            ]
        ]);
    }

    /**
     * Show PR summary for confirmation
     */
    protected function showPRSummary(string $chatId, array $flow): void
    {
        $data = $flow['data'];
        $company = Company::find($data['company_id']);
        $categoryLabels = PurchaseRequisition::getCategoryOptions();
        $formCatLabels = PurchaseRequisition::getFormCategoryOptions();
        $workTypeLabels = PurchaseRequisition::getWorkTypeOptions();
        $procMethodLabels = PurchaseRequisition::getProcurementMethodOptions();
        $priorityLabels = ['low' => '🟢 ต่ำ', 'medium' => '🟡 ปกติ', 'high' => '🟠 สูง', 'urgent' => '🔴 เร่งด่วน'];

        $totalAmount = array_sum(array_column($data['items'], 'estimated_amount'));

        $summary = "📋 <b>สรุปใบ PR</b>\n\n";
        $summary .= "🏢 บริษัท: {$company->display_name}\n";
        $summary .= "🏷 หมวดหมู่: " . ($categoryLabels[$data['category']] ?? $data['category']) . "\n";
        $summary .= "📑 แบบฟอร์ม: " . ($formCatLabels[$data['form_category']] ?? $data['form_category']) . "\n";
        $summary .= "🔧 ประเภทงาน: " . ($workTypeLabels[$data['work_type']] ?? $data['work_type']) . "\n";
        $summary .= "📦 วิธีจัดซื้อ: " . ($procMethodLabels[$data['procurement_method']] ?? $data['procurement_method']) . "\n";
        $summary .= "📝 ชื่อรายการ: {$data['title']}\n";
        $summary .= "📄 รายละเอียด: {$data['description']}\n";
        $summary .= "🎯 วัตถุประสงค์: {$data['purpose']}\n";
        $summary .= "⚡ ความเร่งด่วน: " . ($priorityLabels[$data['priority']] ?? $data['priority']) . "\n";
        $summary .= "📅 ต้องการภายใน: " . \Carbon\Carbon::parse($data['required_date'])->format('d/m/Y') . "\n";
        $summary .= "💼 ในงบประมาณ: " . (($data['is_budgeted'] ?? true) ? '✅ ใช่' : '❌ ไม่ใช่') . "\n";

        if (!empty($data['budget_code'])) {
            $summary .= "🔖 รหัสงบประมาณ: {$data['budget_code']}\n";
        }
        if (!empty($data['project_code'])) {
            $summary .= "🏗 รหัสโครงการ: {$data['project_code']}\n";
        }
        if (!empty($data['procurement_budget'])) {
            $summary .= "💰 วงเงินงบประมาณ: " . number_format($data['procurement_budget'], 2) . " THB\n";
        }
        if (!empty($data['delivery_schedule'])) {
            $summary .= "🚚 กำหนดส่งมอบ: {$data['delivery_schedule']}\n";
        }
        if (!empty($data['payment_schedule'])) {
            $summary .= "💳 เงื่อนไขชำระ: {$data['payment_schedule']}\n";
        }
        if (!empty($data['notes'])) {
            $summary .= "📝 หมายเหตุ: {$data['notes']}\n";
        }

        // Items
        $summary .= "\n📦 <b>รายการสินค้า/บริการ ({$this->countItems($data)} รายการ)</b>\n";
        foreach ($data['items'] as $i => $item) {
            $no = $i + 1;
            $summary .= "  {$no}. {$item['description']}\n";
            $summary .= "     {$item['quantity']} {$item['unit_of_measure']} × " . number_format($item['estimated_unit_price'], 2) . " = " . number_format($item['estimated_amount'], 2) . " THB\n";
        }
        $summary .= "\n💰 <b>รวมทั้งสิ้น: " . number_format($totalAmount, 2) . " THB</b>\n";

        $this->sendMessage($chatId, $summary, [
            'inline_keyboard' => [
                [
                    ['text' => '✅ ยืนยัน สร้าง PR', 'callback_data' => 'pr_confirm'],
                    ['text' => '❌ ยกเลิก', 'callback_data' => 'pr_cancel'],
                ],
            ]
        ]);
    }

    protected function countItems(array $data): int
    {
        return count($data['items'] ?? []);
    }

    protected function flowRejectPR(string $chatId, string $reason, array $flow): void
    {
        $user = User::find($flow['user_id']);
        $pr = PurchaseRequisition::find($flow['pr_id']);

        Cache::forget("tg_flow:{$chatId}");

        if (!$user || !$pr || $pr->status !== 'pending_approval') {
            $this->sendMessage($chatId, "ไม่สามารถปฏิเสธ PR นี้ได้");
            return;
        }

        $pr->update([
            'status' => 'rejected',
            'rejected_by' => $user->id,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
            'rejected_notes' => $reason,
        ]);

        $this->sendMessage($chatId,
            "❌ <b>ปฏิเสธ PR แล้ว</b>\n\n" .
            "📋 {$pr->pr_number}\n" .
            "📝 {$pr->title}\n" .
            "📄 เหตุผล: {$reason}\n" .
            "👤 โดย: {$user->name}\n" .
            "🕐 เวลา: " . now()->format('d/m/Y H:i')
        );

        // Notify requester
        $this->notifyPRRejected($pr, $user, $reason);
    }

    // ==========================================
    // Callback Query Handler
    // ==========================================

    protected function handleCallbackQuery(array $callbackQuery): void
    {
        $chatId = (string) $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];
        $data = $callbackQuery['data'];
        $callbackId = $callbackQuery['id'];

        // Store username for later use
        if (isset($callbackQuery['from']['username'])) {
            Cache::put("tg_username:{$chatId}", $callbackQuery['from']['username'], 3600);
        }

        // Submit PR callback
        if (str_starts_with($data, 'pr_submit:')) {
            $prId = (int) str_replace('pr_submit:', '', $data);
            $this->handleSubmitPR($chatId, $messageId, $prId, $callbackId);
            return;
        }

        // PO callbacks (approve/reject)
        if (str_starts_with($data, 'po_')) {
            $this->handlePOCallback($chatId, $messageId, $data, $callbackId);
            return;
        }

        // PR creation flow callbacks
        if (str_starts_with($data, 'pr_')) {
            $this->handlePRCallback($chatId, $messageId, $data, $callbackId);
            return;
        }

        // Approve/Reject callbacks
        if (str_starts_with($data, 'approve_pr:') || str_starts_with($data, 'reject_pr:')) {
            $this->handleApprovalCallback($chatId, $messageId, $data, $callbackId);
            return;
        }

        $this->answerCallbackQuery($callbackId);
    }

    protected function handlePRCallback(string $chatId, int $messageId, string $data, string $callbackId): void
    {
        $flow = Cache::get("tg_flow:{$chatId}");

        if ($data === 'pr_cancel') {
            Cache::forget("tg_flow:{$chatId}");
            $this->answerCallbackQuery($callbackId, 'ยกเลิกแล้ว');
            $this->editMessage($chatId, $messageId, "❌ ยกเลิกการสร้างใบ PR");
            return;
        }

        if (!$flow) {
            $this->answerCallbackQuery($callbackId, 'เซสชั่นหมดอายุ กรุณา /newpr ใหม่');
            return;
        }

        $this->answerCallbackQuery($callbackId);

        // Company selection → Category
        if (str_starts_with($data, 'pr_company:')) {
            $companyId = (int) str_replace('pr_company:', '', $data);
            $flow['data']['company_id'] = $companyId;
            $flow['step'] = 'select_category';
            Cache::put("tg_flow:{$chatId}", $flow, 1800);

            $categories = PurchaseRequisition::getCategoryOptions();
            $buttons = [];
            foreach ($categories as $key => $label) {
                $buttons[] = [['text' => $label, 'callback_data' => "pr_cat:{$key}"]];
            }
            $buttons[] = [['text' => '❌ ยกเลิก', 'callback_data' => 'pr_cancel']];

            $this->editMessage($chatId, $messageId, "🏷 เลือกหมวดหมู่:", [
                'inline_keyboard' => $buttons
            ]);
            return;
        }

        // Category selection → Form Category
        if (str_starts_with($data, 'pr_cat:')) {
            $category = str_replace('pr_cat:', '', $data);
            $flow['data']['category'] = $category;
            $flow['step'] = 'select_form_category';
            Cache::put("tg_flow:{$chatId}", $flow, 1800);

            $formCats = PurchaseRequisition::getFormCategoryOptions();
            $buttons = [];
            foreach ($formCats as $key => $label) {
                $buttons[] = [['text' => $label, 'callback_data' => "pr_formcat:{$key}"]];
            }
            $buttons[] = [['text' => '❌ ยกเลิก', 'callback_data' => 'pr_cancel']];

            $catLabel = PurchaseRequisition::getCategoryOptions()[$category] ?? $category;
            $this->editMessage($chatId, $messageId, "✅ หมวดหมู่: {$catLabel}");
            $this->sendMessage($chatId, "📑 เลือกแบบฟอร์ม:", [
                'inline_keyboard' => $buttons
            ]);
            return;
        }

        // Form Category selection → Work Type
        if (str_starts_with($data, 'pr_formcat:')) {
            $formCat = str_replace('pr_formcat:', '', $data);
            $flow['data']['form_category'] = $formCat;
            $flow['step'] = 'select_work_type';
            Cache::put("tg_flow:{$chatId}", $flow, 1800);

            $formCatLabel = PurchaseRequisition::getFormCategoryOptions()[$formCat] ?? $formCat;
            $this->editMessage($chatId, $messageId, "✅ แบบฟอร์ม: {$formCatLabel}");
            $this->sendMessage($chatId, "🔧 เลือกประเภทงาน:", [
                'inline_keyboard' => [
                    [['text' => '🛒 ซื้อ', 'callback_data' => 'pr_work:buy']],
                    [['text' => '👷 จ้าง', 'callback_data' => 'pr_work:hire']],
                    [['text' => '🏠 เช่า', 'callback_data' => 'pr_work:rent']],
                    [['text' => '❌ ยกเลิก', 'callback_data' => 'pr_cancel']],
                ]
            ]);
            return;
        }

        // Work type selection → Procurement Method
        if (str_starts_with($data, 'pr_work:')) {
            $workType = str_replace('pr_work:', '', $data);
            $flow['data']['work_type'] = $workType;
            $flow['step'] = 'select_procurement_method';
            Cache::put("tg_flow:{$chatId}", $flow, 1800);

            $procMethods = PurchaseRequisition::getProcurementMethodOptions();
            $buttons = [];
            foreach ($procMethods as $key => $label) {
                $buttons[] = [['text' => $label, 'callback_data' => "pr_procm:{$key}"]];
            }
            $buttons[] = [['text' => '❌ ยกเลิก', 'callback_data' => 'pr_cancel']];

            $workLabel = PurchaseRequisition::getWorkTypeOptions()[$workType] ?? $workType;
            $this->editMessage($chatId, $messageId, "✅ ประเภทงาน: {$workLabel}");
            $this->sendMessage($chatId, "📦 เลือกวิธีจัดซื้อ:", [
                'inline_keyboard' => $buttons
            ]);
            return;
        }

        // Procurement Method selection → Enter Title
        if (str_starts_with($data, 'pr_procm:')) {
            $procMethod = str_replace('pr_procm:', '', $data);
            $flow['data']['procurement_method'] = $procMethod;
            $flow['step'] = 'enter_title';
            Cache::put("tg_flow:{$chatId}", $flow, 1800);

            $procLabel = PurchaseRequisition::getProcurementMethodOptions()[$procMethod] ?? $procMethod;
            $this->editMessage($chatId, $messageId, "✅ วิธีจัดซื้อ: {$procLabel}");
            $this->sendMessage($chatId, "📝 กรุณาพิมพ์ชื่อรายการ/สิ่งที่ต้องการจัดซื้อ:");
            return;
        }

        // Priority selection → Required Date
        if (str_starts_with($data, 'pr_priority:')) {
            $priority = str_replace('pr_priority:', '', $data);
            $flow['data']['priority'] = $priority;
            $flow['step'] = 'select_required_date';
            Cache::put("tg_flow:{$chatId}", $flow, 1800);

            $priorityLabels = ['low' => '🟢 ต่ำ', 'medium' => '🟡 ปกติ', 'high' => '🟠 สูง', 'urgent' => '🔴 เร่งด่วน'];
            $this->editMessage($chatId, $messageId, "✅ ความเร่งด่วน: " . ($priorityLabels[$priority] ?? $priority));
            $this->sendMessage($chatId, "📅 เลือกวันที่ต้องการสินค้า/บริการ:", [
                'inline_keyboard' => [
                    [
                        ['text' => '7 วัน (' . now()->addDays(7)->format('d/m') . ')', 'callback_data' => 'pr_reqdate:7'],
                        ['text' => '14 วัน (' . now()->addDays(14)->format('d/m') . ')', 'callback_data' => 'pr_reqdate:14'],
                    ],
                    [
                        ['text' => '30 วัน (' . now()->addDays(30)->format('d/m') . ')', 'callback_data' => 'pr_reqdate:30'],
                        ['text' => '60 วัน (' . now()->addDays(60)->format('d/m') . ')', 'callback_data' => 'pr_reqdate:60'],
                    ],
                    [['text' => '📅 ระบุวันที่เอง', 'callback_data' => 'pr_reqdate:custom']],
                    [['text' => '❌ ยกเลิก', 'callback_data' => 'pr_cancel']],
                ]
            ]);
            return;
        }

        // Required Date selection → Is Budgeted
        if (str_starts_with($data, 'pr_reqdate:')) {
            $dateOption = str_replace('pr_reqdate:', '', $data);

            if ($dateOption === 'custom') {
                $flow['step'] = 'enter_required_date';
                Cache::put("tg_flow:{$chatId}", $flow, 1800);
                $this->editMessage($chatId, $messageId, "📅 ระบุวันที่เอง");
                $this->sendMessage($chatId,
                    "กรุณาพิมพ์วันที่ต้องการ:\n" .
                    "รูปแบบ: <code>dd/mm/yyyy</code>\n" .
                    "ตัวอย่าง: <code>" . now()->addDays(7)->format('d/m/Y') . "</code>"
                );
                return;
            }

            $days = (int) $dateOption;
            $requiredDate = now()->addDays($days);
            $flow['data']['required_date'] = $requiredDate->format('Y-m-d');
            $flow['step'] = 'select_is_budgeted';
            Cache::put("tg_flow:{$chatId}", $flow, 1800);

            $this->editMessage($chatId, $messageId, "✅ ต้องการภายใน: {$requiredDate->format('d/m/Y')} ({$days} วัน)");
            $this->sendMessage($chatId, "💼 การจัดซื้อนี้อยู่ในงบประมาณหรือไม่?", [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ อยู่ในงบประมาณ', 'callback_data' => 'pr_budgeted:yes'],
                        ['text' => '❌ นอกงบประมาณ', 'callback_data' => 'pr_budgeted:no'],
                    ],
                    [['text' => '❌ ยกเลิก', 'callback_data' => 'pr_cancel']],
                ]
            ]);
            return;
        }

        // Is Budgeted selection → Budget Code
        if (str_starts_with($data, 'pr_budgeted:')) {
            $budgeted = str_replace('pr_budgeted:', '', $data);
            $flow['data']['is_budgeted'] = ($budgeted === 'yes');
            $flow['step'] = 'enter_budget_code';
            Cache::put("tg_flow:{$chatId}", $flow, 1800);

            $label = ($budgeted === 'yes') ? '✅ อยู่ในงบประมาณ' : '❌ นอกงบประมาณ';
            $this->editMessage($chatId, $messageId, "✅ งบประมาณ: {$label}");
            $this->sendMessage($chatId, "🔖 กรุณาระบุรหัสงบประมาณ (Budget Code):\n\n💡 พิมพ์ <code>-</code> หากไม่มี");
            return;
        }

        // Add more items or done
        if (str_starts_with($data, 'pr_additem:')) {
            $choice = str_replace('pr_additem:', '', $data);

            if ($choice === 'yes') {
                $itemCount = count($flow['data']['items'] ?? []);
                $flow['step'] = 'enter_item_description';
                Cache::put("tg_flow:{$chatId}", $flow, 1800);

                $this->editMessage($chatId, $messageId, "➕ เพิ่มรายการ");
                $this->sendMessage($chatId,
                    "📦 <b>เพิ่มรายการสินค้า/บริการ (รายการที่ " . ($itemCount + 1) . ")</b>\n\n" .
                    "กรุณาระบุรายละเอียดสินค้า/บริการ:"
                );
                return;
            }

            // Done - show summary
            $this->editMessage($chatId, $messageId, "✅ เพิ่มรายการเสร็จสิ้น");
            $this->showPRSummary($chatId, $flow);
            return;
        }

        // Confirm PR creation
        if ($data === 'pr_confirm') {
            $this->createPRFromFlow($chatId, $messageId, $flow);
            return;
        }
    }

    protected function createPRFromFlow(string $chatId, int $messageId, array $flow): void
    {
        $user = User::find($flow['user_id']);
        if (!$user) {
            $this->editMessage($chatId, $messageId, "เกิดข้อผิดพลาด: ไม่พบผู้ใช้");
            Cache::forget("tg_flow:{$chatId}");
            return;
        }

        $data = $flow['data'];
        $items = $data['items'] ?? [];
        $totalAmount = array_sum(array_column($items, 'estimated_amount'));

        try {
            $pr = PurchaseRequisition::create([
                'company_id'          => $data['company_id'],
                'pr_number'           => PurchaseRequisition::generatePRNumber(),
                'title'               => $data['title'],
                'description'         => $data['description'],
                'purpose'             => $data['purpose'] ?? null,
                'category'            => $data['category'],
                'form_category'       => $data['form_category'],
                'work_type'           => $data['work_type'],
                'procurement_method'  => $data['procurement_method'],
                'priority'            => $data['priority'],
                'total_amount'        => $totalAmount,
                'procurement_budget'  => $data['procurement_budget'] ?? null,
                'currency'            => 'THB',
                'is_budgeted'         => $data['is_budgeted'] ?? true,
                'budget_code'         => $data['budget_code'] ?? null,
                'project_code'        => $data['project_code'] ?? null,
                'delivery_schedule'   => $data['delivery_schedule'] ?? null,
                'payment_schedule'    => $data['payment_schedule'] ?? null,
                'notes'               => $data['notes'] ?? null,
                'requester_id'        => $user->id,
                'created_by'          => $user->id,
                'department_id'       => $user->department_id,
                'request_date'        => now(),
                'required_date'       => $data['required_date'],
                'status'              => 'draft',
            ]);

            // Create PR items
            foreach ($items as $index => $item) {
                PurchaseRequisitionItem::create([
                    'purchase_requisition_id' => $pr->id,
                    'line_number'             => $index + 1,
                    'description'             => $item['description'],
                    'quantity'                => $item['quantity'],
                    'unit_of_measure'         => $item['unit_of_measure'],
                    'estimated_unit_price'    => $item['estimated_unit_price'],
                    'estimated_amount'        => $item['estimated_amount'],
                    'specification'           => $item['specification'] ?? null,
                    'status'                  => 'pending',
                ]);
            }

            Cache::forget("tg_flow:{$chatId}");

            $itemCount = count($items);
            $this->editMessage($chatId, $messageId,
                "✅ <b>สร้างใบ PR สำเร็จ!</b>\n\n" .
                "📋 เลขที่: <b>{$pr->pr_number}</b>\n" .
                "📝 รายการ: {$pr->title}\n" .
                "📦 จำนวน: {$itemCount} รายการ\n" .
                "💰 รวม: " . number_format($totalAmount, 2) . " THB\n" .
                "📌 สถานะ: Draft\n\n" .
                "💡 เข้าระบบ VENDR เพื่อตรวจสอบและส่งอนุมัติ",
                [
                    'inline_keyboard' => [
                        [['text' => '📤 ส่งอนุมัติเลย', 'callback_data' => "pr_submit:{$pr->id}"]],
                    ]
                ]
            );

        } catch (\Exception $e) {
            Log::error("Telegram PR creation error: {$e->getMessage()}");
            Cache::forget("tg_flow:{$chatId}");
            $this->editMessage($chatId, $messageId, "เกิดข้อผิดพลาดในการสร้าง PR: {$e->getMessage()}");
        }
    }

    // ==========================================
    // PR Status & Listing
    // ==========================================

    protected function handleMyPR(string $chatId): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        $prs = PurchaseRequisition::where('requester_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($prs->isEmpty()) {
            $this->sendMessage($chatId, "คุณยังไม่มีใบ PR\nพิมพ์ /newpr เพื่อสร้างใบ PR ใหม่");
            return;
        }

        $statusIcons = [
            'draft' => '📝', 'pending_approval' => '⏳', 'approved' => '✅',
            'rejected' => '❌', 'in_process' => '🔄', 'completed' => '🏁', 'cancelled' => '🚫',
        ];

        $text = "📋 <b>ใบ PR ของคุณ (ล่าสุด 10 รายการ)</b>\n\n";
        foreach ($prs as $pr) {
            $icon = $statusIcons[$pr->status] ?? '📄';
            $amount = $pr->total_amount ? number_format($pr->total_amount, 2) : '-';
            $text .= "{$icon} <b>{$pr->pr_number}</b>\n";
            $text .= "   {$pr->title}\n";
            $text .= "   💰 {$amount} THB | สถานะ: {$pr->status}\n\n";
        }

        $this->sendMessage($chatId, $text);
    }

    protected function handlePendingApprovals(string $chatId): void
    {
        $user = $this->getLinkedUser($chatId);
        if (!$user) return;

        $query = PurchaseRequisition::where('status', 'pending_approval');

        // Filter by role
        if (!$user->hasRole('admin') && !$user->hasRole('procurement_manager')) {
            if ($user->hasRole('department_head') && $user->department_id) {
                $query->where('department_id', $user->department_id);
            } else {
                $this->sendMessage($chatId, "คุณไม่มีสิทธิ์ดูรายการรออนุมัติ");
                return;
            }
        }

        $prs = $query->orderBy('created_at', 'desc')->limit(10)->get();

        if ($prs->isEmpty()) {
            $this->sendMessage($chatId, "✅ ไม่มีใบ PR ที่รออนุมัติ");
            return;
        }

        $text = "⏳ <b>ใบ PR รออนุมัติ</b>\n\n";
        $buttons = [];

        foreach ($prs as $pr) {
            $requester = $pr->requester->name ?? 'N/A';
            $amount = $pr->total_amount ? number_format($pr->total_amount, 2) : '-';
            $text .= "📋 <b>{$pr->pr_number}</b>\n";
            $text .= "   {$pr->title}\n";
            $text .= "   👤 ผู้ขอ: {$requester} | 💰 {$amount} THB\n\n";

            $buttons[] = [
                ['text' => "✅ {$pr->pr_number}", 'callback_data' => "approve_pr:{$pr->id}"],
                ['text' => "❌ {$pr->pr_number}", 'callback_data' => "reject_pr:{$pr->id}"],
            ];
        }

        $this->sendMessage($chatId, $text, ['inline_keyboard' => $buttons]);
    }

    // ==========================================
    // Approve/Reject via Telegram
    // ==========================================

    protected function handleApprovalCallback(string $chatId, int $messageId, string $data, string $callbackId): void
    {
        $user = User::where('telegram_chat_id', $chatId)->first();
        if (!$user) {
            $this->answerCallbackQuery($callbackId, 'กรุณา /register ก่อน');
            return;
        }

        $parts = explode(':', $data, 2);
        $action = $parts[0];
        $prId = (int) $parts[1];

        $pr = PurchaseRequisition::find($prId);
        if (!$pr) {
            $this->answerCallbackQuery($callbackId, 'ไม่พบใบ PR นี้');
            return;
        }

        if ($pr->status !== 'pending_approval') {
            $this->answerCallbackQuery($callbackId, "PR นี้ไม่ได้อยู่ในสถานะรออนุมัติ (สถานะ: {$pr->status})");
            return;
        }

        // Check permission
        $canApprove = $user->hasRole('admin')
            || $user->hasRole('procurement_manager')
            || ($user->hasRole('department_head') && $user->department_id === $pr->department_id)
            || $pr->pr_approver_id === $user->id;

        if (!$canApprove) {
            $this->answerCallbackQuery($callbackId, 'คุณไม่มีสิทธิ์อนุมัติ PR นี้');
            return;
        }

        if ($action === 'approve_pr') {
            $pr->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $this->answerCallbackQuery($callbackId, '✅ อนุมัติสำเร็จ');
            $this->editMessage($chatId, $messageId,
                "✅ <b>อนุมัติแล้ว</b>\n\n" .
                "📋 {$pr->pr_number}\n" .
                "📝 {$pr->title}\n" .
                "👤 อนุมัติโดย: {$user->name}\n" .
                "🕐 เวลา: " . now()->format('d/m/Y H:i')
            );

            // Notify requester via Telegram
            $this->notifyPRApproved($pr, $user);

        } elseif ($action === 'reject_pr') {
            // Ask for rejection reason
            Cache::put("tg_reject:{$chatId}", [
                'pr_id' => $prId,
                'message_id' => $messageId,
            ], 600);

            // Temporarily set flow to capture rejection reason
            Cache::put("tg_flow:{$chatId}", [
                'step' => 'enter_rejection_reason',
                'pr_id' => $prId,
                'message_id' => $messageId,
                'user_id' => $user->id,
            ], 600);

            $this->answerCallbackQuery($callbackId);
            $this->sendMessage($chatId,
                "❌ <b>Reject PR {$pr->pr_number}</b>\n\n" .
                "กรุณาพิมพ์เหตุผลในการปฏิเสธ:"
            );
        }
    }

    // ==========================================
    // Submit PR callback
    // ==========================================

    protected function handleSubmitPR(string $chatId, int $messageId, int $prId, string $callbackId): void
    {
        $user = User::where('telegram_chat_id', $chatId)->first();
        if (!$user) return;

        $pr = PurchaseRequisition::find($prId);
        if (!$pr || $pr->status !== 'draft') {
            $this->answerCallbackQuery($callbackId, 'ไม่สามารถส่งอนุมัติได้');
            return;
        }

        $pr->update([
            'status' => 'pending_approval',
            'submitted_at' => now(),
        ]);

        $this->answerCallbackQuery($callbackId, '📤 ส่งอนุมัติแล้ว');
        $this->editMessage($chatId, $messageId,
            "📤 <b>ส่งอนุมัติแล้ว!</b>\n\n" .
            "📋 เลขที่: {$pr->pr_number}\n" .
            "📝 รายการ: {$pr->title}\n" .
            "📌 สถานะ: รออนุมัติ"
        );

        // Dispatch event to notify approvers
        try {
            event(new \App\Events\PurchaseRequisitionSubmitted($pr, $user));
        } catch (\Exception $e) {
            Log::error("Failed to dispatch PR submitted event: {$e->getMessage()}");
        }
    }

    // ==========================================
    // Notification Methods (called from Listeners)
    // ==========================================

    public function notifyPRSubmitted(PurchaseRequisition $pr, User $submitter): void
    {
        $approvers = $this->getApprovers($pr);

        $text = "📋 <b>ใบ PR ใหม่รออนุมัติ</b>\n\n" .
            "เลขที่: <b>{$pr->pr_number}</b>\n" .
            "รายการ: {$pr->title}\n" .
            "👤 ผู้ขอ: {$submitter->name}\n" .
            "🏢 แผนก: " . ($pr->department->name ?? '-') . "\n" .
            "💰 งบ: " . number_format($pr->total_amount ?? 0, 2) . " THB\n" .
            "⚡ ความเร่งด่วน: {$pr->priority}";

        $buttons = [
            'inline_keyboard' => [
                [
                    ['text' => '✅ Approve', 'callback_data' => "approve_pr:{$pr->id}"],
                    ['text' => '❌ Reject', 'callback_data' => "reject_pr:{$pr->id}"],
                ],
            ]
        ];

        foreach ($approvers as $approver) {
            if ($approver->telegram_chat_id && $approver->id !== $submitter->id) {
                $this->sendMessage($approver->telegram_chat_id, $text, $buttons);
            }
        }
    }

    public function notifyPRApproved(PurchaseRequisition $pr, User $approver): void
    {
        $requester = $pr->requester;
        if ($requester && $requester->telegram_chat_id) {
            $this->sendMessage($requester->telegram_chat_id,
                "✅ <b>PR ได้รับการอนุมัติ!</b>\n\n" .
                "📋 เลขที่: {$pr->pr_number}\n" .
                "📝 รายการ: {$pr->title}\n" .
                "👤 อนุมัติโดย: {$approver->name}\n" .
                "🕐 เวลา: " . now()->format('d/m/Y H:i')
            );
        }
    }

    public function notifyPRRejected(PurchaseRequisition $pr, User $rejector, string $reason = ''): void
    {
        $requester = $pr->requester;
        if ($requester && $requester->telegram_chat_id) {
            $text = "❌ <b>PR ถูกปฏิเสธ</b>\n\n" .
                "📋 เลขที่: {$pr->pr_number}\n" .
                "📝 รายการ: {$pr->title}\n" .
                "👤 ปฏิเสธโดย: {$rejector->name}\n";

            if ($reason) {
                $text .= "📄 เหตุผล: {$reason}\n";
            }

            $this->sendMessage($requester->telegram_chat_id, $text);
        }
    }

    // ==========================================
    // Helpers
    // ==========================================

    protected function getLinkedUser(string $chatId): ?User
    {
        $user = User::where('telegram_chat_id', $chatId)->first();
        if (!$user) {
            $this->sendMessage($chatId,
                "⚠️ Telegram ยังไม่ได้เชื่อมต่อกับ VENDR\n\n" .
                "พิมพ์ /register email@company.com เพื่อเชื่อมต่อ"
            );
            return null;
        }
        return $user;
    }

    protected function getApprovers(PurchaseRequisition $pr): \Illuminate\Support\Collection
    {
        $approvers = collect();

        // Admins
        $approvers = $approvers->merge(
            User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->get()
        );

        // Procurement Managers
        $approvers = $approvers->merge(
            User::whereHas('roles', fn($q) => $q->where('name', 'procurement_manager'))->get()
        );

        // Department Head
        if ($pr->department_id) {
            $approvers = $approvers->merge(
                User::whereHas('roles', fn($q) => $q->where('name', 'department_head'))
                    ->where('department_id', $pr->department_id)
                    ->get()
            );
        }

        // Specific approver
        if ($pr->pr_approver_id) {
            $specific = User::find($pr->pr_approver_id);
            if ($specific) $approvers->push($specific);
        }

        return $approvers->unique('id');
    }
}
