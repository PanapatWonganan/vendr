<?php

namespace App\Filament\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\BaseModel;

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

        // Allow company selection routes to pass through
        if ($request->routeIs('company.select') ||
            $request->routeIs('company.set') ||
            $request->is('company/*')) {
            return $next($request);
        }

        // Check if user has selected a company
        $companyId = session('company_id');
        $companyConnection = session('company_connection');

        if (!$companyId || !$companyConnection) {
            // Only redirect to company selection if accessing other admin routes
            if ($request->is('admin/*')) {
                return redirect()->route('company.select');
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