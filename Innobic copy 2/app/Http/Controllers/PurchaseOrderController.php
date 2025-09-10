<?php

namespace App\Http\Controllers;

use App\Events\PurchaseOrderApproved;
use App\Events\PurchaseOrderRejected;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderFile;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $status = $request->input('status');
        $priority = $request->input('priority');
        $department = $request->input('department');
        $query = $request->input('query');

        $purchaseOrders = PurchaseOrder::with(['department', 'creator', 'approver'])
            ->when($status, function ($q) use ($status) {
                return $q->where('status', $status);
            })
            ->when($priority, function ($q) use ($priority) {
                return $q->where('priority', $priority);
            })
            ->when($department, function ($q) use ($department) {
                return $q->where('department_id', $department);
            })
            ->when($query, function ($q) use ($query) {
                return $q->where(function ($sq) use ($query) {
                    $sq->where('po_number', 'like', "%{$query}%")
                        ->orWhere('po_title', 'like', "%{$query}%")
                        ->orWhere('vendor_name', 'like', "%{$query}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $departments = Department::active()->orderBy('name')->get();

        return view('purchase_orders.index', [
            'purchaseOrders' => $purchaseOrders,
            'departments' => $departments,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $departments = Department::active()->orderBy('name')->get();
        $vendors = \App\Models\Vendor::where('company_id', session('company_id'))
            ->where('status', 'approved')
            ->orderBy('company_name')
            ->get();
        $nextPoNumber = PurchaseOrder::generatePoNumber();
        
        return view('purchase_orders.create', [
            'departments' => $departments,
            'vendors' => $vendors,
            'nextPoNumber' => $nextPoNumber,
        ]);
    }

    /**
     * Show the form for creating PO from PR.
     */
    public function createFromPR(\App\Models\PurchaseRequisition $purchaseRequisition)
    {
        if ($purchaseRequisition->status !== 'approved') {
            return redirect()->back()->with('error', 'เฉพาะ PR ที่ได้รับอนุมัติแล้วเท่านั้นที่สามารถสร้าง PO ได้');
        }

        $departments = Department::active()->orderBy('name')->get();
        $vendors = \App\Models\Vendor::where('company_id', session('company_id'))
            ->where('status', 'approved')
            ->orderBy('company_name')
            ->get();
        $nextPoNumber = PurchaseOrder::generatePoNumber();
        
        return view('purchase_orders.create', [
            'departments' => $departments,
            'vendors' => $vendors,
            'nextPoNumber' => $nextPoNumber,
            'purchaseRequisition' => $purchaseRequisition,
        ]);
    }

    /**
     * Get the current user ID for the selected company database
     */
    private function getCurrentUserId()
    {
        $currentUser = Auth::user();
        $currentConnection = session('company_connection');
        
        // If using default connection, return current user ID
        if (!$currentConnection || $currentConnection === config('database.default')) {
            return $currentUser->id;
        }
        
        // For company databases, find or create user with same email
        $companyUser = DB::connection($currentConnection)
            ->table('users')
            ->where('email', $currentUser->email)
            ->first();
            
        if ($companyUser) {
            return $companyUser->id;
        }
        
        // If user doesn't exist in company database, create them
        $newUserId = DB::connection($currentConnection)->table('users')->insertGetId([
            'name' => $currentUser->name,
            'email' => $currentUser->email,
            'password' => $currentUser->password,
            'department_id' => $currentUser->department_id,
            'email_verified_at' => $currentUser->email_verified_at,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Also copy user roles if they exist
        if ($currentUser->roles) {
            foreach ($currentUser->roles as $role) {
                // Check if role exists in company database
                $companyRole = DB::connection($currentConnection)
                    ->table('roles')
                    ->where('name', $role->name)
                    ->first();
                    
                if ($companyRole) {
                    DB::connection($currentConnection)->table('role_user')->insert([
                        'user_id' => $newUserId,
                        'role_id' => $companyRole->id,
                        'department_id' => $currentUser->department_id,
                        'is_active' => true,
                        'assigned_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
        
        return $newUserId;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Check if database has new fields for dynamic validation
        $hasNewFields = Schema::hasColumn('purchase_orders', 'vendor_id') && 
                       Schema::hasColumn('purchase_orders', 'sap_po_number') && 
                       Schema::hasColumn('purchase_orders', 'work_type');

        // Basic validation rules
        $rules = [
            'purchase_requisition_id' => 'nullable|exists:purchase_requisitions,id',
            'po_title' => 'required|string|max:255',
            'vendor_name' => 'nullable|string|max:255',
            'total_amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'priority' => 'required|in:low,medium,high,urgent',
            'notes' => 'nullable|string',
            // Files validation
            'po_files' => 'required|array|min:1',
            'po_files.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
            'file_descriptions.*' => 'nullable|string|max:255',
            'file_categories.*' => 'required|in:po_document,quotation,specification,attachment,other',
        ];

        // Add conditional validation for new fields
        if ($hasNewFields) {
            // Always make vendor_id nullable to avoid foreign key issues
            // We'll validate vendor existence in the logic instead
            $rules = array_merge($rules, [
                'sap_po_number' => 'nullable|string|max:255',
                'work_type' => 'required|in:buy,hire,rent',
                'procurement_method' => 'nullable|in:agreement_price,invitation_bid,open_bid,special_1,special_2,selection',
                'vendor_id' => 'nullable', // Always nullable
                'contact_name' => 'required|string|max:255',
                'contact_email' => 'required|email|max:255',
                'stamp_duty' => 'nullable|numeric|min:0',
                'delivery_schedule' => 'nullable|string',
                'payment_schedule' => 'nullable|string',
                'payment_terms' => 'nullable|string',
                'operation_duration' => 'nullable|string',
            ]);
        } else {
            // Fallback validation for older schema
            $rules = array_merge($rules, [
                'contact_email' => 'required|email|max:255',
            ]);
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();

        try {
            // Generate PO number
            $poNumber = PurchaseOrder::generatePoNumber();
            
            // Get the correct user ID for current company database
            $currentUserId = $this->getCurrentUserId();

            // Check if database has new columns by actually checking schema
            $hasNewFields = Schema::hasColumn('purchase_orders', 'vendor_id') && 
                           Schema::hasColumn('purchase_orders', 'sap_po_number') && 
                           Schema::hasColumn('purchase_orders', 'work_type');

            // Get PR data if creating from PR
            $purchaseRequisition = null;
            if ($validated['purchase_requisition_id']) {
                $purchaseRequisition = \App\Models\PurchaseRequisition::find($validated['purchase_requisition_id']);
            }

            // Create purchase order with basic data first
            $poData = [
                'company_id' => session('company_id', 1),
                'po_number' => $poNumber,
                'po_title' => $validated['po_title'],
                'vendor_name' => $validated['vendor_name'],
                'total_amount' => $validated['total_amount'],
                'currency' => $validated['currency'],
                'order_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'],
                'priority' => $validated['priority'],
                'notes' => $validated['notes'],
                'created_by' => $currentUserId,
                'status' => 'draft',
            ];

            // Add pr_id if column exists
            if (Schema::hasColumn('purchase_orders', 'pr_id')) {
                $poData['pr_id'] = $validated['purchase_requisition_id'];
            }

            // Add new fields only if database supports them
            if ($hasNewFields) {
                // Check if vendors actually exist in current database connection
                $actualVendorExists = false;
                if (!empty($validated['vendor_id'])) {
                    // Use the current company's database connection
                    $currentConnection = session('company_connection', 'mysql');
                    $actualVendorExists = DB::connection($currentConnection)
                        ->table('vendors')
                        ->where('id', $validated['vendor_id'])
                        ->where('company_id', session('company_id', 1))
                        ->exists();
                }
                
                $poData['vendor_id'] = $actualVendorExists ? $validated['vendor_id'] : null;
                $poData['contact_name'] = $validated['contact_name'] ?? null;
                $poData['contact_email'] = $validated['contact_email'];
                $poData['sap_po_number'] = $validated['sap_po_number'] ?? null;
                $poData['work_type'] = $validated['work_type'];
                $poData['procurement_method'] = $validated['procurement_method'] ?? null;
                $poData['stamp_duty'] = $validated['stamp_duty'] ?? null;
                $poData['delivery_schedule'] = $validated['delivery_schedule'] ?? null;
                $poData['payment_schedule'] = $validated['payment_schedule'] ?? null;
                $poData['payment_terms'] = $validated['payment_terms'] ?? null;
                $poData['operation_duration'] = $validated['operation_duration'] ?? null;
            } else {
                // Fallback for older schema
                $poData['vendor_contact'] = $validated['contact_email'];
                $poData['description'] = $validated['notes'] ?? 'PO created from PR';
            }

            // Add department_id (should exist in all schemas)
            if ($purchaseRequisition) {
                $poData['department_id'] = $purchaseRequisition->department_id;
                
                // Add inspection_committee_id only if column exists
                if (Schema::hasColumn('purchase_orders', 'inspection_committee_id')) {
                    $poData['inspection_committee_id'] = $purchaseRequisition->inspection_committee_id;
                }
            } else {
                $poData['department_id'] = Auth::user()->department_id ?? 1;
            }

            $purchaseOrder = PurchaseOrder::create($poData);

            // Upload files
            if ($request->hasFile('po_files')) {
                foreach ($request->file('po_files') as $index => $file) {
                    $fileName = time() . '_' . $index . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs('purchase_orders/' . $purchaseOrder->id, $fileName, 'public');

                    PurchaseOrderFile::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'file_name' => $fileName,
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $filePath,
                        'file_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'file_category' => $request->input('file_categories.' . $index, 'po_document'),
                        'description' => $request->input('file_descriptions.' . $index),
                        'uploaded_by' => $currentUserId,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', 'ใบ PO ได้ถูกสร้างเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['department', 'creator', 'approver', 'files.uploader']);
        
        return view('purchase_orders.show', [
            'po' => $purchaseOrder,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PurchaseOrder $purchaseOrder)
    {
        // Only allow editing if status allows
        if (!$purchaseOrder->canEdit()) {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'ไม่สามารถแก้ไข PO ที่อยู่ในสถานะนี้ได้');
        }

        $departments = Department::active()->orderBy('name')->get();
        $vendors = \App\Models\Vendor::where('company_id', session('company_id'))
            ->where('status', 'approved')
            ->orderBy('company_name')
            ->get();
        $purchaseOrder->load(['files']);
        
        return view('purchase_orders.edit', [
            'po' => $purchaseOrder,
            'departments' => $departments,
            'vendors' => $vendors,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        // Only allow updating if status allows
        if (!$purchaseOrder->canEdit()) {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'ไม่สามารถแก้ไข PO ที่อยู่ในสถานะนี้ได้');
        }

        // Check if database has new fields for dynamic validation
        $hasNewFields = Schema::hasColumn('purchase_orders', 'vendor_id') && 
                       Schema::hasColumn('purchase_orders', 'sap_po_number');

        // Basic validation rules for update
        $rules = [
            'po_title' => 'required|string|max:255',
            'vendor_name' => 'nullable|string|max:255',
            'total_amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'priority' => 'required|in:low,medium,high,urgent',
            'notes' => 'nullable|string',
            // Files validation (optional for update)
            'po_files.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'file_descriptions.*' => 'nullable|string|max:255',
            'file_categories.*' => 'nullable|in:po_document,quotation,specification,attachment,other',
        ];

        // Add conditional validation for new fields
        if ($hasNewFields) {
            $rules = array_merge($rules, [
                'vendor_id' => 'nullable', // Always nullable to avoid foreign key issues
                'sap_po_number' => 'nullable|string|max:255',
                'contact_name' => 'required|string|max:255',
                'contact_email' => 'required|email|max:255',
            ]);
        } else {
            // Fallback validation for older schema
            $rules = array_merge($rules, [
                'contact_email' => 'required|email|max:255',
            ]);
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();

        try {
            // Prepare update data with basic fields only
            $updateData = [
                'po_title' => $validated['po_title'],
                'total_amount' => $validated['total_amount'],
                'currency' => $validated['currency'],
                'order_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'],
                'notes' => $validated['notes'],
            ];

            // Add new fields only if they exist in database schema
            if (Schema::hasColumn('purchase_orders', 'vendor_id')) {
                // Validate vendor exists before assigning
                $actualVendorExists = false;
                if (!empty($validated['vendor_id'])) {
                    // Use the current company's database connection
                    $currentConnection = session('company_connection', 'mysql');
                    $actualVendorExists = DB::connection($currentConnection)
                        ->table('vendors')
                        ->where('id', $validated['vendor_id'])
                        ->where('company_id', session('company_id', 1))
                        ->exists();
                }
                $updateData['vendor_id'] = $actualVendorExists ? $validated['vendor_id'] : null;
            }
            if (Schema::hasColumn('purchase_orders', 'sap_po_number')) {
                $updateData['sap_po_number'] = $validated['sap_po_number'] ?? null;
            }
            if (Schema::hasColumn('purchase_orders', 'contact_name')) {
                $updateData['contact_name'] = $validated['contact_name'] ?? null;
            }
            if (Schema::hasColumn('purchase_orders', 'contact_email')) {
                $updateData['contact_email'] = $validated['contact_email'];
            }
            if (isset($validated['vendor_name'])) {
                $updateData['vendor_name'] = $validated['vendor_name']; // vendor_name should exist in all schemas
            }

            // Update purchase order
            $purchaseOrder->update($updateData);

            // Upload new files if provided
            if ($request->hasFile('po_files')) {
                foreach ($request->file('po_files') as $index => $file) {
                    if ($file) {
                        $fileName = time() . '_' . $index . '_' . $file->getClientOriginalName();
                        $filePath = $file->storeAs('purchase_orders/' . $purchaseOrder->id, $fileName, 'public');

                        PurchaseOrderFile::create([
                            'purchase_order_id' => $purchaseOrder->id,
                            'file_name' => $fileName,
                            'original_name' => $file->getClientOriginalName(),
                            'file_path' => $filePath,
                            'file_type' => $file->getMimeType(),
                            'file_size' => $file->getSize(),
                            'file_category' => $request->input('file_categories.' . $index, 'po_document'),
                            'description' => $request->input('file_descriptions.' . $index),
                            'uploaded_by' => $this->getCurrentUserId(),
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', 'ใบ PO ได้ถูกแก้ไขเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        // Only allow deletion if status allows
        if (!$purchaseOrder->canEdit()) {
            return redirect()->route('purchase-orders.index')
                ->with('error', 'ไม่สามารถลบ PO ที่อยู่ในสถานะนี้ได้');
        }

        DB::beginTransaction();

        try {
            // Delete all files
            foreach ($purchaseOrder->files as $file) {
                $file->deleteFile();
            }

            // Delete directory if empty
            $directory = 'purchase_orders/' . $purchaseOrder->id;
            if (Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->deleteDirectory($directory);
            }

            $purchaseOrder->delete();

            DB::commit();

            return redirect()->route('purchase-orders.index')
                ->with('success', 'ใบ PO ได้ถูกลบเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Show pending approvals page
     */
    public function pendingApprovals(Request $request)
    {
        $user = Auth::user();
        
        // Check if user has permission to approve POs
        if (!$user->isAdmin() && !$user->hasRole('procurement_manager') && !$user->hasRole('department_head')) {
            abort(403, 'คุณไม่มีสิทธิ์ในการเข้าถึงหน้านี้');
        }
        
        $query = PurchaseOrder::with(['department', 'creator'])
            ->where('status', 'pending_approval');
        
        // Filter based on user role
        if (!$user->isAdmin() && !$user->hasRole('procurement_manager')) {
            // Department heads can only see POs from their department
            if ($user->hasRole('department_head')) {
                $query->where('department_id', $user->department_id);
            }
        }
        
        // Apply additional filters
        if ($request->has('department') && $request->department) {
            $query->where('department_id', $request->department);
        }
        
        if ($request->has('priority') && $request->priority) {
            $query->where('priority', $request->priority);
        }
        
        $pendingPOs = $query->orderBy('created_at', 'asc')->paginate(15);
        $departments = Department::active()->orderBy('name')->get();
        
        return view('purchase_orders.pending_approvals', compact('pendingPOs', 'departments'));
    }

    /**
     * Submit PO for approval
     */
    public function submitForApproval(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->submitForApproval()) {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', 'ใบ PO ได้ถูกส่งเพื่อขออนุมัติเรียบร้อยแล้ว');
        }

        return back()->with('error', 'ไม่สามารถส่งใบ PO เพื่อขออนุมัติได้');
    }

    /**
     * Approve PO
     */
    public function approve(Request $request, PurchaseOrder $purchaseOrder)
    {
        // Check permission
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->hasRole('procurement_manager') && 
            !($user->hasRole('department_head') && $user->department_id == $purchaseOrder->department_id)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'คุณไม่มีสิทธิ์ในการอนุมัติ PO นี้'], 403);
            }
            abort(403, 'คุณไม่มีสิทธิ์ในการอนุมัติ PO นี้');
        }

        if ($purchaseOrder->status !== 'pending_approval') {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'ใบ PO นี้ไม่สามารถอนุมัติได้'], 400);
            }
            return back()->with('error', 'ใบ PO นี้ไม่สามารถอนุมัติได้');
        }

        try {
            if ($purchaseOrder->approve($this->getCurrentUserId())) {
                // Reload model to get updated data
                $purchaseOrder->refresh();
                
                // Dispatch event for email notification with connection info
                $connectionName = session('company_connection') ?? 'mysql';
                event(new PurchaseOrderApproved($purchaseOrder, $user, $connectionName));
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'ใบ PO ได้ถูกอนุมัติเรียบร้อยแล้ว'
                    ]);
                }
                
                $redirectRoute = $request->input('redirect_to') === 'pending' 
                    ? 'purchase-orders.pending-approvals' 
                    : 'purchase-orders.show';
                
                return redirect()->route($redirectRoute, $purchaseOrder)
                    ->with('success', 'ใบ PO ได้ถูกอนุมัติเรียบร้อยแล้ว');
            }

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'ไม่สามารถอนุมัติใบ PO ได้'], 500);
            }
            return back()->with('error', 'ไม่สามารถอนุมัติใบ PO ได้');
            
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Reject PO
     */
    public function reject(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        // Check permission
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->hasRole('procurement_manager') && 
            !($user->hasRole('department_head') && $user->department_id == $purchaseOrder->department_id)) {
            abort(403, 'คุณไม่มีสิทธิ์ในการปฏิเสธ PO นี้');
        }

        if ($purchaseOrder->reject($this->getCurrentUserId(), $request->rejection_reason)) {
            // Dispatch event for email notification
            event(new PurchaseOrderRejected($purchaseOrder, $user, $request->rejection_reason));
            
            $redirectRoute = $request->input('redirect_to') === 'pending' 
                ? 'purchase-orders.pending-approvals' 
                : 'purchase-orders.show';
            
            return redirect()->route($redirectRoute, $purchaseOrder)
                ->with('success', 'ใบ PO ได้ถูกปฏิเสธเรียบร้อยแล้ว');
        }

        return back()->with('error', 'ไม่สามารถปฏิเสธใบ PO ได้');
    }

    /**
     * Send PO to vendor
     */
    public function sendToVendor(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->sendToVendor()) {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', 'ใบ PO ได้ถูกส่งให้ผู้ขายเรียบร้อยแล้ว');
        }

        return back()->with('error', 'ไม่สามารถส่งใบ PO ให้ผู้ขายได้');
    }

    /**
     * Mark PO as received
     */
    public function markReceived(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'is_fully_received' => 'required|boolean',
        ]);

        if ($purchaseOrder->markReceived($validated['is_fully_received'])) {
            $message = $validated['is_fully_received'] 
                ? 'ใบ PO ได้ถูกบันทึกการรับครบแล้ว' 
                : 'ใบ PO ได้ถูกบันทึกการรับบางส่วน';
                
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', $message);
        }

        return back()->with('error', 'ไม่สามารถบันทึกการรับของได้');
    }

    /**
     * Close PO
     */
    public function close(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->close()) {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', 'ใบ PO ได้ถูกปิดงานเรียบร้อยแล้ว');
        }

        return back()->with('error', 'ไม่สามารถปิดงาน PO ได้');
    }

    /**
     * Cancel PO
     */
    public function cancel(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:1000',
        ]);

        if ($purchaseOrder->cancel($validated['cancellation_reason'])) {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', 'ใบ PO ได้ถูกยกเลิกเรียบร้อยแล้ว');
        }

        return back()->with('error', 'ไม่สามารถยกเลิก PO ได้');
    }

    /**
     * Download file
     */
    public function downloadFile(PurchaseOrderFile $file)
    {
        if (!Storage::disk('public')->exists($file->file_path)) {
            return back()->with('error', 'ไม่พบไฟล์ที่ต้องการดาวน์โหลด');
        }

        return Storage::disk('public')->download($file->file_path, $file->original_name);
    }

    /**
     * View file in browser
     */
    public function viewFile(PurchaseOrderFile $file)
    {
        if (!Storage::disk('public')->exists($file->file_path)) {
            abort(404, 'ไม่พบไฟล์ที่ต้องการ');
        }

        $filePath = Storage::disk('public')->path($file->file_path);
        $mimeType = $file->file_type;

        // Set appropriate headers for browser viewing
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $file->original_name . '"',
        ];

        return response()->file($filePath, $headers);
    }

    /**
     * Delete file
     */
    public function deleteFile(PurchaseOrderFile $file)
    {
        try {
            $file->deleteFile();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
