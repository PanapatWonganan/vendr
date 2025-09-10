<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\CompanySelector;
use App\Filament\Middleware\CompanyMiddleware;
use App\Filament\Pages\CompanySelect;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Innobic Procurement System')
            ->brandLogo(asset('assets/img/innobic.png'))
            ->favicon(asset('assets/img/innobic.png'))
            ->colors([
                'primary' => Color::Blue,
                'warning' => Color::Orange,
                'success' => Color::Green,
                'danger' => Color::Red,
            ])
            ->darkMode(true)
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->font('Sarabun')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
                CompanySelect::class,
                \App\Filament\Pages\CalendarPage::class,
            ])
            ->widgets([
                CompanySelector::class,
                \App\Filament\Widgets\DashboardStatsOverview::class,
                \App\Filament\Widgets\VendorGradeStats::class,
                \App\Filament\Widgets\VendorGradeApexChart::class,
                \App\Filament\Widgets\ValueAnalysisStats::class,
                \App\Filament\Widgets\ValueAnalysisSavingsChart::class,
                \App\Filament\Widgets\CalendarWidget::class,
                \App\Filament\Widgets\ProcurementStatsOverview::class,
                \App\Filament\Widgets\PendingApprovalsChart::class,
                \App\Filament\Widgets\ProcurementReportsWidget::class,
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                CompanyMiddleware::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentFullCalendarPlugin::make(),
                \Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin::make(),
            ])
            ->navigationItems([
                NavigationItem::make('จัดซื้อตรง ≤ 10,000 บาท')
                    ->url(fn () => \App\Filament\Resources\PurchaseRequisitionResource::getUrl('create-direct-small'))
                    ->icon('heroicon-o-shopping-cart')
                    ->group('Procurement Management')
                    ->sort(10),
                NavigationItem::make('จัดซื้อตรง ≤ 100,000 บาท')
                    ->url(fn () => \App\Filament\Resources\PurchaseRequisitionResource::getUrl('create-direct-medium'))
                    ->icon('heroicon-o-shopping-bag')
                    ->group('Procurement Management')
                    ->sort(11),
                NavigationItem::make('คำขอของฉัน')
                    ->url(fn () => \App\Filament\Resources\PurchaseRequisitionResource::getUrl('my-requests'))
                    ->icon('heroicon-o-document-text')
                    ->group('Procurement Management')
                    ->sort(12),
                NavigationItem::make('รออนุมัติ')
                    ->url(fn () => \App\Filament\Resources\PurchaseRequisitionResource::getUrl('pending-approvals'))
                    ->icon('heroicon-o-clock')
                    ->group('Procurement Management')
                    ->sort(13)
                    ->badge(function () {
                        return \App\Models\PurchaseRequisition::where('status', 'pending_approval')->count();
                    }),
            ]);
    }
}
