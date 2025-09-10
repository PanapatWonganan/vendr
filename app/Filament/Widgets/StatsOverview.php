<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\User;
use App\Models\Vendor;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = session('company_id');
        
        if (!$companyId) {
            return [
                Stat::make('No Company Selected', 0)
                    ->description('Please select a company first')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('warning'),
            ];
        }

        return [
            Stat::make('Total Users', User::count())
                ->description('Active users in system')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->chart([7, 2, 10, 3, 15, 4, 17]),
            
            Stat::make('Purchase Requisitions', 
                PurchaseRequisition::where('company_id', $companyId)->count())
                ->description('Current company PRs')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning')
                ->chart([15, 4, 10, 2, 12, 4, 12]),
            
            Stat::make('Purchase Orders', 
                PurchaseOrder::where('company_id', $companyId)->count())
                ->description('Current company POs')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('success')
                ->chart([3, 2, 5, 7, 9, 2, 10]),
            
            Stat::make('Vendors', 
                Vendor::where('company_id', $companyId)->count())
                ->description('Current company vendors')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('info')
                ->chart([1, 2, 3, 4, 5, 6, 7]),
        ];
    }
}