<x-mail::message>
# {{ $isCreatorCopy ? 'สำเนา: ' : '' }}แจ้งเตือนใบตรวจรับงาน/วัสดุ (GR)

@if($isCreatorCopy)
สวัสดี คุณ{{ $creator->name }},

นี่เป็นสำเนาการแจ้งเตือนสำหรับใบตรวจรับงาน/วัสดุที่คุณได้สร้างไว้
@else
สวัสดี คุณ{{ $inspectionCommittee->name ?? 'คณะกรรมการตรวจสอบ' }},

คุณได้รับมอบหมายให้เป็นคณะกรรมการตรวจสอบสำหรับใบตรวจรับงาน/วัสดุใหม่
@endif

## รายละเอียดใบตรวจรับ

**เลขที่ GR:** {{ $goodsReceipt->gr_number ?: 'รอสร้าง' }}  
**วันที่รับ:** {{ $goodsReceipt->receipt_date ? \Carbon\Carbon::parse($goodsReceipt->receipt_date)->format('d/m/Y') : 'ไม่ระบุ' }}  
**งวดที่:** {{ $goodsReceipt->delivery_milestone }}  
**เปอร์เซ็นต์:** {{ $goodsReceipt->milestone_percentage }}%  

@if($purchaseOrder)
**เลขที่ PO:** {{ $purchaseOrder->po_number }}  
**หัวข้อ PO:** {{ $purchaseOrder->po_title }}  
@endif

@if($supplier)
**ผู้ขาย:** {{ $supplier->name }}  
@endif

**สถานะตรวจสอบ:** 
@switch($goodsReceipt->inspection_status)
    @case('pending')
        🟡 รอตรวจสอบ
        @break
    @case('passed')
        ✅ ผ่านการตรวจสอบ
        @break
    @case('failed')
        ❌ ไม่ผ่านการตรวจสอบ
        @break
    @case('partial')
        🟠 ผ่านบางส่วน
        @break
    @default
        {{ $goodsReceipt->inspection_status }}
@endswitch

**สถานะ:** 
@switch($goodsReceipt->status)
    @case('draft')
        📝 แบบร่าง
        @break
    @case('completed')
        ✅ เสร็จสมบูรณ์
        @break
    @case('returned')
        🔄 ส่งคืน
        @break
    @case('partially_returned')
        🔄 ส่งคืนบางส่วน
        @break
    @case('cancelled')
        ❌ ยกเลิก
        @break
    @default
        {{ $goodsReceipt->status }}
@endswitch

@if($goodsReceipt->notes)
**หมายเหตุ:** {{ $goodsReceipt->notes }}
@endif

@if($goodsReceipt->inspection_notes)
**หมายเหตุการตรวจสอบ:** {{ $goodsReceipt->inspection_notes }}
@endif

**ผู้สร้าง:** {{ $creator->name }}  
**วันที่สร้าง:** {{ $goodsReceipt->created_at ? $goodsReceipt->created_at->format('d/m/Y H:i') : 'ไม่ทราบ' }}

<x-mail::button :url="config('app.url') . '/admin/goods-receipts/' . $goodsReceipt->id">
ดูรายละเอียดใบตรวจรับ
</x-mail::button>

@if(!$isCreatorCopy && $goodsReceipt->inspection_status === 'pending')
กรุณาเข้าสู่ระบบเพื่อตรวจสอบและอัปเดตสถานะการตรวจรับนี้
@endif

ขอบคุณครับ,  
{{ config('app.name') }}
</x-mail::message>
