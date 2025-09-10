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
        // Check if user has selected a company
        $companyId = session('company_id');
        $companyConnection = session('company_connection');

        if (!$companyId || !$companyConnection) {
            // Redirect to company selection page instead of auto-setting default
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