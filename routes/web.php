<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseRequisitionController;
use App\Http\Middleware\CompanyMiddleware;
use Illuminate\Support\Facades\Route;

// Redirect root to Filament admin panel
Route::get('/', function () {
    return redirect('/admin');
})->name('welcome');

// Company selection routes (without company middleware)
Route::middleware('auth')->group(function () {
    Route::get('/company/select', [CompanyController::class, 'select'])->name('company.select');
    Route::post('/company/set', [CompanyController::class, 'setCompany'])->name('company.set');
    Route::post('/company/switch', [CompanyController::class, 'switchCompany'])->name('company.switch');
    Route::post('/company/clear', [CompanyController::class, 'clearCompany'])->name('company.clear');
});

// Dashboard route with vendor statistics
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', CompanyMiddleware::class])
    ->name('dashboard');

// Essential routes for Filament functionality
Route::middleware(['auth', CompanyMiddleware::class])->group(function () {
    // Profile routes (still needed for user profile management)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // File download routes (if still needed by Filament)
    Route::get('/po-files/{file}/download', [PurchaseOrderController::class, 'downloadFile'])
        ->name('po-files.download');
    Route::get('/pr-attachments/{attachment}/download', [PurchaseRequisitionController::class, 'downloadAttachment'])
        ->name('pr-attachments.download');
    
    // Knowledge Base routes
    Route::get('/admin/knowledge-articles/{article}/view', function (App\Models\KnowledgeArticle $article) {
        $article->incrementViews();
        return view('knowledge-article-view', compact('article'));
    })->name('knowledge.view');
    
    Route::post('/admin/knowledge-articles/{article}/increment-views', function (App\Models\KnowledgeArticle $article) {
        $article->incrementViews();
        return response()->json(['success' => true]);
    })->name('knowledge.increment-views');
    
    // Note: GR/MR now managed via Filament Admin Panel only
});

require __DIR__.'/auth.php';
