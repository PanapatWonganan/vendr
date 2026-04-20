<?php

namespace App\Http\Middleware;

use App\Filament\Pages\CompanySelect;
use App\Models\BaseModel;
use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CompanyMiddleware
{
    /**
     * Handle an incoming request.
     * ใช้ middleware ตัวนี้ตัวเดียวทั้ง web routes และ Filament panel.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow login / logout routes to pass through
        if ($request->routeIs('filament.admin.auth.login') ||
            $request->is('admin/login*') ||
            $request->is('admin/logout*')) {
            return $next($request);
        }

        // Allow Filament company selection page to pass through
        if ($request->is('admin/company-select')) {
            return $next($request);
        }

        $companyId = session('company_id');
        $companyConnection = session('company_connection');

        // ตรวจสอบว่า user ได้เลือก company แล้วหรือยัง
        if (!$companyId || !$companyConnection) {
            return redirect()->to(CompanySelect::getUrl());
        }

        // Validate company still exists and is active (cached 60s)
        $isActive = Cache::remember("company_active_{$companyId}", 60, function () use ($companyId) {
            return Company::where('id', $companyId)
                ->where('is_active', true)
                ->exists();
        });

        if (!$isActive) {
            session()->forget(['company_id', 'company_connection', 'company_name', 'company_display_name']);
            Cache::forget("company_active_{$companyId}");
            return redirect()->to(CompanySelect::getUrl())
                ->with('error', 'บริษัทที่เลือกไม่สามารถใช้งานได้แล้ว กรุณาเลือกบริษัทใหม่');
        }

        // Set database connection สำหรับ request นี้
        BaseModel::setCompanyConnection('mysql');

        // Share company info กับ views
        $company = Company::find($companyId);
        if ($company) {
            $request->merge(['current_company' => $company]);
            view()->share('currentCompany', $company);
        }

        return $next($request);
    }
}
