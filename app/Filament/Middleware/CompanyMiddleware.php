<?php

namespace App\Filament\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\BaseModel;
use App\Filament\Pages\CompanySelect;

class CompanyMiddleware
{
    public function __construct()
    {
        \Log::info('CompanyMiddleware __construct called');
    }

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

        \Log::info('CompanyMiddleware check', [
            'path' => $request->path(),
            'company_id' => $companyId,
            'company_connection' => $companyConnection,
            'has_company' => !empty($companyId),
        ]);

        if (!$companyId || !$companyConnection) {
            // Only redirect to company selection if accessing other admin routes
            if ($request->is('admin/*')) {
                \Log::info('CompanyMiddleware redirecting to company select');
                return redirect()->to(CompanySelect::getUrl());
            }
        }

        // Set database connection for models (ปิดการใช้งานชั่วคราว เพราะใช้ single database)
        // if ($companyConnection) {
        //     BaseModel::setCompanyConnection($companyConnection);
        //     config(['database.default' => $companyConnection]);
        // }

        return $next($request);
    }
}