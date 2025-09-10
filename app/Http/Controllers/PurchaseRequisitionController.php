<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\PurchaseRequisitionAttachment;
use App\Events\PurchaseRequisitionApproved;
use App\Events\PurchaseRequisitionRejected;
use App\Events\PurchaseRequisitionSubmitted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\User; // Added this import for User model
use App\Models\Vendor; // Added this import for Vendor model

class PurchaseRequisitionController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        // Middleware ถูกกำหนดใน routes/web.php แล้ว
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Check if user has permission to view purchase requisitions
        if (!Auth::user()->hasPermission('purchase_requisition.read') && 
            !Auth::user()->roles->contains('name', 'requester') && 
            !Auth::user()->roles->contains('name', 'admin')) {
            abort(403, 'Unauthorized action.');
        }

        $status = $request->input('status');
        $priority = $request->input('priority');
        $category = $request->input('category');
        $department = $request->input('department');
        $query = $request->input('query');
        $work_type = $request->input('work_type');
        $procurement_method = $request->input('procurement_method');

        $purchaseRequisitions = PurchaseRequisition::with(['department', 'requester', 'user', 'company', 'attachments'])
            ->where('company_id', session('company_id'))
            ->when($status, function ($q) use ($status) {
                return $q->where('status', $status);
            })
            ->when($priority, function ($q) use ($priority) {
                return $q->where('priority', $priority);
            })
            ->when($category, function ($q) use ($category) {
                return $q->where('category', $category);
            })
            ->when($department, function ($q) use ($department) {
                return $q->where('department_id', $department);
            })
            ->when($work_type, function ($q) use ($work_type) {
                return $q->where('work_type', $work_type);
            })
            ->when($procurement_method, function ($q) use ($procurement_method) {
                return $q->where('procurement_method', $procurement_method);
            })
            ->when($query, function ($q) use ($query) {
                return $q->where(function ($sq) use ($query) {
                    $sq->where('pr_number', 'like', "%{$query}%")
                        ->orWhere('title', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%");
                });
            });

        // If user is not admin, show only their department's PRs or ones they created
        if (!Auth::user()->isAdmin() && !Auth::user()->hasRole('procurement_officer') && !Auth::user()->hasRole('procurement_manager')) {
            $purchaseRequisitions = $purchaseRequisitions->where(function ($q) {
                $q->where('department_id', Auth::user()->department_id)
                    ->orWhere('requester_id', Auth::user()->id);
            });
        }

        $purchaseRequisitions = $purchaseRequisitions->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Get departments for filter
        $departments = Department::active()->orderBy('name')->get();

        return view('purchase_requisitions.index', [
            'purchaseRequisitions' => $purchaseRequisitions,
            'departments' => $departments,
            'filters' => $request->only(['status', 'priority', 'category', 'department', 'query', 'work_type', 'procurement_method']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Check if user has permission to create purchase requisitions
        if (!Auth::user()->hasPermission('purchase_requisition.create') && 
            !Auth::user()->roles->contains('name', 'requester') && 
            !Auth::user()->roles->contains('name', 'admin')) {
            abort(403, 'Unauthorized action.');
        }

        // Get user's department or all departments if admin
        if (Auth::user()->isAdmin()) {
            $departments = Department::active()->orderBy('name')->get();
        } else {
            $departments = Department::where('id', Auth::user()->department_id)->get();
        }

        // Get users by roles for committee selections
        $procurementCommitteeUsers = User::whereHas('roles', function($query) {
            $query->where('name', 'procurement_committee');
        })->orderBy('name')->get();
        
        $inspectionCommitteeUsers = User::whereHas('roles', function($query) {
            $query->where('name', 'inspection_committee');
        })->orderBy('name')->get();
        
        $approverUsers = User::whereHas('roles', function($query) {
            $query->where('name', 'approver');
        })->orderBy('name')->get();
        
        $otherStakeholderUsers = User::whereHas('roles', function($query) {
            $query->where('name', 'other_stakeholder');
        })->orderBy('name')->get();

        // Get all users for cascading dropdown (department -> requester)
        $users = User::with('department')
            ->whereHas('department')
            ->orderBy('name')
            ->get();

        return view('purchase_requisitions.create', [
            'departments' => $departments,
            'procurementCommitteeUsers' => $procurementCommitteeUsers,
            'inspectionCommitteeUsers' => $inspectionCommitteeUsers,
            'approverUsers' => $approverUsers,
            'otherStakeholderUsers' => $otherStakeholderUsers,
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // เฉพาะ Admin เท่านั้นที่สร้าง PR ได้
        if (!Auth::user()->isAdmin()) {
            abort(403, 'เฉพาะ Admin เท่านั้นที่สามารถสร้างใบ PR ได้');
        }

        // Validate basic PR data
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string',
            'work_type' => 'required|in:buy,hire,rent',
            'procurement_method' => 'nullable|in:agreement_price,invitation_bid,open_bid,special_1,special_2,selection',
            'procurement_budget' => 'nullable|numeric|min:0',
            'delivery_schedule' => 'nullable|string',
            'payment_schedule' => 'nullable|string',
            'procurement_committee_id' => 'nullable|exists:users,id',
            'inspection_committee_id' => 'nullable|exists:users,id',
            'pr_approver_id' => 'nullable|exists:users,id',
            'other_stakeholder_id' => 'nullable|exists:users,id',
            'department_id' => 'required|exists:departments,id',
            'requester_id' => 'required|exists:users,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'total_amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'required|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric|min:0',
        ]);

        // Start a database transaction
        DB::beginTransaction();

        try {
            // Debug: Log the validated data
            \Log::info('Validated data in store method:', $validated);
            \Log::info('All request data:', $request->all());

            // Calculate total amount from items
            $totalAmount = array_reduce($request->items, function ($carry, $item) {
                return $carry + ($item['quantity'] * $item['unit_price']);
            }, 0);

            // Create PR number
            $prNumber = PurchaseRequisition::generatePRNumber();

            // Create the PR
            $purchaseRequisition = new PurchaseRequisition([
                'company_id' => session('company_id'),
                'pr_number' => $prNumber,
                'title' => $validated['title'],
                'category' => $validated['category'],
                'description' => $validated['description'] ?? null,
                'department_id' => $validated['department_id'],
                'requester_id' => $validated['requester_id'], // ผู้ขอจริง
                'created_by' => Auth::id(), // Admin ที่คีย์ข้อมูล
                'request_date' => now(),
                'required_date' => now()->addDays(30),
                'status' => $request->has('save_as_draft') ? 'draft' : 'pending_approval',
                'priority' => $validated['priority'],
                'total_amount' => $totalAmount,
                'currency' => $validated['currency'],
                'notes' => $validated['notes'] ?? null,
                'work_type' => $validated['work_type'],
                'procurement_method' => $validated['procurement_method'] ?? null,
                'procurement_budget' => $validated['procurement_budget'] ?? null,
                'delivery_schedule' => $validated['delivery_schedule'] ?? null,
                'payment_schedule' => $validated['payment_schedule'] ?? null,
                'procurement_committee_id' => $validated['procurement_committee_id'] ?? null,
                'inspection_committee_id' => $validated['inspection_committee_id'] ?? null,
                'pr_approver_id' => $validated['pr_approver_id'] ?? null,
                'other_stakeholder_id' => $validated['other_stakeholder_id'] ?? null,
            ]);

            $purchaseRequisition->save();

            // Save items
            foreach ($request->items as $index => $itemData) {
                $item = new PurchaseRequisitionItem([
                    'purchase_requisition_id' => $purchaseRequisition->id,
                    'line_number' => $index + 1,
                    'item_code' => $itemData['item_code'] ?? null,
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_of_measure' => $itemData['unit'],
                    'estimated_unit_price' => $itemData['unit_price'],
                    'estimated_amount' => $itemData['total_price'],
                    'required_date' => $itemData['delivery_date'] ?? null,
                    'specification' => $itemData['technical_specifications'] ?? null,
                    'remarks' => $itemData['notes'] ?? null,
                    'status' => 'pending',
                ]);

                $purchaseRequisition->items()->save($item);
            }

            // Save attachments if any
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $index => $file) {
                    $fileName = time() . '_' . $index . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs('purchase_requisitions/' . $purchaseRequisition->id, $fileName, 'public');

                    $attachment = new PurchaseRequisitionAttachment([
                        'purchase_requisition_id' => $purchaseRequisition->id,
                        'file_name' => $fileName,
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $filePath,
                        'file_size' => $file->getSize(),
                        'file_type' => $file->getMimeType(),
                        'uploaded_by' => Auth::id(),
                    ]);

                    $purchaseRequisition->attachments()->save($attachment);
                }
            }

            // TODO: Add approval workflow logic here, create approval records based on threshold amount

            // Fire event if PR is submitted for approval (not draft)
            if (!$request->has('save_as_draft')) {
                event(new PurchaseRequisitionSubmitted($purchaseRequisition, Auth::user()));
            }

            DB::commit();

            $message = $request->has('save_as_draft') 
                ? 'ใบขอซื้อถูกบันทึกเป็นแบบร่างเรียบร้อยแล้ว' 
                : 'ใบขอซื้อถูกสร้างและส่งขออนุมัติเรียบร้อยแล้ว';

            return redirect()->route('purchase-requisitions.show', $purchaseRequisition)
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseRequisition $purchaseRequisition)
    {
        // Check if user has permission to view purchase requisitions
        if (!Auth::user()->hasPermission('purchase_requisition.read') && 
            !Auth::user()->roles->contains('name', 'requester') && 
            !Auth::user()->roles->contains('name', 'admin')) {
            abort(403, 'Unauthorized action.');
        }

        // Check if user can view this specific PR
        if (!Auth::user()->roles->contains('name', 'admin') && 
            !Auth::user()->hasRole('procurement_officer') && 
            !Auth::user()->hasRole('procurement_manager') && 
            Auth::user()->department_id != $purchaseRequisition->department_id && 
            Auth::user()->id != $purchaseRequisition->requester_id) {
            abort(403, 'Unauthorized action.');
        }

        // Load relationships
        $purchaseRequisition->load([
            'department', 
            'requester', 
            'createdBy',
            'items',
            'attachments',
            'procurementCommittee',
            'inspectionCommittee', 
            'prApprover',
            'otherStakeholder'
        ]);

        // Check if this is an AJAX request for modal content
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'pr' => $purchaseRequisition,
                    'html' => view('purchase_requisitions.partials.details', [
                        'purchaseRequisition' => $purchaseRequisition
                    ])->render()
                ]
            ]);
        }

        return view('purchase_requisitions.show', [
            'purchaseRequisition' => $purchaseRequisition,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PurchaseRequisition $purchaseRequisition)
    {
        // Check if user has permission to edit purchase requisitions
        if (!Auth::user()->hasPermission('purchase_requisition.update') && 
            !Auth::user()->roles->contains('name', 'requester') && 
            !Auth::user()->roles->contains('name', 'admin')) {
            abort(403, 'Unauthorized action.');
        }

        // Check if user can edit this specific PR
        if (!Auth::user()->roles->contains('name', 'admin') && 
            Auth::user()->id != $purchaseRequisition->requester_id) {
            abort(403, 'Unauthorized action.');
        }

        // Cannot edit if not in draft status
        if ($purchaseRequisition->status !== 'draft' && 
            $purchaseRequisition->status !== 'rejected') {
            return redirect()->route('purchase-requisitions.show', $purchaseRequisition)
                ->with('error', 'ไม่สามารถแก้ไขใบขอซื้อที่ไม่ได้อยู่ในสถานะแบบร่างหรือถูกปฏิเสธ');
        }

        // Get user's department or all departments if admin
        if (Auth::user()->isAdmin()) {
            $departments = Department::active()->orderBy('name')->get();
        } else {
            $departments = Department::where('id', Auth::user()->department_id)->get();
        }

        // Get users by roles for committee selections
        $procurementCommitteeUsers = User::whereHas('roles', function($query) {
            $query->where('name', 'procurement_committee');
        })->orderBy('name')->get();
        
        $inspectionCommitteeUsers = User::whereHas('roles', function($query) {
            $query->where('name', 'inspection_committee');
        })->orderBy('name')->get();
        
        $approverUsers = User::whereHas('roles', function($query) {
            $query->where('name', 'approver');
        })->orderBy('name')->get();
        
        $otherStakeholderUsers = User::whereHas('roles', function($query) {
            $query->where('name', 'other_stakeholder');
        })->orderBy('name')->get();

        // Load relationships
        $purchaseRequisition->load(['items', 'attachments']);

        return view('purchase_requisitions.edit', [
            'purchaseRequisition' => $purchaseRequisition,
            'departments' => $departments,
            'procurementCommitteeUsers' => $procurementCommitteeUsers,
            'inspectionCommitteeUsers' => $inspectionCommitteeUsers,
            'approverUsers' => $approverUsers,
            'otherStakeholderUsers' => $otherStakeholderUsers,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        // Check if user has permission to update purchase requisitions
        if (!Auth::user()->hasPermission('purchase_requisition.update') && 
            !Auth::user()->roles->contains('name', 'requester') && 
            !Auth::user()->roles->contains('name', 'admin')) {
            abort(403, 'Unauthorized action.');
        }

        // Debug information
        \Log::info('Purchase Requisition Update called', [
            'has_save_as_draft' => $request->has('save_as_draft'),
            'save_as_draft_value' => $request->input('save_as_draft'),
            'form_action' => $request->input('form_action'),
            'pr_id' => $purchaseRequisition->id,
            'current_status' => $purchaseRequisition->status
        ]);

        // Check if user can update this specific PR
        if (!Auth::user()->roles->contains('name', 'admin') && 
            Auth::user()->id != $purchaseRequisition->requester_id) {
            abort(403, 'Unauthorized action.');
        }

        // Cannot update if not in draft status
        if ($purchaseRequisition->status !== 'draft' && 
            $purchaseRequisition->status !== 'rejected') {
            return redirect()->route('purchase-requisitions.show', $purchaseRequisition)
                ->with('error', 'ไม่สามารถแก้ไขใบขอซื้อที่ไม่ได้อยู่ในสถานะแบบร่างหรือถูกปฏิเสธ');
        }

        // Validate basic PR data
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|in:premium_products,advertising_services',
            'description' => 'nullable|string',
            'department_id' => 'required|exists:departments,id',
            'required_date' => 'required|date|after_or_equal:today',
            'priority' => 'required|in:low,medium,high,urgent',
            'budget_code' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'currency' => 'required|string|size:3',
            'work_type' => 'required|in:buy,hire,rent',
            'procurement_method' => 'nullable|in:agreement_price,invitation_bid,open_bid,special_1,special_2,selection',
            'procurement_budget' => 'nullable|numeric|min:0',
            'delivery_schedule' => 'nullable|string',
            'payment_schedule' => 'nullable|string',
            'procurement_committee_id' => 'nullable|exists:users,id',
            'inspection_committee_id' => 'nullable|exists:users,id',
            'pr_approver_id' => 'nullable|exists:users,id',
            'other_stakeholder_id' => 'nullable|exists:users,id',
            // Items validation
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:purchase_requisition_items,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0.01',
            'items.*.total_price' => 'required|numeric|min:0.01',
            'items.*.delivery_date' => 'nullable|date|after_or_equal:today',
            'items.*.notes' => 'nullable|string',
            'items.*.technical_specifications' => 'nullable|string',
            'items.*.suggested_vendor' => 'nullable|string|max:255',
            // Attachments validation
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
            'attachment_descriptions.*' => 'nullable|string|max:255',
            // Existing attachments to delete
            'delete_attachments' => 'nullable|array',
            'delete_attachments.*' => 'exists:purchase_requisition_attachments,id',
        ]);

        // Start a database transaction
        DB::beginTransaction();

        try {
            // Calculate total amount from items
            $totalAmount = array_reduce($request->items, function ($carry, $item) {
                return $carry + ($item['quantity'] * $item['unit_price']);
            }, 0);

            // Update the PR
            $purchaseRequisition->title = $validated['title'];
            $purchaseRequisition->description = $validated['description'] ?? null;
            $purchaseRequisition->department_id = $validated['department_id'];
            $purchaseRequisition->required_date = $validated['required_date'];
            $purchaseRequisition->status = $request->has('save_as_draft') ? 'draft' : 'pending_approval';
            $purchaseRequisition->priority = $validated['priority'];
            $purchaseRequisition->total_amount = $totalAmount;
            $purchaseRequisition->currency = $validated['currency'];
            $purchaseRequisition->budget_code = $validated['budget_code'] ?? null;
            $purchaseRequisition->notes = $validated['notes'] ?? null;
            $purchaseRequisition->work_type = $validated['work_type'];
            $purchaseRequisition->procurement_method = $validated['procurement_method'] ?? null;
            $purchaseRequisition->procurement_budget = $validated['procurement_budget'] ?? null;
            $purchaseRequisition->delivery_schedule = $validated['delivery_schedule'] ?? null;
            $purchaseRequisition->payment_schedule = $validated['payment_schedule'] ?? null;
            $purchaseRequisition->procurement_committee_id = $validated['procurement_committee_id'] ?? null;
            $purchaseRequisition->inspection_committee_id = $validated['inspection_committee_id'] ?? null;
            $purchaseRequisition->pr_approver_id = $validated['pr_approver_id'] ?? null;
            $purchaseRequisition->other_stakeholder_id = $validated['other_stakeholder_id'] ?? null;
            $purchaseRequisition->request_date = $purchaseRequisition->request_date ?? now();
            $purchaseRequisition->rejection_reason = null; // Clear rejection reason if resubmitting
            
            // ตรวจสอบว่า created_by มีค่าหรือไม่ ถ้าไม่มีให้กำหนดค่า
            if (empty($purchaseRequisition->created_by)) {
                $purchaseRequisition->created_by = Auth::id();
            }

            $purchaseRequisition->save();

            // Get existing item IDs to identify deleted items
            $existingItemIds = $purchaseRequisition->items->pluck('id')->toArray();
            $updatedItemIds = [];

            // Update or create items
            foreach ($request->items as $index => $itemData) {
                if (isset($itemData['id']) && !empty($itemData['id'])) {
                    // Update existing item
                    $item = PurchaseRequisitionItem::find($itemData['id']);
                    $updatedItemIds[] = $item->id;
                } else {
                    // Create new item
                    $item = new PurchaseRequisitionItem();
                    $item->purchase_requisition_id = $purchaseRequisition->id;
                    $item->line_number = $index + 1;
                }

                $item->item_code = $itemData['item_code'] ?? null;
                $item->description = $itemData['description'];
                $item->quantity = $itemData['quantity'];
                $item->unit_of_measure = $itemData['unit'];
                $item->estimated_unit_price = $itemData['unit_price'];
                $item->estimated_amount = $itemData['total_price'];
                $item->required_date = $itemData['delivery_date'] ?? null;
                $item->specification = $itemData['technical_specifications'] ?? null;
                $item->remarks = $itemData['notes'] ?? null;
                $item->status = 'pending';
                $item->save();
            }

            // Delete items that were removed
            $deletedItemIds = array_diff($existingItemIds, $updatedItemIds);
            if (!empty($deletedItemIds)) {
                PurchaseRequisitionItem::whereIn('id', $deletedItemIds)->delete();
            }

            // TODO: Delete attachments if requested (temporarily disabled)
            // if ($request->has('delete_attachments')) {
            //     $attachmentsToDelete = PurchaseRequisitionAttachment::whereIn('id', $request->delete_attachments)
            //         ->where('purchase_requisition_id', $purchaseRequisition->id)
            //         ->get();

            //     foreach ($attachmentsToDelete as $attachment) {
            //         // Delete file from storage
            //         if (Storage::disk('public')->exists($attachment->file_path)) {
            //             Storage::disk('public')->delete($attachment->file_path);
            //         }
            //         $attachment->delete();
            //     }
            // }

            // TODO: Save new attachments if any (temporarily disabled)
            // if ($request->hasFile('attachments')) {
            //     foreach ($request->file('attachments') as $index => $file) {
            //         $fileName = time() . '_' . $file->getClientOriginalName();
            //         $filePath = $file->storeAs('purchase_requisitions/' . $purchaseRequisition->id, $fileName, 'public');

            //         $attachment = new PurchaseRequisitionAttachment([
            //             'purchase_requisition_id' => $purchaseRequisition->id,
            //             'file_name' => $fileName,
            //             'original_name' => $file->getClientOriginalName(),
            //             'file_path' => $filePath,
            //             'file_size' => $file->getSize(),
            //             'file_type' => $file->getMimeType(),
            //             'description' => $request->input('attachment_descriptions.' . $index, null),
            //             'uploaded_by' => Auth::id(),
            //         ]);

            //         $purchaseRequisition->attachments()->save($attachment);
            //     }
            // }

            // TODO: Update approval workflow logic if status changed from draft/rejected to pending

            // Fire event if PR status changed to pending_approval (not draft)
            if (!$request->has('save_as_draft') && $purchaseRequisition->status === 'pending_approval') {
                event(new PurchaseRequisitionSubmitted($purchaseRequisition, Auth::user()));
            }

            DB::commit();

            $message = $request->has('save_as_draft') 
                ? 'ใบขอซื้อถูกบันทึกเป็นแบบร่างเรียบร้อยแล้ว' 
                : 'ใบขอซื้อถูกแก้ไขและส่งขออนุมัติเรียบร้อยแล้ว';

            return redirect()->route('purchase-requisitions.show', $purchaseRequisition)
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseRequisition $purchaseRequisition)
    {
        $user = Auth::user();
        
        // Check if user has permission to delete purchase requisitions
        // Admin can delete any PR, requester can delete their own draft PR
        $canDelete = $user->hasRole('admin') || 
                    ($user->hasRole('requester') && $user->id == $purchaseRequisition->requester_id);
        
        if (!$canDelete) {
            return redirect()->route('purchase-requisitions.index')
                ->with('error', 'คุณไม่มีสิทธิ์ในการลบใบขอซื้อนี้');
        }

        // Can only delete PR in draft or rejected status
        if (!in_array($purchaseRequisition->status, ['draft', 'rejected'])) {
            return redirect()->route('purchase-requisitions.show', $purchaseRequisition)
                ->with('error', 'ไม่สามารถลบใบขอซื้อที่อยู่ในสถานะ "' . $purchaseRequisition->status . '" ได้ (สามารถลบได้เฉพาะสถานะ "draft" หรือ "rejected" เท่านั้น)');
        }

        try {
            $prNumber = $purchaseRequisition->pr_number;
            $prTitle = $purchaseRequisition->title;
            
            // TODO: Delete attachments from storage (temporarily disabled)
            // foreach ($purchaseRequisition->attachments as $attachment) {
            //     if (Storage::disk('public')->exists($attachment->file_path)) {
            //         Storage::disk('public')->delete($attachment->file_path);
            //     }
            // }

            // Delete the PR record (cascades to items and other related records)
            $purchaseRequisition->delete();

            return redirect()->route('purchase-requisitions.index')
                ->with('success', 'ใบขอซื้อหมายเลข ' . $prNumber . ' (' . $prTitle . ') ถูกลบเรียบร้อยแล้ว');
                
        } catch (\Exception $e) {
            return redirect()->route('purchase-requisitions.show', $purchaseRequisition)
                ->with('error', 'เกิดข้อผิดพลาดในการลบใบขอซื้อ: ' . $e->getMessage());
        }
    }



    /**
     * Download attachment file.
     */
    public function downloadAttachment(PurchaseRequisitionAttachment $attachment)
    {
        // Check if user can access this PR's attachments
        $purchaseRequisition = $attachment->purchaseRequisition;
        
        // Check if user has permission to view purchase requisitions
        if (!Auth::user()->hasPermission('purchase_requisition.read') && 
            !Auth::user()->roles->contains('name', 'requester') && 
            !Auth::user()->roles->contains('name', 'admin')) {
            abort(403, 'Unauthorized action.');
        }

        // If user is not admin, check if they can access this specific PR
        if (!Auth::user()->isAdmin() && 
            !Auth::user()->hasRole('procurement_officer') && 
            !Auth::user()->hasRole('procurement_manager')) {
            
            // Allow access if user is in the same department or is the requester
            if (Auth::user()->department_id != $purchaseRequisition->department_id && 
                Auth::user()->id != $purchaseRequisition->requester_id) {
                abort(403, 'Unauthorized action.');
            }
        }

        // Check if file exists
        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'ไม่พบไฟล์ที่ต้องการ');
        }

        return Storage::disk('public')->download(
            $attachment->file_path, 
            $attachment->original_name
        );
    }

    /**
     * View attachment file in browser
     */
    public function viewAttachment(PurchaseRequisitionAttachment $attachment)
    {
        // Check if user can access this PR's attachments
        $purchaseRequisition = $attachment->purchaseRequisition;
        
        // Check if user has permission to view purchase requisitions
        if (!Auth::user()->hasPermission('purchase_requisition.read') && 
            !Auth::user()->roles->contains('name', 'requester') && 
            !Auth::user()->roles->contains('name', 'admin')) {
            abort(403, 'Unauthorized action.');
        }

        // If user is not admin, check if they can access this specific PR
        if (!Auth::user()->isAdmin() && 
            !Auth::user()->hasRole('procurement_officer') && 
            !Auth::user()->hasRole('procurement_manager')) {
            
            // Allow access if user is in the same department or is the requester
            if (Auth::user()->department_id != $purchaseRequisition->department_id && 
                Auth::user()->id != $purchaseRequisition->requester_id) {
                abort(403, 'Unauthorized action.');
            }
        }

        // Check if file exists
        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'ไม่พบไฟล์ที่ต้องการ');
        }

        $filePath = Storage::disk('public')->path($attachment->file_path);
        $mimeType = $attachment->file_type;

        // Set appropriate headers for browser viewing
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $attachment->original_name . '"',
        ];

        return response()->file($filePath, $headers);
    }

    /**
     * Show my PR requests (for employees)
     */
    public function myRequests(Request $request)
    {
        $user = Auth::user();
        
        $query = PurchaseRequisition::with(['department', 'requester', 'createdBy'])
            ->where('requester_id', $user->id);

        // Add filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('query')) {
            $searchQuery = $request->query;
            $query->where(function ($q) use ($searchQuery) {
                $q->where('pr_number', 'LIKE', "%{$searchQuery}%")
                  ->orWhere('title', 'LIKE', "%{$searchQuery}%")
                  ->orWhere('description', 'LIKE', "%{$searchQuery}%");
            });
        }

        $purchaseRequisitions = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('purchase_requisitions.my_requests', [
            'purchaseRequisitions' => $purchaseRequisitions,
            'filters' => $request->only(['status', 'priority', 'query']),
        ]);
    }

    /**
     * Show pending approvals
     */
    public function pendingApprovals(Request $request)
    {
        $user = Auth::user();
        
        // Check if user can approve
        $canApprove = $user->isAdmin() || 
                     $user->hasRole('procurement_manager') || 
                     $user->hasRole('department_head');
        
        if (!$canApprove) {
            abort(403, 'คุณไม่มีสิทธิ์อนุมัติใบ PR');
        }

        $query = PurchaseRequisition::with(['department', 'requester', 'createdBy', 'items'])
            ->where('status', 'pending_approval');

        // Department heads see only their department's PRs
        if ($user->hasRole('department_head') && !$user->isAdmin() && !$user->hasRole('procurement_manager')) {
            $query->where('department_id', $user->department_id);
        }

        // Add filters
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('department')) {
            $query->where('department_id', $request->department);
        }

        if ($request->filled('query')) {
            $searchQuery = $request->query;
            $query->where(function ($q) use ($searchQuery) {
                $q->where('pr_number', 'LIKE', "%{$searchQuery}%")
                  ->orWhere('title', 'LIKE', "%{$searchQuery}%")
                  ->orWhereHas('requester', function ($q) use ($searchQuery) {
                      $q->where('name', 'LIKE', "%{$searchQuery}%");
                  });
            });
        }

        $purchaseRequisitions = $query->orderBy('priority', 'desc')
            ->orderBy('request_date', 'asc')
            ->paginate(15)
            ->withQueryString();

        // Get departments for filter
        $departments = Department::active()->orderBy('name')->get();

        return view('purchase_requisitions.pending_approvals', [
            'purchaseRequisitions' => $purchaseRequisitions,
            'departments' => $departments,
            'filters' => $request->only(['priority', 'department', 'query']),
        ]);
    }

    /**
     * Submit PR for approval (from draft status)
     */
    public function submitForApproval(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        $user = Auth::user();
        
        // Check if user can submit this PR
        if (!$user->isAdmin() && $user->id !== $purchaseRequisition->requester_id) {
            return response()->json(['success' => false, 'message' => 'คุณไม่มีสิทธิ์ส่งใบ PR นี้'], 403);
        }

        if ($purchaseRequisition->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'เฉพาะใบ PR สถานะแบบร่างเท่านั้นที่สามารถส่งขออนุมัติได้'], 400);
        }

        try {
            DB::beginTransaction();

            // Update status to pending
            $purchaseRequisition->update([
                'status' => 'pending_approval'
            ]);

            // Fire event for notification
            event(new PurchaseRequisitionSubmitted($purchaseRequisition, $user));

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true, 
                    'message' => 'ส่งใบ PR ขออนุมัติเรียบร้อยแล้ว'
                ]);
            }

            return redirect()->back()->with('success', 'ส่งใบ PR ขออนุมัติเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Approve PR with proper workflow and email notification
     */
    public function approve(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        $user = Auth::user();
        
        // Check approval permission
        $canApprove = $user->isAdmin() || 
                     $user->hasRole('procurement_manager') || 
                     ($user->hasRole('department_head') && 
                      $user->department_id === $purchaseRequisition->department_id);
        
        if (!$canApprove) {
            return response()->json(['success' => false, 'message' => 'คุณไม่มีสิทธิ์อนุมัติใบ PR นี้'], 403);
        }

        if ($purchaseRequisition->status !== 'pending_approval') {
            return response()->json(['success' => false, 'message' => 'ใบ PR นี้ไม่สามารถอนุมัติได้'], 400);
        }

        $validated = $request->validate([
            'approval_comments' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Update PR status
            $purchaseRequisition->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'approval_comments' => $validated['approval_comments'],
            ]);

            // Dispatch email event
            event(new PurchaseRequisitionApproved($purchaseRequisition, $user));

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true, 
                    'message' => 'ใบ PR ได้รับการอนุมัติเรียบร้อยแล้ว'
                ]);
            }

            return redirect()->back()->with('success', 'ใบ PR ได้รับการอนุมัติเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Reject PR with proper workflow and email notification
     */
    public function reject(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        $user = Auth::user();
        
        // Check rejection permission
        $canReject = $user->isAdmin() || 
                    $user->hasRole('procurement_manager') || 
                    ($user->hasRole('department_head') && 
                     $user->department_id === $purchaseRequisition->department_id);
        
        if (!$canReject) {
            return response()->json(['success' => false, 'message' => 'คุณไม่มีสิทธิ์ปฏิเสธใบ PR นี้'], 403);
        }

        if ($purchaseRequisition->status !== 'pending_approval') {
            return response()->json(['success' => false, 'message' => 'ใบ PR นี้ไม่สามารถปฏิเสธได้'], 400);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Update PR status
            $purchaseRequisition->update([
                'status' => 'rejected',
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
            ]);

            // Dispatch email event
            event(new PurchaseRequisitionRejected($purchaseRequisition, $user, $validated['rejection_reason']));

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true, 
                    'message' => 'ใบ PR ถูกปฏิเสธเรียบร้อยแล้ว'
                ]);
            }

            return redirect()->back()->with('success', 'ใบ PR ถูกปฏิเสธเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Show form for creating direct purchase PR ≤10,000
     */
    public function createDirectSmall()
    {
        $prNumber = PurchaseRequisition::generatePRNumber();
        $departments = Department::all();
        $users = User::all();
        $vendors = Vendor::where('status', 'approved')->get();
        
        // Users for dropdowns filtered by roles
        $procurementCommitteeUsers = User::whereHas('roles', function($query) {
            $query->where('name', 'procurement_committee');
        })->get();
        
        $approverUsers = User::whereHas('roles', function($query) {
            $query->where('name', 'approver');
        })->get();
        
        $otherStakeholderUsers = User::whereHas('roles', function($query) {
            $query->where('name', 'other_stakeholder');
        })->get();
        
        // If no vendors found, show warning
        if ($vendors->isEmpty()) {
            session()->flash('warning', 'ไม่พบข้อมูลผู้ขายในบริษัทนี้ กรุณาเพิ่มข้อมูลผู้ขายก่อนสร้าง PR จัดซื้อตรง');
        }
        
        return view('purchase_requisitions.create_direct_small', compact(
            'prNumber',
            'departments',
            'users',
            'vendors',
            'procurementCommitteeUsers',
            'approverUsers', 
            'otherStakeholderUsers'
        ));
    }

    /**
     * Store direct purchase PR ≤10,000
     */
    public function storeDirectSmall(Request $request)
    {
        // Validate request
        $validated = $request->validate([
            // Standard PR fields
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'department_id' => 'required|exists:departments,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'required_date' => 'required|date|after_or_equal:today',
            'purpose' => 'required|string',
            
            // New required fields
            'category' => 'required|string',
            'work_type' => 'required|string',
            'procurement_method' => 'nullable|string',
            'procurement_budget' => 'nullable|numeric|min:0',
            'procurement_committee_id' => 'nullable|exists:users,id',
            'pr_approver_id' => 'nullable|exists:users,id',
            'other_stakeholder_id' => 'nullable|exists:users,id',
            
            // Direct purchase specific fields
            'approval_request_date' => 'required|date',
            'clause_number' => 'required|integer|min:1|max:5',
            'prepared_by_id' => 'required|exists:users,id',
            'io_number' => 'required|string|max:50',
            'cost_center' => 'required|string|max:50',
            'supplier_vendor_id' => 'required|exists:vendors,id',
            'reference_document' => 'nullable|string|max:255',
            
            // Items
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit' => 'required|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
        ]);

        // Calculate total amount
        $totalAmount = collect($request->items)->sum('total');
        
        // Validate amount limit for direct_small (≤10,000)
        if ($totalAmount > 10000) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['total_amount' => 'ยอดรวมต้องไม่เกิน 10,000 บาท (ยอดปัจจุบัน: ' . number_format($totalAmount, 2) . ' บาท)']);
        }

        DB::beginTransaction();
        try {
            // Create PR with direct_small type
            $pr = PurchaseRequisition::create([
                'company_id' => session('company_id'),
                'pr_number' => PurchaseRequisition::generatePRNumber(),
                'pr_type' => 'direct_small',
                'requires_po' => false,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'department_id' => $validated['department_id'],
                'requester_id' => auth()->id(),
                'created_by' => auth()->id(),
                'request_date' => now(),
                'required_date' => $validated['required_date'],
                'priority' => $validated['priority'],
                'purpose' => $validated['purpose'],
                'total_amount' => $totalAmount,
                'status' => 'draft',
                
                // New fields from regular PR
                'category' => $validated['category'],
                'work_type' => $validated['work_type'],
                'procurement_method' => $validated['procurement_method'],
                'procurement_budget' => $validated['procurement_budget'],
                'procurement_committee_id' => $validated['procurement_committee_id'],
                'pr_approver_id' => $validated['pr_approver_id'],
                'other_stakeholder_id' => $validated['other_stakeholder_id'],
                
                // Direct purchase specific fields
                'approval_request_date' => $validated['approval_request_date'],
                'clause_number' => $validated['clause_number'],
                'prepared_by_id' => $validated['prepared_by_id'],
                'io_number' => $validated['io_number'],
                'cost_center' => $validated['cost_center'],
                'supplier_vendor_id' => $validated['supplier_vendor_id'],
                'reference_document' => $validated['reference_document'],
            ]);

            // Create PR items
            foreach ($request->items as $index => $item) {
                $pr->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_of_measure' => $item['unit'],
                    'estimated_unit_price' => $item['unit_price'],
                    'estimated_amount' => $item['total'],
                    'line_number' => $index + 1,
                ]);
            }

            DB::commit();

            return redirect()->route('purchase-requisitions.show', $pr)
                ->with('success', 'ใบขอซื้อตรง (≤10,000 บาท) ถูกสร้างเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
    }

    /**
     * Show form for creating direct purchase PR ≤100,000
     */
    public function createDirectMedium()
    {
        $prNumber = PurchaseRequisition::generatePRNumber();
        $departments = Department::all();
        $users = User::all();
        $vendors = Vendor::where('status', 'approved')->get();
        
        // Users for dropdowns filtered by roles
        $procurementCommitteeUsers = User::whereHas('roles', function($query) {
            $query->where('name', 'procurement_committee');
        })->get();
        
        $approverUsers = User::whereHas('roles', function($query) {
            $query->where('name', 'approver');
        })->get();
        
        $otherStakeholderUsers = User::whereHas('roles', function($query) {
            $query->where('name', 'other_stakeholder');
        })->get();
        
        // If no vendors found, show warning
        if ($vendors->isEmpty()) {
            session()->flash('warning', 'ไม่พบข้อมูลผู้ขายในบริษัทนี้ กรุณาเพิ่มข้อมูลผู้ขายก่อนสร้าง PR จัดซื้อตรง');
        }
        
        return view('purchase_requisitions.create_direct_medium', compact(
            'prNumber',
            'departments',
            'users',
            'vendors',
            'procurementCommitteeUsers',
            'approverUsers', 
            'otherStakeholderUsers'
        ));
    }

    /**
     * Store direct purchase PR ≤100,000
     */
    public function storeDirectMedium(Request $request)
    {
        // Similar to storeDirectSmall but with 100,000 limit
        $validated = $request->validate([
            // Standard PR fields
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'department_id' => 'required|exists:departments,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'required_date' => 'required|date|after_or_equal:today',
            'purpose' => 'required|string',
            
            // New required fields
            'category' => 'required|string',
            'work_type' => 'required|string',
            'procurement_method' => 'nullable|string',
            'procurement_budget' => 'nullable|numeric|min:0',
            'procurement_committee_id' => 'nullable|exists:users,id',
            'pr_approver_id' => 'nullable|exists:users,id',
            'other_stakeholder_id' => 'nullable|exists:users,id',
            
            // Direct purchase specific fields
            'approval_request_date' => 'required|date',
            'clause_number' => 'required|integer|min:1|max:5',
            'prepared_by_id' => 'required|exists:users,id',
            'io_number' => 'required|string|max:50',
            'cost_center' => 'required|string|max:50',
            'supplier_vendor_id' => 'required|exists:vendors,id',
            'reference_document' => 'nullable|string|max:255',
            
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit' => 'required|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
        ]);

        $totalAmount = collect($request->items)->sum('total');
        
        // Validate amount limit for direct_medium (≤100,000)
        if ($totalAmount > 100000) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['total_amount' => 'ยอดรวมต้องไม่เกิน 100,000 บาท (ยอดปัจจุบัน: ' . number_format($totalAmount, 2) . ' บาท)']);
        }

        DB::beginTransaction();
        try {
            $pr = PurchaseRequisition::create([
                'company_id' => session('company_id'),
                'pr_number' => PurchaseRequisition::generatePRNumber(),
                'pr_type' => 'direct_medium',
                'requires_po' => false,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'department_id' => $validated['department_id'],
                'requester_id' => auth()->id(),
                'created_by' => auth()->id(),
                'request_date' => now(),
                'required_date' => $validated['required_date'],
                'priority' => $validated['priority'],
                'purpose' => $validated['purpose'],
                'total_amount' => $totalAmount,
                'status' => 'draft',
                
                // New fields from regular PR
                'category' => $validated['category'],
                'work_type' => $validated['work_type'],
                'procurement_method' => $validated['procurement_method'],
                'procurement_budget' => $validated['procurement_budget'],
                'procurement_committee_id' => $validated['procurement_committee_id'],
                'pr_approver_id' => $validated['pr_approver_id'],
                'other_stakeholder_id' => $validated['other_stakeholder_id'],
                
                // Direct purchase specific fields
                'approval_request_date' => $validated['approval_request_date'],
                'clause_number' => $validated['clause_number'],
                'prepared_by_id' => $validated['prepared_by_id'],
                'io_number' => $validated['io_number'],
                'cost_center' => $validated['cost_center'],
                'supplier_vendor_id' => $validated['supplier_vendor_id'],
                'reference_document' => $validated['reference_document'],
            ]);

            // Create PR items
            foreach ($request->items as $index => $item) {
                $pr->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_of_measure' => $item['unit'],
                    'estimated_unit_price' => $item['unit_price'],
                    'estimated_amount' => $item['total'],
                    'line_number' => $index + 1,
                ]);
            }

            DB::commit();

            return redirect()->route('purchase-requisitions.show', $pr)
                ->with('success', 'ใบขอซื้อตรง (≤100,000 บาท) ถูกสร้างเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
    }
}
