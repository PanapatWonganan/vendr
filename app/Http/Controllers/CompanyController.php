<?php

namespace App\Http\Controllers;

use App\Models\BaseModel;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * Show company selection page
     */
    public function select()
    {
        $companies = Company::getActiveCompanies();
        $currentCompany = session('company_id') ? Company::find(session('company_id')) : null;
        
        return view('company.select', compact('companies', 'currentCompany'));
    }

    /**
     * Set selected company
     */
    public function setCompany(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $company = Company::findOrFail($request->company_id);

        if (!$company->isActive()) {
            return back()->with('error', 'บริษัทที่เลือกไม่สามารถใช้งานได้');
        }

        // Set session
        session([
            'company_id' => $company->id,
            'company_connection' => $company->getDatabaseConnection(),
            'company_name' => $company->display_name,
        ]);

        // Set database connection
        BaseModel::setCompanyConnection($company->getDatabaseConnection());

        return redirect('/admin')->with('success', 'เลือกบริษัท ' . $company->display_name . ' เรียบร้อยแล้ว');
    }

    /**
     * Switch company (quick switch from navbar)
     */
    public function switchCompany(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $company = Company::findOrFail($request->company_id);

        if (!$company->isActive()) {
            return response()->json(['error' => 'บริษัทที่เลือกไม่สามารถใช้งานได้'], 400);
        }

        // Set session
        session([
            'company_id' => $company->id,
            'company_connection' => $company->getDatabaseConnection(),
            'company_name' => $company->display_name,
        ]);

        // Set database connection
        BaseModel::setCompanyConnection($company->getDatabaseConnection());

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
        session()->forget(['company_id', 'company_connection', 'company_name']);
        BaseModel::clearCompanyConnection();
        
        return redirect()->route('company.select')->with('success', 'ออกจากบริษัทเรียบร้อยแล้ว');
    }
}
