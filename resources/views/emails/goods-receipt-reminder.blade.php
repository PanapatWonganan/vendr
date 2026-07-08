<x-mail::message>
# 🔔 แจ้งเตือน: ใบตรวจรับงาน/วัสดุใกล้ครบกำหนด

สวัสดี คุณ{{ $inspectionCommittee->name ?? 'คณะกรรมการตรวจสอบ' }},

**⚠️ การแจ้งเตือนสำคัญ**: ใบตรวจรับงาน/วัสดุด้านล่างจะ**ครบกำหนดส่งมอบในอีก {{ $daysUntilDelivery }} วัน**

กรุณาดำเนินการตรวจสอบและอัปเดตสถานะโดยเร็วที่สุด

---

## รายละเอียดใบตรวจรับ

**เลขที่ GR:** {{ $goodsReceipt->gr_number ?: 'รอสร้าง' }}  
**วันที่รับ:** {{ $goodsReceipt->receipt_date ? \Carbon\Carbon::parse($goodsReceipt->receipt_date)->format('d/m/Y') : 'ไม่ระบุ' }}  
**งวดที่:** {{ $goodsReceipt->delivery_milestone }}  
**เปอร์เซ็นต์:** {{ $goodsReceipt->milestone_percentage }}%  

@if($purchaseOrder)
**เลขที่ PO:** {{ $purchaseOrder->po_number }}  
**หัวข้อ PO:** {{ $purchaseOrder->po_title }}  
**📅 วันที่ครบกำหนดส่งมอบ:** {{ $expectedDeliveryDate ? \Carbon\Carbon::parse($expectedDeliveryDate)->format('d/m/Y') : 'ไม่ระบุ' }}
@endif

@if($supplier)
**ผู้ขาย:** {{ $supplier->company_name ?? $supplier->name }}  
@endif

**สถานะตรวจสอบ:** 
@switch($goodsReceipt->inspection_status)
    @case('pending')
        🟡 **รอตรวจสอบ** ← **ต้องดำเนินการ**
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

**สถานะโดยรวม:** 
@switch($goodsReceipt->status)
    @case('draft')
        📝 **แบบร่าง** ← **รอดำเนินการ**
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

---

## 📋 การดำเนินการที่แนะนำ

@if($goodsReceipt->inspection_status === 'pending')
1. **ตรวจสอบงาน/วัสดุ** ที่ได้รับจากผู้ขาย
2. **อัปเดตสถานะการตรวจสอบ** ในระบบ
3. **เพิ่มหมายเหตุ** หากมีข้อสังเกตหรือปัญหา
4. **อนุมัติ** หรือ **ปฏิเสธ** การตรวจรับ
@else
- ตรวจสอบสถานะปัจจุบันและดำเนินการตามความเหมาะสม
@endif

<x-mail::button :url="config('app.url') . '/admin/goods-receipts/' . $goodsReceipt->id">
🔍 ดำเนินการตรวจสอบเลย
</x-mail::button>

---

⏰ **หมายเหตุ**: การแจ้งเตือนนี้ถูกส่งล่วงหน้า {{ $daysUntilDelivery }} วัน เพื่อให้คุณมีเวลาเพียงพอในการตรวจสอบและประสานงาน

หากมีคำถามหรือต้องการความช่วยเหลือ กรุณาติดต่อผู้สร้าง GR: {{ $creator->name }} ({{ $creator->email ?? 'ไม่มีอีเมล' }})

ขอบคุณสำหรับความร่วมมือครับ,  
{{ config('app.name') }}
</x-mail::message>
