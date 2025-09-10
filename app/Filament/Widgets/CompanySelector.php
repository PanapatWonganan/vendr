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
        // ถ้ายังไม่มี session company_id ให้เซ็ตเป็น default (company 1)
        if (!session('company_id')) {
            $defaultCompany = Company::where('is_active', true)->first();
            if ($defaultCompany) {
                session([
                    'company_id' => $defaultCompany->id,
                    'company_name' => $defaultCompany->name,
                    'company_connection' => $defaultCompany->database_connection,
                    'company_display_name' => $defaultCompany->display_name,
                ]);
            }
        }

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
        $company = Company::find($companyId);
        
        if (!$company || !$company->is_active) {
            return;
        }

        // Test database connection
        try {
            \DB::connection($company->database_connection)->getPdo();
        } catch (\Exception $e) {
            return;
        }

        // Set session data
        session([
            'company_id' => $company->id,
            'company_name' => $company->name,
            'company_connection' => $company->database_connection,
            'company_display_name' => $company->display_name,
        ]);

        // Refresh page
        return redirect('/admin');
    }
}