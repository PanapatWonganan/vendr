<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\User;
use App\Models\Department;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display the dashboard
     */
    public function index()
    {
        $user = Auth::user();
        
        // Basic stats
        $totalUsers = User::count();
        $totalDepartments = Department::count();
        $totalRoles = Role::count();
        
        // Purchase Order stats
        $totalPOs = PurchaseOrder::count();
        $pendingApprovalPOs = PurchaseOrder::where('status', 'pending_approval')->count();
        $draftPOs = PurchaseOrder::where('status', 'draft')->count();
        
        // POs pending approval that the current user can approve
        $myPendingApprovals = 0;
        if ($user->isAdmin() || $user->hasRole('procurement_manager') || $user->hasRole('department_head')) {
            if ($user->isAdmin() || $user->hasRole('procurement_manager')) {
                // Admin and procurement managers can approve all POs
                $myPendingApprovals = $pendingApprovalPOs;
            } else if ($user->hasRole('department_head')) {
                // Department heads can approve POs from their department only
                $myPendingApprovals = PurchaseOrder::where('status', 'pending_approval')
                    ->where('department_id', $user->department_id)
                    ->count();
            }
        }
        
        // Recent POs for quick view
        $recentPOs = PurchaseOrder::with(['department', 'creator'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Calendar events for upcoming deadlines
        $calendarEvents = $this->getCalendarEvents();
        
        // PR Statistics
        $totalPRs = PurchaseRequisition::count();
        $pendingPRs = PurchaseRequisition::where('status', 'pending_approval')->count();
        $completedPRs = PurchaseRequisition::where('status', 'completed')->count();
        $directPurchasePRs = PurchaseRequisition::whereIn('pr_type', ['direct_small', 'direct_medium'])->count();
        
        return view('dashboard', compact(
            'totalUsers',
            'totalDepartments', 
            'totalRoles',
            'totalPOs',
            'pendingApprovalPOs',
            'draftPOs',
            'myPendingApprovals',
            'recentPOs',
            'calendarEvents',
            'totalPRs',
            'pendingPRs', 
            'completedPRs',
            'directPurchasePRs'
        ));
    }

    /**
     * Get calendar events for upcoming deadlines
     */
    private function getCalendarEvents()
    {
        $events = collect();
        $now = Carbon::now();
        $endDate = $now->copy()->addDays(30); // Show next 30 days

        // PO Delivery Dates (Blue tones)
        $poDeliveries = PurchaseOrder::whereBetween('expected_delivery_date', [$now->toDateString(), $endDate->toDateString()])
            ->whereIn('status', ['approved', 'in_progress'])
            ->whereNotNull('expected_delivery_date')
            ->get()
            ->map(function ($po) {
                $daysUntil = Carbon::parse($po->expected_delivery_date)->diffInDays(Carbon::now());
                $priority = $daysUntil <= 3 ? 'po_urgent' : ($daysUntil <= 7 ? 'po_high' : 'po_normal');
                $backgroundColor = $this->getPriorityColor($priority);
                
                return [
                    'id' => 'po_' . $po->id,
                    'title' => "PO: {$po->po_number}",
                    'start' => $po->expected_delivery_date,
                    'date' => $po->expected_delivery_date,
                    'type' => 'po_delivery',
                    'priority' => $priority,
                    'description' => "ครบกำหนดส่งของ PO {$po->po_number}",
                    'url' => route('purchase-orders.show', $po->id),
                    'days_until' => $daysUntil,
                    'className' => 'priority-' . $priority,
                    'backgroundColor' => $backgroundColor,
                    'borderColor' => $backgroundColor,
                    'extendedProps' => [
                        'entity_id' => $po->id,
                        'entity_type' => 'po',
                        'editable' => true
                    ]
                ];
            });

        // PR Required Dates (Green tones)
        $prRequired = PurchaseRequisition::whereBetween('required_date', [$now->toDateString(), $endDate->toDateString()])
            ->whereIn('status', ['pending_approval', 'approved'])
            ->get()
            ->map(function ($pr) {
                $daysUntil = Carbon::parse($pr->required_date)->diffInDays(Carbon::now());
                $priority = $daysUntil <= 3 ? 'pr_urgent' : ($daysUntil <= 7 ? 'pr_high' : 'pr_normal');
                $backgroundColor = $this->getPriorityColor($priority);
                
                return [
                    'id' => 'pr_' . $pr->id,
                    'title' => "PR: {$pr->pr_number}",
                    'start' => $pr->required_date,
                    'date' => $pr->required_date,
                    'type' => 'pr_required',
                    'priority' => $priority,
                    'description' => "ครบกำหนดต้องการสินค้า PR {$pr->pr_number}",
                    'url' => route('purchase-requisitions.show', $pr->id),
                    'days_until' => $daysUntil,
                    'className' => 'priority-' . $priority,
                    'backgroundColor' => $backgroundColor,
                    'borderColor' => $backgroundColor,
                    'extendedProps' => [
                        'entity_id' => $pr->id,
                        'entity_type' => 'pr',
                        'editable' => true
                    ]
                ];
            });

        // Overdue items (Red - critical)
        $overduePOs = PurchaseOrder::where('expected_delivery_date', '<', $now->toDateString())
            ->whereIn('status', ['approved', 'in_progress'])
            ->whereNotNull('expected_delivery_date')
            ->get()
            ->map(function ($po) {
                $priority = 'overdue';
                $backgroundColor = $this->getPriorityColor($priority);
                
                return [
                    'id' => 'po_overdue_' . $po->id,
                    'title' => "⚠️ PO: {$po->po_number}",
                    'start' => $po->expected_delivery_date,
                    'date' => $po->expected_delivery_date,
                    'type' => 'po_overdue',
                    'priority' => $priority,
                    'description' => "PO {$po->po_number} เลยกำหนดส่งของแล้ว",
                    'url' => route('purchase-orders.show', $po->id),
                    'days_until' => Carbon::parse($po->expected_delivery_date)->diffInDays(Carbon::now(), false), // negative
                    'className' => 'priority-' . $priority,
                    'backgroundColor' => $backgroundColor,
                    'borderColor' => $backgroundColor,
                    'extendedProps' => [
                        'entity_id' => $po->id,
                        'entity_type' => 'po',
                        'editable' => true
                    ]
                ];
            });

        // Merge all events
        $events = $events->merge($poDeliveries)
                        ->merge($prRequired)
                        ->merge($overduePOs)
                        ->sortBy('date');

        return $events;
    }

    /**
     * Get color for priority level
     */
    private function getPriorityColor($priority)
    {
        switch ($priority) {
            // PO Colors (Blue tones)
            case 'po_urgent':
                return '#1e3a8a'; // Dark blue
            case 'po_high':
                return '#3b82f6'; // Medium blue
            case 'po_normal':
                return '#93c5fd'; // Light blue
                
            // PR Colors (Green tones)
            case 'pr_urgent':
                return '#166534'; // Dark green
            case 'pr_high':
                return '#22c55e'; // Medium green
            case 'pr_normal':
                return '#86efac'; // Light green
                
            // Overdue (Red)
            case 'overdue':
                return '#dc2626'; // Red
                
            // Legacy fallback
            case 'urgent':
                return '#e53e3e';
            case 'high':
                return '#ff8c00';
            case 'medium':
                return '#4299e1';
            case 'low':
            default:
                return '#38a169';
        }
    }

    /**
     * Update event date via drag & drop
     */
    public function updateEventDate(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
            'type' => 'required|in:po,pr',
            'new_date' => 'required|date',
        ]);

        $success = false;
        $message = '';

        try {
            if ($validated['type'] === 'po') {
                $po = PurchaseOrder::find($validated['id']);
                if ($po && $po->canEdit()) {
                    $po->update(['expected_delivery_date' => $validated['new_date']]);
                    $success = true;
                    $message = 'อัปเดตวันที่ส่งของ PO สำเร็จ';
                } else {
                    $message = 'ไม่พบ PO หรือไม่สามารถแก้ไขได้';
                }
            } else if ($validated['type'] === 'pr') {
                $pr = PurchaseRequisition::find($validated['id']);
                if ($pr && $pr->canEdit()) {
                    $pr->update(['required_date' => $validated['new_date']]);
                    $success = true;
                    $message = 'อัปเดตวันที่ต้องการ PR สำเร็จ';
                } else {
                    $message = 'ไม่พบ PR หรือไม่สามารถแก้ไขได้';
                }
            }
        } catch (\Exception $e) {
            $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }

        return response()->json([
            'success' => $success,
            'message' => $message
        ]);
    }
} 