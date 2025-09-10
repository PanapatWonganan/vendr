<?php

namespace App\Http\Controllers;

use App\Models\ContractApproval;
use App\Models\ContractFile;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ContractApprovalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $status = $request->input('status');
        $contractType = $request->input('contract_type');
        $department = $request->input('department');
        $priority = $request->input('priority');
        $query = $request->input('query');

        $contracts = ContractApproval::with(['department', 'uploader', 'reviewer'])
            ->when($status, function ($q) use ($status) {
                return $q->where('status', $status);
            })
            ->when($contractType, function ($q) use ($contractType) {
                return $q->where('contract_type', $contractType);
            })
            ->when($department, function ($q) use ($department) {
                return $q->where('department_id', $department);
            })
            ->when($priority, function ($q) use ($priority) {
                return $q->where('priority', $priority);
            })
            ->when($query, function ($q) use ($query) {
                return $q->where(function ($sq) use ($query) {
                    $sq->where('contract_number', 'like', "%{$query}%")
                        ->orWhere('contract_title', 'like', "%{$query}%")
                        ->orWhere('vendor_name', 'like', "%{$query}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $departments = Department::active()->orderBy('name')->get();

        return view('contract_approvals.index', [
            'contracts' => $contracts,
            'departments' => $departments,
            'filters' => $request->only(['status', 'contract_type', 'department', 'priority', 'query']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $departments = Department::active()->orderBy('name')->get();
        $nextContractNumber = ContractApproval::generateContractNumber();
        
        return view('contract_approvals.create', [
            'departments' => $departments,
            'nextContractNumber' => $nextContractNumber,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'contract_title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'vendor_name' => 'required|string|max:255',
            'contract_value' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'contract_date' => 'required|date',
            'start_date' => 'required|date|after_or_equal:contract_date',
            'end_date' => 'required|date|after:start_date',
            'contract_type' => 'required|in:purchase,service,rental,maintenance,other',
            'department_id' => 'required|exists:departments,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'project_code' => 'nullable|string|max:50',
            'budget_code' => 'nullable|string|max:50',
            // Files validation
            'contract_files' => 'required|array|min:1',
            'contract_files.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
            'file_descriptions.*' => 'nullable|string|max:255',
            'file_categories.*' => 'required|in:contract,attachment,amendment,approval,other',
        ]);

        DB::beginTransaction();

        try {
            // Generate contract number
            $contractNumber = ContractApproval::generateContractNumber();

            // Create contract approval
            $contract = ContractApproval::create([
                'contract_number' => $contractNumber,
                'contract_title' => $validated['contract_title'],
                'description' => $validated['description'],
                'vendor_name' => $validated['vendor_name'],
                'contract_value' => $validated['contract_value'],
                'currency' => $validated['currency'],
                'contract_date' => $validated['contract_date'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'contract_type' => $validated['contract_type'],
                'department_id' => $validated['department_id'],
                'priority' => $validated['priority'],
                'project_code' => $validated['project_code'],
                'budget_code' => $validated['budget_code'],
                'uploaded_by' => Auth::id(),
                'status' => 'pending',
            ]);

            // Upload files
            if ($request->hasFile('contract_files')) {
                foreach ($request->file('contract_files') as $index => $file) {
                    $fileName = time() . '_' . $index . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs('contracts/' . $contract->id, $fileName, 'public');

                    ContractFile::create([
                        'contract_approval_id' => $contract->id,
                        'file_name' => $fileName,
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $filePath,
                        'file_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'file_category' => $request->input('file_categories.' . $index, 'contract'),
                        'description' => $request->input('file_descriptions.' . $index),
                        'uploaded_by' => Auth::id(),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('contract-approvals.show', $contract)
                ->with('success', 'สัญญาได้ถูกอัพโหลดเรียบร้อยแล้ว รอการตรวจสอบ');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ContractApproval $contractApproval)
    {
        $contractApproval->load(['department', 'uploader', 'reviewer', 'files.uploader']);
        
        return view('contract_approvals.show', [
            'contract' => $contractApproval,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ContractApproval $contractApproval)
    {
        // Only allow editing if status is pending or rejected
        if (!in_array($contractApproval->status, ['pending', 'rejected'])) {
            return redirect()->route('contract-approvals.show', $contractApproval)
                ->with('error', 'ไม่สามารถแก้ไขสัญญาที่อยู่ในสถานะนี้ได้');
        }

        $departments = Department::active()->orderBy('name')->get();
        $contractApproval->load(['files']);
        
        return view('contract_approvals.edit', [
            'contract' => $contractApproval,
            'departments' => $departments,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ContractApproval $contractApproval)
    {
        // Only allow updating if status is pending or rejected
        if (!in_array($contractApproval->status, ['pending', 'rejected'])) {
            return redirect()->route('contract-approvals.show', $contractApproval)
                ->with('error', 'ไม่สามารถแก้ไขสัญญาที่อยู่ในสถานะนี้ได้');
        }

        $validated = $request->validate([
            'contract_title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'vendor_name' => 'required|string|max:255',
            'contract_value' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'contract_date' => 'required|date',
            'start_date' => 'required|date|after_or_equal:contract_date',
            'end_date' => 'required|date|after:start_date',
            'contract_type' => 'required|in:purchase,service,rental,maintenance,other',
            'department_id' => 'required|exists:departments,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'project_code' => 'nullable|string|max:50',
            'budget_code' => 'nullable|string|max:50',
            // New files validation
            'new_files.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'new_file_descriptions.*' => 'nullable|string|max:255',
            'new_file_categories.*' => 'required_with:new_files.*|in:contract,attachment,amendment,approval,other',
            // Files to delete
            'delete_files' => 'nullable|array',
            'delete_files.*' => 'exists:contract_files,id',
        ]);

        DB::beginTransaction();

        try {
            // Update contract approval
            $contractApproval->update([
                'contract_title' => $validated['contract_title'],
                'description' => $validated['description'],
                'vendor_name' => $validated['vendor_name'],
                'contract_value' => $validated['contract_value'],
                'currency' => $validated['currency'],
                'contract_date' => $validated['contract_date'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'contract_type' => $validated['contract_type'],
                'department_id' => $validated['department_id'],
                'priority' => $validated['priority'],
                'project_code' => $validated['project_code'],
                'budget_code' => $validated['budget_code'],
                'status' => 'pending', // Reset to pending when updated
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_notes' => null,
                'rejection_reason' => null,
            ]);

            // Delete selected files
            if ($request->has('delete_files')) {
                $filesToDelete = ContractFile::whereIn('id', $request->delete_files)
                    ->where('contract_approval_id', $contractApproval->id)
                    ->get();

                foreach ($filesToDelete as $file) {
                    // Delete file from storage
                    if (Storage::disk('public')->exists($file->file_path)) {
                        Storage::disk('public')->delete($file->file_path);
                    }
                    $file->delete();
                }
            }

            // Upload new files
            if ($request->hasFile('new_files')) {
                foreach ($request->file('new_files') as $index => $file) {
                    $fileName = time() . '_' . $index . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs('contracts/' . $contractApproval->id, $fileName, 'public');

                    ContractFile::create([
                        'contract_approval_id' => $contractApproval->id,
                        'file_name' => $fileName,
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $filePath,
                        'file_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'file_category' => $request->input('new_file_categories.' . $index, 'contract'),
                        'description' => $request->input('new_file_descriptions.' . $index),
                        'uploaded_by' => Auth::id(),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('contract-approvals.show', $contractApproval)
                ->with('success', 'สัญญาได้ถูกแก้ไขเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ContractApproval $contractApproval)
    {
        // Only allow deletion if status is pending or rejected
        if (!in_array($contractApproval->status, ['pending', 'rejected'])) {
            return redirect()->route('contract-approvals.show', $contractApproval)
                ->with('error', 'ไม่สามารถลบสัญญาที่อยู่ในสถานะนี้ได้');
        }

        try {
            // Delete all files from storage
            foreach ($contractApproval->files as $file) {
                if (Storage::disk('public')->exists($file->file_path)) {
                    Storage::disk('public')->delete($file->file_path);
                }
            }

            // Delete the contract (files will be deleted by cascade)
            $contractApproval->delete();

            return redirect()->route('contract-approvals.index')
                ->with('success', 'สัญญาได้ถูกลบเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            return redirect()->route('contract-approvals.show', $contractApproval)
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Start review process
     */
    public function startReview(ContractApproval $contractApproval)
    {
        if ($contractApproval->status !== 'pending') {
            return redirect()->route('contract-approvals.show', $contractApproval)
                ->with('error', 'ไม่สามารถเริ่มตรวจสอบสัญญาที่อยู่ในสถานะนี้ได้');
        }

        $contractApproval->update([
            'status' => 'under_review',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->route('contract-approvals.show', $contractApproval)
            ->with('success', 'เริ่มกระบวนการตรวจสอบสัญญาแล้ว');
    }

    /**
     * Approve contract
     */
    public function approve(Request $request, ContractApproval $contractApproval)
    {
        $request->validate([
            'review_notes' => 'nullable|string',
        ]);

        if (!in_array($contractApproval->status, ['pending', 'under_review'])) {
            return redirect()->route('contract-approvals.show', $contractApproval)
                ->with('error', 'ไม่สามารถอนุมัติสัญญาที่อยู่ในสถานะนี้ได้');
        }

        $contractApproval->update([
            'status' => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_notes' => $request->review_notes,
            'rejection_reason' => null,
        ]);

        return redirect()->route('contract-approvals.show', $contractApproval)
            ->with('success', 'สัญญาได้รับการอนุมัติเรียบร้อยแล้ว');
    }

    /**
     * Reject contract
     */
    public function reject(Request $request, ContractApproval $contractApproval)
    {
        $request->validate([
            'rejection_reason' => 'required|string',
            'review_notes' => 'nullable|string',
        ]);

        if (!in_array($contractApproval->status, ['pending', 'under_review'])) {
            return redirect()->route('contract-approvals.show', $contractApproval)
                ->with('error', 'ไม่สามารถไม่อนุมัติสัญญาที่อยู่ในสถานะนี้ได้');
        }

        $contractApproval->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_notes' => $request->review_notes,
            'rejection_reason' => $request->rejection_reason,
        ]);

        return redirect()->route('contract-approvals.show', $contractApproval)
            ->with('success', 'สัญญาถูกปฏิเสธแล้ว');
    }

    /**
     * Download contract file
     */
    public function downloadFile(ContractFile $file)
    {
        if (!Storage::disk('public')->exists($file->file_path)) {
            abort(404, 'ไม่พบไฟล์ที่ต้องการ');
        }

        return Storage::disk('public')->download($file->file_path, $file->original_name);
    }
}
