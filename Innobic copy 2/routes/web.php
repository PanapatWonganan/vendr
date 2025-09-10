<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContractApprovalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseRequisitionController;
use App\Http\Controllers\ValueAnalysisController;
use App\Http\Controllers\VendorController;
use App\Http\Middleware\CheckAdminRole;
use App\Http\Middleware\CompanyMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Company selection routes (without company middleware)
Route::middleware('auth')->group(function () {
    Route::get('/company/select', [CompanyController::class, 'select'])->name('company.select');
    Route::post('/company/set', [CompanyController::class, 'setCompany'])->name('company.set');
    Route::post('/company/switch', [CompanyController::class, 'switchCompany'])->name('company.switch');
    Route::post('/company/clear', [CompanyController::class, 'clearCompany'])->name('company.clear');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', CompanyMiddleware::class])
    ->name('dashboard');

// Dashboard calendar drag & drop route
Route::post('/dashboard/update-event-date', [DashboardController::class, 'updateEventDate'])
    ->middleware(['auth', 'verified', CompanyMiddleware::class])
    ->name('dashboard.update-event-date');

Route::middleware(['auth', CompanyMiddleware::class])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Purchase Requisitions routes
    Route::resource('purchase-requisitions', PurchaseRequisitionController::class);
    
    // Additional Purchase Requisition routes
    Route::get('/purchase-requisitions/my/requests', [PurchaseRequisitionController::class, 'myRequests'])
        ->name('purchase-requisitions.my-requests');
    Route::get('/purchase-requisitions/pending/approvals', [PurchaseRequisitionController::class, 'pendingApprovals'])
        ->name('purchase-requisitions.pending-approvals');
    Route::post('/purchase-requisitions/{purchaseRequisition}/submit-for-approval', [PurchaseRequisitionController::class, 'submitForApproval'])
        ->name('purchase-requisitions.submit-for-approval');
    Route::post('/purchase-requisitions/{purchaseRequisition}/approve', [PurchaseRequisitionController::class, 'approve'])
        ->name('purchase-requisitions.approve');
    Route::post('/purchase-requisitions/{purchaseRequisition}/reject', [PurchaseRequisitionController::class, 'reject'])
        ->name('purchase-requisitions.reject');
    Route::get('/purchase-requisitions/attachments/{attachment}/download', [PurchaseRequisitionController::class, 'downloadAttachment'])
        ->name('purchase-requisitions.download-attachment');
    Route::get('/pr-attachments/{attachment}/view', [PurchaseRequisitionController::class, 'viewAttachment'])
        ->name('pr-attachments.view');
    Route::get('/pr-attachments/{attachment}/download', [PurchaseRequisitionController::class, 'downloadAttachment'])
        ->name('pr-attachments.download');
    
    // Direct Purchase PR routes
    Route::get('/purchase-requisitions/direct/small/create', [PurchaseRequisitionController::class, 'createDirectSmall'])
        ->name('purchase-requisitions.create-direct-small');
    Route::post('/purchase-requisitions/direct/small', [PurchaseRequisitionController::class, 'storeDirectSmall'])
        ->name('purchase-requisitions.store-direct-small');
    Route::get('/purchase-requisitions/direct/medium/create', [PurchaseRequisitionController::class, 'createDirectMedium'])
        ->name('purchase-requisitions.create-direct-medium');
    Route::post('/purchase-requisitions/direct/medium', [PurchaseRequisitionController::class, 'storeDirectMedium'])
        ->name('purchase-requisitions.store-direct-medium');

    // Contract Approval routes
    Route::resource('contract-approvals', ContractApprovalController::class);
    
    // Additional Contract Approval routes
    Route::post('/contract-approvals/{contractApproval}/start-review', [ContractApprovalController::class, 'startReview'])
        ->name('contract-approvals.start-review');
    Route::post('/contract-approvals/{contractApproval}/approve', [ContractApprovalController::class, 'approve'])
        ->name('contract-approvals.approve');
    Route::post('/contract-approvals/{contractApproval}/reject', [ContractApprovalController::class, 'reject'])
        ->name('contract-approvals.reject');
    Route::get('/contract-files/{file}/download', [ContractApprovalController::class, 'downloadFile'])
        ->name('contract-files.download');

    // Purchase Orders Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/purchase-orders/create-from-pr/{purchaseRequisition}', [PurchaseOrderController::class, 'createFromPR'])->name('purchase-orders.create-from-pr');
});
Route::resource('purchase-orders', PurchaseOrderController::class);
    
    // Additional Purchase Order routes
    Route::get('/purchase-orders/pending/approvals', [PurchaseOrderController::class, 'pendingApprovals'])
        ->name('purchase-orders.pending-approvals');
    Route::post('/purchase-orders/{purchaseOrder}/submit-for-approval', [PurchaseOrderController::class, 'submitForApproval'])
        ->name('purchase-orders.submit-for-approval');
    Route::post('/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])
        ->name('purchase-orders.approve');
    Route::post('/purchase-orders/{purchaseOrder}/reject', [PurchaseOrderController::class, 'reject'])
        ->name('purchase-orders.reject');
    Route::post('/purchase-orders/{purchaseOrder}/send-to-vendor', [PurchaseOrderController::class, 'sendToVendor'])
        ->name('purchase-orders.send-to-vendor');
    Route::post('/purchase-orders/{purchaseOrder}/mark-received', [PurchaseOrderController::class, 'markReceived'])
        ->name('purchase-orders.mark-received');
    Route::post('/purchase-orders/{purchaseOrder}/close', [PurchaseOrderController::class, 'close'])
        ->name('purchase-orders.close');
    Route::post('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])
        ->name('purchase-orders.cancel');
    Route::get('/po-files/{file}/download', [PurchaseOrderController::class, 'downloadFile'])
->name('po-files.download');
Route::get('/po-files/{file}/view', [PurchaseOrderController::class, 'viewFile'])
->name('po-files.view');
Route::delete('/po-files/{file}', [PurchaseOrderController::class, 'deleteFile'])
->name('po-files.delete');

    // Vendor routes
    Route::resource('vendors', VendorController::class);
    
    // Additional Vendor routes
    Route::post('/vendors/{vendor}/approve', [VendorController::class, 'approve'])
        ->name('vendors.approve');
    Route::post('/vendors/{vendor}/reject', [VendorController::class, 'reject'])
        ->name('vendors.reject');
    Route::post('/vendors/{vendor}/suspend', [VendorController::class, 'suspend'])
        ->name('vendors.suspend');
    Route::delete('/vendors/{vendor}/remove-document', [VendorController::class, 'removeDocument'])
        ->name('vendors.remove-document');

    // Value Analysis routes
    Route::resource('value-analysis', ValueAnalysisController::class);
    
    // Additional Value Analysis routes
    Route::get('/value-analysis/pr-details/{pr_id}', [ValueAnalysisController::class, 'getPRDetails'])
        ->name('value-analysis.pr-details');
    Route::post('/value-analysis/{valueAnalysis}/start-analysis', [ValueAnalysisController::class, 'startAnalysis'])
        ->name('value-analysis.start-analysis');
    Route::post('/value-analysis/{valueAnalysis}/complete-analysis', [ValueAnalysisController::class, 'completeAnalysis'])
        ->name('value-analysis.complete-analysis');
    Route::post('/value-analysis/{valueAnalysis}/approve', [ValueAnalysisController::class, 'approve'])
        ->name('value-analysis.approve');
    Route::post('/value-analysis/{valueAnalysis}/reject', [ValueAnalysisController::class, 'reject'])
        ->name('value-analysis.reject');
});

// Admin routes
Route::middleware(['auth', CheckAdminRole::class, CompanyMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class);
});

require __DIR__.'/auth.php';
