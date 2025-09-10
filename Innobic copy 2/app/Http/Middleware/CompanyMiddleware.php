<?php

namespace App\Http\Middleware;

use App\Models\BaseModel;
use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ถ้าเป็นหน้า company selection ให้ผ่านไป
        if ($request->routeIs('company.select') || $request->routeIs('company.set')) {
            return $next($request);
        }

        // ตรวจสอบว่า user ได้เลือก company แล้วหรือยัง
        if (!session('company_id') || !session('company_connection')) {
            return redirect()->route('company.select');
        }

        // ตรวจสอบว่า company ยังมีอยู่และ active หรือไม่
        $company = Company::find(session('company_id'));
        if (!$company || !$company->isActive()) {
            // ถ้า company ไม่มีหรือไม่ active ให้ clear session และ redirect
            session()->forget(['company_id', 'company_connection', 'company_name']);
            return redirect()->route('company.select');
        }

        // Set database connection สำหรับ request นี้
        BaseModel::setCompanyConnection($company->getDatabaseConnection());

        // เพิ่มข้อมูล company ใน request เพื่อใช้ใน view
        $request->merge([
            'current_company' => $company
        ]);

        // Share company info กับ views
        view()->share('currentCompany', $company);

        return $next($request);
    }
}
