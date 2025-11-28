<x-mail::message>
# {{ $isPayerCopy ? 'สำเนา: ' : '' }}แจ้งการจ่ายเงินงวดที่ {{ $paymentMilestone->milestone_number }}

@if($isPayerCopy)
สวัสดี คุณ{{ $payer->name }},

นี่เป็นสำเนาการแจ้งเตือนสำหรับการจ่ายเงินงวดที่คุณได้บันทึกไว้
@else
สวัสดี คุณ{{ $inspectionCommittee->name ?? 'คณะกรรมการตรวจสอบ' }},

มีการจ่ายเงินงวดใหม่ที่เกี่ยวข้องกับ Purchase Order ที่คุณดูแลอยู่
@endif

## รายละเอียดการจ่ายเงิน

**งวดที่:** {{ $paymentMilestone->milestone_number }}  
**ชื่องวด:** {{ $paymentMilestone->milestone_title }}  
**จำนวนเงิน:** ฿{{ number_format($paymentMilestone->amount, 2) }}  
**จำนวนที่จ่าย:** ฿{{ number_format($paymentMilestone->paid_amount, 2) }}  
**วันที่จ่าย:** {{ $paymentMilestone->paid_date ? \Carbon\Carbon::parse($paymentMilestone->paid_date)->format('d/m/Y') : 'ไม่ระบุ' }}  

@if($paymentMilestone->payment_reference)
**เลขที่อ้างอิง:** {{ $paymentMilestone->payment_reference }}  
@endif

@if($purchaseOrder)
## ข้อมูล Purchase Order

**เลขที่ PO:** {{ $purchaseOrder->po_number }}  
**หัวข้อ PO:** {{ $purchaseOrder->po_title }}  
**ยอดรวม PO:** ฿{{ number_format($purchaseOrder->total_amount, 2) }}  
@endif

@if($vendor)
**ผู้ขาย:** {{ $vendor->company_name }}  
@endif

**สถานะ:** 
@switch($paymentMilestone->status)
    @case('paid')
        ✅ จ่ายแล้ว
        @break
    @case('pending')
        🟡 รอจ่าย
        @break
    @case('overdue')
        🔴 เกินกำหนด
        @break
    @case('cancelled')
        ❌ ยกเลิก
        @break
    @default
        {{ $paymentMilestone->status }}
@endswitch

@if($paymentMilestone->payment_notes)
**หมายเหตุการจ่าย:** {{ $paymentMilestone->payment_notes }}
@endif

**ผู้บันทึกการจ่าย:** {{ $payer->name }}  
**วันที่บันทึก:** {{ $paymentMilestone->updated_at ? $paymentMilestone->updated_at->format('d/m/Y H:i') : 'ไม่ทราบ' }}

<x-mail::button :url="config('app.url') . '/admin/payment-milestones/' . $paymentMilestone->id . '/edit'">
ดูรายละเอียดงวดการจ่าย
</x-mail::button>

@if(!$isPayerCopy)
กรุณาเข้าสู่ระบบเพื่อตรวจสอบข้อมูลการจ่ายเงินงวดนี้
@endif

ขอบคุณครับ,  
{{ config('app.name') }}
</x-mail::message>