<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Company;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompanySelect extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static string $view = 'filament.pages.company-select';
    protected static ?string $title = 'เลือกบริษัท';
    protected static bool $shouldRegisterNavigation = false;
    
    public ?array $data = [];
    public $selectedCompany = null;

    public function mount(): void
    {
        // If already selected, redirect to dashboard
        if (session('company_id')) {
            $this->redirect('/admin');
        }
        
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('company_id')
                    ->label('เลือกบริษัท')
                    ->options(Company::where('is_active', true)->pluck('display_name', 'id'))
                    ->required()
                    ->placeholder('กรุณาเลือกบริษัทที่ต้องการทำงาน')
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedCompany = $state ? Company::find($state) : null;
                    }),
            ])
            ->statePath('data');
    }

    public function selectCompany(): void
    {
        $data = $this->form->getState();

        if (!isset($data['company_id'])) {
            return;
        }

        $company = Company::find($data['company_id']);

        if (!$company || !$company->is_active) {
            $this->addError('company_id', 'บริษัทที่เลือกไม่พร้อมใช้งาน');
            return;
        }

        // Set session data (ใช้ single database)
        session([
            'company_id' => $company->id,
            'company_name' => $company->name,
            'company_connection' => 'mysql', // ใช้ default connection
            'company_display_name' => $company->display_name,
        ]);

        // Redirect to admin dashboard
        $this->redirect('/admin');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('select')
                ->label('เข้าสู่ระบบ')
                ->action('selectCompany')
                ->color('primary')
                ->icon('heroicon-o-arrow-right'),
        ];
    }
}