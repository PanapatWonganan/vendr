<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseRequisitionController;
use App\Http\Middleware\CompanyMiddleware;
use Illuminate\Support\Facades\Route;

// ============================================================
// Root redirect → Filament admin panel
// ============================================================
Route::get('/', function () {
    return redirect('/admin');
})->name('welcome');

// ============================================================
// Company API routes (ใช้โดย CompanySelector widget / AJAX)
// company.select + company.set ถูกลบแล้ว — ใช้ Filament CompanySelect page เท่านั้น
// ============================================================
Route::middleware(['auth', 'throttle:10,1'])->group(function () {
    Route::post('/company/switch', [CompanyController::class, 'switchCompany'])->name('company.switch');
    Route::post('/company/clear', [CompanyController::class, 'clearCompany'])->name('company.clear');
});

// ============================================================
// Dashboard redirect → Filament admin (ไม่มี Blade dashboard แยกแล้ว)
// ============================================================
Route::get('/dashboard', fn () => redirect('/admin'))
    ->middleware('auth')
    ->name('dashboard');

// Essential routes for Filament functionality
Route::middleware(['auth', CompanyMiddleware::class])->group(function () {
    // Profile routes (still needed for user profile management)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // File download routes
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

    // Legacy named routes still referenced by resources/views/layouts/app.blade.php
    // (sidebar ของหน้า /profile) — ถ้าไม่มีชื่อ route พวกนี้ หน้า profile จะ 500
    Route::get('/purchase-requisitions', fn () => redirect(\App\Filament\Resources\PurchaseRequisitionResource::getUrl()))
        ->name('purchase-requisitions.index');
    Route::get('/purchase-requisitions/my-requests', fn () => redirect(\App\Filament\Resources\PurchaseRequisitionResource::getUrl('my-requests')))
        ->name('purchase-requisitions.my-requests');
    Route::get('/purchase-requisitions/pending-approvals', fn () => redirect(\App\Filament\Resources\PurchaseRequisitionResource::getUrl('pending-approvals')))
        ->name('purchase-requisitions.pending-approvals');
    Route::get('/purchase-orders', fn () => redirect(\App\Filament\Resources\PurchaseOrderResource::getUrl()))
        ->name('purchase-orders.index');
    Route::get('/purchase-orders/pending-approvals', fn () => redirect(\App\Filament\Resources\PurchaseOrderResource::getUrl('pending-approvals')))
        ->name('purchase-orders.pending-approvals');
    Route::get('/value-analysis', fn () => redirect(\App\Filament\Resources\ValueAnalysisResource::getUrl()))
        ->name('value-analysis.index');
});

// Telegram Bot Webhook (token validated in controller)
Route::post('/telegram/webhook/{token}', [\App\Http\Controllers\TelegramWebhookController::class, 'handle'])
    ->name('telegram.webhook')
    ->middleware('throttle:60,1')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Telegram polling command for local dev (no webhook needed)
Route::get('/telegram/polling', function () {
    if (app()->environment('production')) {
        abort(404);
    }

    $bot = app(\App\Services\TelegramBotService::class);
    $offset = 0;
    $rounds = 0;
    $maxRounds = 100;

    while ($rounds < $maxRounds) {
        $response = \Illuminate\Support\Facades\Http::get(
            'https://api.telegram.org/bot'.config('telegram.bot_token').'/getUpdates',
            ['offset' => $offset, 'timeout' => 30]
        )->json();

        if (! empty($response['result'])) {
            foreach ($response['result'] as $update) {
                $bot->handleUpdate($update);
                $offset = $update['update_id'] + 1;
            }
        }
        $rounds++;
    }

    return 'Polling ended after '.$maxRounds.' rounds';
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

require __DIR__.'/auth.php';
