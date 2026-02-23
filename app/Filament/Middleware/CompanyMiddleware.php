<?php

namespace App\Filament\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\BaseModel;
use App\Filament\Pages\CompanySelect;

class CompanyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Allow login routes to pass through without company check
        if ($request->routeIs('filament.admin.auth.login') ||
            $request->is('admin/login*') ||
            $request->is('admin/logout*')) {
            return $next($request);
        }

        // Allow company selection page to pass through
        if ($request->is('admin/company-select')) {
            return $next($request);
        }

        // Check if user has selected a company
        $companyId = session('company_id');
        $companyConnection = session('company_connection');

        if (!$companyId || !$companyConnection) {
            // Only redirect to company selection if accessing other admin routes
            if ($request->is('admin/*')) {
                return redirect()->to(CompanySelect::getUrl());
            }
        } else {
            // Validate that the selected company still exists and is active (cached 60s)
            $isActive = Cache::remember("company_active_{$companyId}", 60, function () use ($companyId) {
                return \App\Models\Company::where('id', $companyId)
                    ->where('is_active', true)
                    ->exists();
            });

            if (!$isActive) {
                session()->forget(['company_id', 'company_connection', 'company_name']);
                Cache::forget("company_active_{$companyId}");

                if ($request->is('admin/*')) {
                    return redirect()->to(CompanySelect::getUrl())
                        ->with('error', 'บริษัทที่เลือกไม่สามารถใช้งานได้แล้ว กรุณาเลือกบริษัทใหม่');
                }
            }
        }

        return $next($request);
    }
}