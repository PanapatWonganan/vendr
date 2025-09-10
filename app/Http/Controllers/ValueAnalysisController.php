<?php

namespace App\Http\Controllers;

use App\Models\ValueAnalysis;
use App\Models\PurchaseRequisition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ValueAnalysisController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $valueAnalyses = ValueAnalysis::with(['purchaseRequisition', 'creator', 'analyzer', 'approver'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('value_analysis.index', compact('valueAnalyses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Get approved purchase requisitions that don't have value analysis yet
        $purchaseRequisitions = PurchaseRequisition::with(['department', 'requester'])
            ->whereIn('status', ['approved', 'pending_approval'])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('value_analysis')
                    ->whereRaw('value_analysis.purchase_requisition_id = purchase_requisitions.id');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('value_analysis.create', compact('purchaseRequisitions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_requisition_id' => 'required|exists:purchase_requisitions,id',
            'procured_from' => 'nullable|string|max:1000',
            'agreed_amount' => 'nullable|numeric|min:0',
        ]);

        // Get PR details
        $pr = PurchaseRequisition::findOrFail($validated['purchase_requisition_id']);

        // Check if VA already exists for this PR
        $existingVA = ValueAnalysis::where('purchase_requisition_id', $pr->id)->first();
        if ($existingVA) {
            return back()->with('error', 'Value Analysis สำหรับ PR นี้มีอยู่แล้ว');
        }

        DB::beginTransaction();
        try {
            // Generate VA number
            $vaNumber = ValueAnalysis::generateVANumber();

            // Create Value Analysis
            $valueAnalysis = ValueAnalysis::create([
                'va_number' => $vaNumber,
                'purchase_requisition_id' => $pr->id,
                'work_type' => $pr->work_type,
                'procurement_method' => $pr->procurement_method,
                'procured_from' => $validated['procured_from'] ?? null,
                'agreed_amount' => $validated['agreed_amount'] ?? null,
                'total_budget' => $pr->total_amount,
                'currency' => $pr->currency,
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('value-analysis.show', $valueAnalysis)
                ->with('success', 'สร้าง Value Analysis เรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ValueAnalysis $valueAnalysis)
    {
        $valueAnalysis->load(['purchaseRequisition.department', 'purchaseRequisition.requester', 'creator', 'analyzer', 'approver']);
        
        return view('value_analysis.show', compact('valueAnalysis'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ValueAnalysis $valueAnalysis)
    {
        if (!$valueAnalysis->canEdit()) {
            return back()->with('error', 'ไม่สามารถแก้ไข Value Analysis นี้ได้');
        }

        return view('value_analysis.edit', compact('valueAnalysis'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ValueAnalysis $valueAnalysis)
    {
        if (!$valueAnalysis->canEdit()) {
            return back()->with('error', 'ไม่สามารถแก้ไข Value Analysis นี้ได้');
        }

        $validated = $request->validate([
            'analysis_objective' => 'nullable|string',
            'analysis_scope' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'conclusion' => 'nullable|string',
        ]);

        $valueAnalysis->update($validated);

        return redirect()->route('value-analysis.show', $valueAnalysis)
            ->with('success', 'อัพเดท Value Analysis เรียบร้อยแล้ว');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ValueAnalysis $valueAnalysis)
    {
        if (!$valueAnalysis->canEdit()) {
            return back()->with('error', 'ไม่สามารถลบ Value Analysis นี้ได้');
        }

        $valueAnalysis->delete();

        return redirect()->route('value-analysis.index')
            ->with('success', 'ลบ Value Analysis เรียบร้อยแล้ว');
    }

    /**
     * Get PR details via AJAX
     */
    public function getPRDetails($pr_id)
    {
        $pr = PurchaseRequisition::with(['department', 'requester', 'items'])
            ->findOrFail($pr_id);

        return response()->json([
            'work_type' => $pr->work_type,
            'work_type_label' => $pr->work_type_label,
            'procurement_method' => $pr->procurement_method,
            'procurement_method_label' => $pr->procurement_method_label,
            'total_amount' => number_format($pr->total_amount, 2),
            'currency' => $pr->currency,
            'department' => $pr->department->name,
            'requester' => $pr->requester->name,
            'title' => $pr->title,
            'description' => $pr->description,
        ]);
    }

    /**
     * Start analysis process
     */
    public function startAnalysis(ValueAnalysis $valueAnalysis)
    {
        if ($valueAnalysis->startAnalysis()) {
            return back()->with('success', 'เริ่มการวิเคราะห์แล้ว');
        }

        return back()->with('error', 'ไม่สามารถเริ่มการวิเคราะห์ได้');
    }

    /**
     * Complete analysis
     */
    public function completeAnalysis(ValueAnalysis $valueAnalysis)
    {
        if ($valueAnalysis->complete()) {
            return back()->with('success', 'การวิเคราะห์เสร็จสิ้นแล้ว รอการอนุมัติ');
        }

        return back()->with('error', 'ไม่สามารถทำเครื่องหมายเสร็จสิ้นได้');
    }

    /**
     * Approve analysis
     */
    public function approve(ValueAnalysis $valueAnalysis)
    {
        if (!$valueAnalysis->canApprove()) {
            return back()->with('error', 'ไม่สามารถอนุมัติ Value Analysis นี้ได้');
        }

        if ($valueAnalysis->approve(Auth::id())) {
            return back()->with('success', 'อนุมัติ Value Analysis เรียบร้อยแล้ว');
        }

        return back()->with('error', 'เกิดข้อผิดพลาดในการอนุมัติ');
    }

    /**
     * Reject analysis
     */
    public function reject(ValueAnalysis $valueAnalysis)
    {
        if ($valueAnalysis->reject()) {
            return back()->with('success', 'ปฏิเสธ Value Analysis แล้ว');
        }

        return back()->with('error', 'เกิดข้อผิดพลาดในการปฏิเสธ');
    }
}
