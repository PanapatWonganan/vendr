<?php

namespace App\Http\Controllers;

use App\Filament\Pages\CompanySelect;
use App\Models\BaseModel;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * Switch company (quick switch from navbar / AJAX)
     */
    public function switchCompany(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $company = Company::findOrFail($request->company_id);

        if (!$company->isActive()) {
            return response()->json(['error' => 'บริษัทที่เลือกไม่สามารถใช้งานได้'], 400);
        }

        // Authorization: user ต้องมี active role อย่างน้อย 1 อัน
        if ($user->activeRoles()->count() === 0) {
            \Illuminate\Support\Facades\Log::warning('Company switch denied: user has no active role', [
                'user_id' => $user->id,
                'requested_company_id' => $company->id,
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'คุณไม่มีสิทธิ์เข้าถึงบริษัทนี้'], 403);
        }

        $previousCompanyId = session('company_id');

        // Regenerate session เพื่อป้องกัน session fixation
        $request->session()->regenerate();

        // Set session — consistent keys ทุกจุด
        session([
            'company_id' => $company->id,
            'company_connection' => 'mysql',
            'company_name' => $company->display_name,
            'company_display_name' => $company->display_name,
        ]);

        // Set database connection
        BaseModel::setCompanyConnection('mysql');

        // Invalidate cached active status เผื่อมีการเปลี่ยนแปลง
        \Illuminate\Support\Facades\Cache::forget("company_active_{$company->id}");

        // Audit log
        \Illuminate\Support\Facades\Log::info('Company switched', [
            'user_id' => $user->id,
            'from_company_id' => $previousCompanyId,
            'to_company_id' => $company->id,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'เปลี่ยนบริษัทเป็น ' . $company->display_name . ' เรียบร้อยแล้ว',
            'company' => [
                'id' => $company->id,
                'name' => $company->display_name,
                'logo' => $company->getLogoUrl(),
            ]
        ]);
    }

    /**
     * Clear company selection (logout from company)
     */
    public function clearCompany()
    {
        session()->forget(['company_id', 'company_connection', 'company_name', 'company_display_name']);
        BaseModel::clearCompanyConnection();

        return redirect()->to(CompanySelect::getUrl())->with('success', 'ออกจากบริษัทเรียบร้อยแล้ว');
    }
}
