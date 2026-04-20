<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;

class CompanySelector extends Widget
{
    protected static string $view = 'filament.widgets.company-selector';
    
    protected static ?int $sort = -1; // Show at top
    protected static bool $isLazy = false;
    
    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        // ไม่ auto-set company_id อีกต่อไป — user ต้องเลือกเองผ่าน CompanySelect page
        // (ป้องกัน multi-tenancy bypass)
        $currentCompany = session('company_id') ?
            Company::find(session('company_id')) : null;

        $companies = Company::where('is_active', true)->get();

        return [
            'currentCompany' => $currentCompany,
            'companies' => $companies,
            'user' => Auth::user(),
        ];
    }

    public function switchCompany($companyId)
    {
        $user = Auth::user();

        if (!$user) {
            abort(401);
        }

        $company = Company::find($companyId);

        if (!$company || !$company->isActive()) {
            return;
        }

        // ตรวจสิทธิ์ user ว่ามีสิทธิ์เข้าถึง company นี้หรือไม่
        // ปัจจุบัน design คือ user ทุกคนเข้าทุก active company ได้
        // แต่บังคับ re-auth check ก่อน switch เพื่อกันการเรียกผ่าน direct API
        if (!$user->hasAnyRole(['admin', 'procurement_manager', 'procurement_officer', 'requester', 'approver', 'viewer', 'user'])) {
            // user ไม่มี role ที่ active อย่างน้อย 1 อัน → ห้าม switch
            abort(403, 'บัญชีของคุณไม่มี role ที่ใช้งานได้');
        }

        // Regenerate session ป้องกัน session fixation ก่อน set ค่าใหม่
        session()->regenerate();

        // Set session data — consistent keys ทุกจุด
        session([
            'company_id' => $company->id,
            'company_name' => $company->display_name,
            'company_connection' => 'mysql',
            'company_display_name' => $company->display_name,
        ]);

        // Clear cache ที่อาจมีข้อมูลเก่า
        \Illuminate\Support\Facades\Cache::forget("company_active_{$company->id}");

        // Refresh page
        return redirect('/admin');
    }
}