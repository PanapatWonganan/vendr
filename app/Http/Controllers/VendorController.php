<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VendorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $vendors = Vendor::with('company')
            ->byCompany(session('company_id'))
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('vendors.index', compact('vendors'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $workCategories = [
            'construction' => 'ก่อสร้าง',
            'manufacturing' => 'การผลิต',
            'it_services' => 'บริการด้านไอที',
            'consulting' => 'ที่ปรึกษา',
            'maintenance' => 'บำรุงรักษา',
            'transportation' => 'ขนส่ง',
            'catering' => 'จัดเลี้ยง',
            'security' => 'รักษาความปลอดภัย',
            'cleaning' => 'ทำความสะอาด',
            'others' => 'อื่นๆ',
        ];
        
        return view('vendors.create', compact('workCategories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'tax_id' => [
                'required',
                'string',
                'max:20',
                'unique:vendors,tax_id',
            ],
            'address' => 'required|string',
            'work_category' => 'required|string',
            'experience' => 'nullable|string',
            'contact_name' => 'required|string|max:255',
            'contact_phone' => 'required|string|max:20',
            'contact_email' => 'required|email|max:255',
            'documents.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240', // 10MB max
        ]);

        $documents = [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store('vendor-documents', 'public');
                $documents[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                ];
            }
        }

        Vendor::create([
            'company_id' => session('company_id'),
            'company_name' => $request->company_name,
            'tax_id' => $request->tax_id,
            'address' => $request->address,
            'work_category' => $request->work_category,
            'experience' => $request->experience,
            'contact_name' => $request->contact_name,
            'contact_phone' => $request->contact_phone,
            'contact_email' => $request->contact_email,
            'documents' => $documents,
        ]);

        return redirect()->route('vendors.index')
            ->with('success', 'ลงทะเบียนผู้ขายเรียบร้อยแล้ว');
    }

    /**
     * Display the specified resource.
     */
    public function show(Vendor $vendor)
    {
        // Check if vendor belongs to current company
        if ($vendor->company_id !== session('company_id')) {
            abort(403);
        }
        
        return view('vendors.show', compact('vendor'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vendor $vendor)
    {
        // Check if vendor belongs to current company
        if ($vendor->company_id !== session('company_id')) {
            abort(403);
        }
        
        $workCategories = [
            'construction' => 'ก่อสร้าง',
            'manufacturing' => 'การผลิต',
            'it_services' => 'บริการด้านไอที',
            'consulting' => 'ที่ปรึกษา',
            'maintenance' => 'บำรุงรักษา',
            'transportation' => 'ขนส่ง',
            'catering' => 'จัดเลี้ยง',
            'security' => 'รักษาความปลอดภัย',
            'cleaning' => 'ทำความสะอาด',
            'others' => 'อื่นๆ',
        ];
        
        return view('vendors.edit', compact('vendor', 'workCategories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vendor $vendor)
    {
        // Check if vendor belongs to current company
        if ($vendor->company_id !== session('company_id')) {
            abort(403);
        }
        
        $request->validate([
            'company_name' => 'required|string|max:255',
            'tax_id' => [
                'required',
                'string',
                'max:20',
                'unique:vendors,tax_id,' . $vendor->id,
            ],
            'address' => 'required|string',
            'work_category' => 'required|string',
            'experience' => 'nullable|string',
            'contact_name' => 'required|string|max:255',
            'contact_phone' => 'required|string|max:20',
            'contact_email' => 'required|email|max:255',
            'documents.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240', // 10MB max
        ]);

        $documents = $vendor->documents ?? [];
        
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store('vendor-documents', 'public');
                $documents[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                ];
            }
        }

        $vendor->update([
            'company_name' => $request->company_name,
            'tax_id' => $request->tax_id,
            'address' => $request->address,
            'work_category' => $request->work_category,
            'experience' => $request->experience,
            'contact_name' => $request->contact_name,
            'contact_phone' => $request->contact_phone,
            'contact_email' => $request->contact_email,
            'documents' => $documents,
        ]);

        return redirect()->route('vendors.index')
            ->with('success', 'อัพเดทข้อมูลผู้ขายเรียบร้อยแล้ว');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vendor $vendor)
    {
        // Check if vendor belongs to current company
        if ($vendor->company_id !== session('company_id')) {
            abort(403);
        }
        
        // Delete uploaded documents
        if ($vendor->documents) {
            foreach ($vendor->documents as $document) {
                if (isset($document['path'])) {
                    Storage::disk('public')->delete($document['path']);
                }
            }
        }
        
        $vendor->delete();

        return redirect()->route('vendors.index')
            ->with('success', 'ลบข้อมูลผู้ขายเรียบร้อยแล้ว');
    }
    
    /**
     * Approve vendor
     */
    public function approve(Vendor $vendor)
    {
        // Check if vendor belongs to current company
        if ($vendor->company_id !== session('company_id')) {
            abort(403);
        }
        
        $vendor->approve();
        
        return redirect()->back()
            ->with('success', 'อนุมัติผู้ขายเรียบร้อยแล้ว');
    }
    
    /**
     * Reject vendor
     */
    public function reject(Vendor $vendor)
    {
        // Check if vendor belongs to current company
        if ($vendor->company_id !== session('company_id')) {
            abort(403);
        }
        
        $vendor->reject();
        
        return redirect()->back()
            ->with('success', 'ปฏิเสธผู้ขายเรียบร้อยแล้ว');
    }
    
    /**
     * Suspend vendor
     */
    public function suspend(Vendor $vendor)
    {
        // Check if vendor belongs to current company
        if ($vendor->company_id !== session('company_id')) {
            abort(403);
        }
        
        $vendor->suspend();
        
        return redirect()->back()
            ->with('success', 'ระงับผู้ขายเรียบร้อยแล้ว');
    }
    
    /**
     * Remove document
     */
    public function removeDocument(Vendor $vendor, Request $request)
    {
        // Check if vendor belongs to current company
        if ($vendor->company_id !== session('company_id')) {
            abort(403);
        }
        
        $documentIndex = $request->input('document_index');
        $documents = $vendor->documents ?? [];
        
        if (isset($documents[$documentIndex])) {
            // Delete file from storage
            if (isset($documents[$documentIndex]['path'])) {
                Storage::disk('public')->delete($documents[$documentIndex]['path']);
            }
            
            // Remove from array
            unset($documents[$documentIndex]);
            $documents = array_values($documents); // Reindex array
            
            $vendor->update(['documents' => $documents]);
        }
        
        return redirect()->back()
            ->with('success', 'ลบเอกสารเรียบร้อยแล้ว');
    }
}
