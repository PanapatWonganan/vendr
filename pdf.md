 เมื่อ อนุมัติ Purchase Order ระบบจะทำงานดังนี้:

  [1] อนุมัติ PO → [2] ส่ง Event → [3] Listener รับ Event → [4] สร้าง PDF 2 ไฟล์
  → [5] ส่ง Email พร้อม PDF แนบ

  ---
  📂 โครงสร้างไฟล์

  1. Event Listener

  app/Listeners/SendPurchaseOrderApprovedNotification.php

  - รับ Event PurchaseOrderApproved
  - สร้าง PDF 2 ไฟล์
  - ส่ง Email ไปยังผู้เกี่ยวข้อง

  2. Mail Class

  app/Mail/PurchaseOrderApprovedMail.php

  - จัดการเนื้อหา Email
  - แนบไฟล์ PDF

  3. PDF Services

  A. PurchaseOrderPdfService (app/Services/PurchaseOrderPdfService.php)
  - สร้าง SOW (Scope of Work) PDF
  - เลือก template ตาม work_type

  B. DeliveryNotePdfService (app/Services/DeliveryNotePdfService.php)
  - สร้าง Delivery Note PDF
  - ใช้สำหรับใบส่งของ/ใบตรวจรับ

  4. Email Template

  resources/views/emails/purchase-order-approved.blade.php

  5. PDF Templates

  - resources/views/pdf/purchase-orders/purchase.blade.php (ซื้อ)
  - resources/views/pdf/purchase-orders/hire-sow.blade.php (จ้าง)
  - resources/views/pdf/purchase-orders/rent.blade.php (เช่า)
  - resources/views/pdf/delivery-note.blade.php (ใบส่งของ)

  ---
  🎯 การทำงานแบบละเอียด

  Phase 1: การสร้าง PDF

  📄 PDF ที่ 1: SOW (Scope of Work)

  ไฟล์: PurchaseOrderPdfService.php:19-66

  // ตัวอย่างการทำงาน
  public function generatePdf(PurchaseOrder $purchaseOrder): string
  {
      // 1. เลือก template ตาม work_type
      $template = $this->selectTemplate($purchaseOrder);

      // 2. เตรียมข้อมูล
      $data = $this->prepareData($purchaseOrder);

      // 3. สร้าง PDF ด้วย mPDF (รองรับภาษาไทย)
      $pdf = PDF::loadView($template, $data, [], $config);

      return $pdf->output(); // คืนค่าเป็น binary string
  }

  การเลือก Template:

  | work_type   | Template                     | รหัสเอกสาร  |
  |-------------|------------------------------|-------------|
  | buy (ซื้อ)  | pdf.purchase-orders.purchase | PCMN-002-FO |
  | hire (จ้าง) | pdf.purchase-orders.hire-sow | PCM-002     |
  | rent (เช่า) | pdf.purchase-orders.rent     | PCMN-002-FO |

  ข้อมูลที่ส่งไปยัง Template:

  [
      'purchaseOrder' => $purchaseOrder,
      'workTypeLabel' => 'ซื้อ/จ้าง/เช่า',
      'documentCode' => 'PCM-002 หรือ PCMN-002-FO',
      'participantLabel' => 'ผู้ขาย/ผู้รับจ้าง/ผู้ให้เช่า',
      'penaltyRate' => '0.1 หรือ 0.2',
      'procurementMethodLabel' => 'ตกลงราคา/เชิญชวน/...',
      'company' => [...], // ข้อมูลบริษัท
      'printDate' => '17/10/2025 15:30',
      'approvalDate' => '17/10/2025',
  ]

  ชื่อไฟล์ที่สร้าง:
  SOW_PURCHASE_PO-20251017-0001_20251017153045.pdf
  SOW_HIRE_PO-20251017-0001_20251017153045.pdf
  SOW_RENT_PO-20251017-0001_20251017153045.pdf

  ---
  📦 PDF ที่ 2: Delivery Note (ใบส่งของ)

  ไฟล์: DeliveryNotePdfService.php:19-62

  public function generatePdf(PurchaseOrder $purchaseOrder): string
  {
      // 1. เตรียมข้อมูล (ข้อมูลผู้ขาย, ที่อยู่, items)
      $data = $this->prepareData($purchaseOrder);

      // 2. สร้าง PDF จาก template เดียว
      $pdf = PDF::loadView('pdf.delivery-note', $data, [], $config);

      return $pdf->output();
  }

  ข้อมูลที่ส่งไปยัง Template:

  [
      'purchaseOrder' => $purchaseOrder,
      'vendorName' => 'ชื่อผู้ขาย',
      'vendorAddress' => 'ที่อยู่ผู้ขาย',
      'company' => [...], // ข้อมูลบริษัท
      'printDate' => '17/10/2025 15:30',
  ]

  ชื่อไฟล์ที่สร้าง:
  DeliveryNote_PO-20251017-0001_20251017153045.pdf

  ---
  Phase 2: การส่ง Email

  ไฟล์: SendPurchaseOrderApprovedNotification.php:24-450

  ผู้รับ Email (ส่งแยกกัน 3 ฉบับ):

  1. คณะกรรมการตรวจรับ (Inspection Committee)
  // บรรทัด 258-318
  if ($inspectionCommittee && $inspectionCommittee->email) {
      Mail::to($inspectionCommittee->email)
          ->send(new PurchaseOrderApprovedMail(
              $purchaseOrder,
              $approver,
              $inspectionCommittee,  // ← recipient (internal)
              $pdfContent,            // ← SOW PDF
              $pdfFilename,
              $deliveryNotePdfContent, // ← Delivery Note PDF
              $deliveryNotePdfFilename
          ));
  }

  2. ผู้ขาย (Vendor)
  // บรรทัด 320-373
  if ($vendorEmail) {
      Mail::to($vendorEmail)
          ->send(new PurchaseOrderApprovedMail(
              $purchaseOrder,
              $approver,
              null,  // ← recipient null (external)
              $pdfContent,
              $pdfFilename,
              $deliveryNotePdfContent,
              $deliveryNotePdfFilename
          ));
  }

  3. คณะกรรมการตรวจรับ (อีกครั้ง - สำหรับ legacy code)
  // บรรทัด 375-424 (duplicate สำหรับ backward compatibility)

  เนื้อหา Email แตกต่างกันตาม Recipient:

  A. สำหรับคณะกรรมการตรวจรับ (Internal):
  สวัสดีคุณ [ชื่อ],

  เรามีความยินดีที่จะแจ้งให้ทราบว่า ใบ Purchase Order ของคุณ
  ได้รับการอนุมัติเรียบร้อยแล้ว

  📋 ขั้นตอนต่อไป:
  - ใบ PO พร้อมสำหรับการส่งให้ผู้ขายแล้ว
  - ทีมจัดซื้อจะดำเนินการส่ง PO ให้ผู้ขายต่อไป
  - โปรดเตรียมตัวสำหรับการตรวจรับสินค้า

  B. สำหรับผู้ขาย (External):
  สวัสดี,

  เรามีความยินดีที่จะแจ้งให้ทราบว่า บริษัทฯ ได้อนุมัติใบ
  Purchase Order แล้ว และขอให้ท่านดำเนินการตามรายละเอียด
  ดังต่อไปนี้

  📋 ขั้นตอนต่อไป:
  - ยืนยันการรับ PO ภายใน 2 วันทำการ
  - เตรียมสินค้า/บริการ
  - ประสานการส่งมอบ
  - จัดส่งใบกำกับภาษี

  ไฟล์ PDF แนบใน Email:

  ✅ SOW PDF - เอกสาร Scope of Work (ตาม work_type)✅ Delivery Note PDF - ใบส่งของ/ใบตรวจรับ

  ---
  🎨 รายละเอียด Email Template

  ไฟล์: resources/views/emails/purchase-order-approved.blade.php

  โครงสร้าง Email:

  ┌─────────────────────────────────────┐
  │ ✅ ใบ PO ได้รับการอนุมัติแล้ว        │ ← Header (สีเขียว)
  ├─────────────────────────────────────┤
  │ สวัสดี [ชื่อ],                       │
  │                                     │
  │ 📋 รายละเอียดใบ PO                  │
  │ ┌─────────────────────────────────┐ │
  │ │ เลขที่ PO: PO-20251017-0001    │ │
  │ │ หัวข้อ: ทดสอบ 004              │ │
  │ │ ผู้ขาย: ABC Company            │ │
  │ │ มูลค่า: 42,800.00 THB          │ │
  │ │ สถานะ: [อนุมัติแล้ว]           │ │
  │ └─────────────────────────────────┘ │
  │                                     │
  │ 👤 ผู้อนุมัติ: Admin               │
  │ ⏰ เวลาที่อนุมัติ: 17/10/2025      │
  │                                     │
  │ [🔍 ดูรายละเอียดใบ PO]             │ ← ปุ่ม
  │                                     │
  │ 📋 ขั้นตอนต่อไป                    │
  │ • ...                               │
  ├─────────────────────────────────────┤
  │ 🏢 INNOBIC                          │ ← Footer
  │ ระบบจัดการ Purchase Order          │
  └─────────────────────────────────────┘

  CSS Styling:

  - Font: Sarabun (รองรับภาษาไทย)
  - Colors:
    - Header: สีเขียว Gradient #28a745 → #20c997
    - ปุ่ม: สีน้ำเงิน Gradient #007bff → #0056b3
    - Status Badge: สีเขียว #28a745
  - Responsive: รองรับ mobile (max-width: 600px)

  ---
  📊 PDF Template Structure

  ตัวอย่าง: Purchase Template (ซื้อ)

  resources/views/pdf/purchase-orders/purchase.blade.php

  ┌──────────────────────────────────────┐
  │         รหัสเอกสาร: PCMN-002-FO       │ ← Header Code
  ├──────────────────────────────────────┤
  │           ใบขอซื้อ/จ้าง              │
  │     บริษัท อินโนบิค นูทริชั่น จำกัด   │ ← Title
  ├──────────────────────────────────────┤
  │ 1. ข้อมูลทั่วไป                      │
  │    □ ซื้อ ☒ จ้าง □ เช่า             │ ← Checkboxes
  │    เลขที่ PO: PO-20251017-0001      │
  │    วันที่: 17/10/2025                │
  │                                      │
  │ 2. รายละเอียดงาน                     │
  │    ชื่องาน: ______________________   │
  │    ผู้ขาย: _______________________   │
  │    วิธีจัดหา: ตกลงราคา              │
  │                                      │
  │ 3. รายการสินค้า/บริการ               │
  │    ┌─────┬────────┬──────┬────────┐ │
  │    │ ลำดับ│รายการ  │จำนวน│  ราคา  │ │
  │    ├─────┼────────┼──────┼────────┤ │
  │    │  1  │ ...    │ 10   │ 1,000  │ │
  │    └─────┴────────┴──────┴────────┘ │
  │                                      │
  │    ยอดรวม: 10,000.00 บาท            │
  │    VAT 7%:    700.00 บาท            │
  │    รวมทั้งสิ้น: 10,700.00 บาท       │
  │                                      │
  │ 4. เงื่อนไขและข้อตกลง                │
  │    - การจ่ายเงิน: ...               │
  │    - การส่งมอบ: ...                 │
  │    - ค่าปรับ: 0.2% ต่อวัน          │
  │                                      │
  │ 5. ลายเซ็น                           │
  │    ผู้ขอซื้อ: _________________     │
  │    ผู้อนุมัติ: _________________    │
  │    วันที่: __/__/____               │
  └──────────────────────────────────────┘

  Font สำหรับ PDF:

  - THSarabunNew / freeserif (รองรับภาษาไทย)
  - mPDF Library ใช้สำหรับสร้าง PDF
  - Config: UTF-8, A4, Portrait

  ---
  🔧 จุดสำคัญที่ต้องรู้

  1. Prevent Duplicate Emails

  // บรรทัด 27-41
  $eventKey = "po_approved_" . $event->purchaseOrderId . '_' . $event->approverId;

  if (Cache::has($eventKey)) {
      return; // ป้องกันส่ง email ซ้ำ
  }

  Cache::put($eventKey, now()->toDateTimeString(), 300); // 5 นาที

  2. Multi-Database Support

  // บรรทัด 44-109
  // รองรับหลาย database connections:
  // - mysql (main)
  // - innobic_asia
  // - innobic_nutrition  
  // - innobic_ll

  3. Error Handling

  // บรรทัด 221-256
  try {
      $pdfContent = $pdfService->generatePdf($purchaseOrder);
      Log::info('PDF generated successfully');
  } catch (\Exception $e) {
      Log::error('Failed to generate PDF');
      // ยังคงส่ง email ต่อไป (ไม่มี PDF แนบ)
  }

  4. Relationship Loading

  // PurchaseOrderPdfService.php:100-107
  $purchaseOrder->load([
      'creator',       // ผู้สร้าง PO
      'vendor',        // ผู้ขาย
      'inspectionCommittee', // คณะกรรมการ
      'purchaseRequisition', // PR ที่เกี่ยวข้อง
      'items',         // รายการสินค้า
      'approver',      // ผู้อนุมัติ
  ]);

  ---
  💡 แนวทางการปรับปรุง

  1. เพิ่ม/แก้ไขเนื้อหา Email

  ไฟล์: resources/views/emails/purchase-order-approved.blade.php

  ตัวอย่างการแก้ไข:

  <!-- เพิ่มข้อมูลส่วนลดที่เพิ่งเพิ่มเข้ามา -->
  @if($purchaseOrder->discount_amount > 0)
  <div class="detail-row">
      <span class="detail-label">ส่วนลด:</span>
      <span class="detail-value">
          {{ number_format($purchaseOrder->discount_amount, 2) }} บาท
      </span>
  </div>

  @if($purchaseOrder->discount_reason)
  <div class="detail-row">
      <span class="detail-label">เหตุผล:</span>
      <span class="detail-value">{{ $purchaseOrder->discount_reason }}</span>
  </div>
  @endif
  @endif

  ---
  2. แก้ไข PDF Template

  ตัวอย่าง: เพิ่มส่วนลดใน Purchase Template

  ไฟล์: resources/views/pdf/purchase-orders/purchase.blade.php

  <!-- เพิ่มหลังยอดรวมสินค้า -->
  <tr>
      <td colspan="3" class="text-right"><strong>ยอดรวมสินค้า:</strong></td>
      <td class="text-right">
          {{ number_format($purchaseOrder->items->sum('line_total'), 2) }} บาท
      </td>
  </tr>

  @if($purchaseOrder->discount_amount > 0)
  <tr>
      <td colspan="3" class="text-right">
          <strong>ส่วนลด/ปรับราคา:</strong>
          @if($purchaseOrder->discount_reason)
              <br><small>({{ $purchaseOrder->discount_reason }})</small>
          @endif
      </td>
      <td class="text-right">
          -{{ number_format($purchaseOrder->discount_amount, 2) }} บาท
      </td>
  </tr>
  @endif

  <tr>
      <td colspan="3" class="text-right"><strong>ยอดสุทธิ (ก่อน VAT):</strong></td>
      <td class="text-right">
          {{ number_format($purchaseOrder->subtotal, 2) }} บาท
      </td>
  </tr>

  ---
  3. เพิ่ม Template ใหม่

  ถ้าต้องการเพิ่ม template สำหรับ work_type อื่นๆ:

  Step 1: สร้างไฟล์ template ใหม่
  resources/views/pdf/purchase-orders/[new-type].blade.php

  Step 2: แก้ไข PurchaseOrderPdfService.php:74-89
  private function selectTemplate(PurchaseOrder $purchaseOrder): string
  {
      switch ($purchaseOrder->work_type) {
          case 'hire':
              return 'pdf.purchase-orders.hire-sow';
          case 'rent':
              return 'pdf.purchase-orders.rent';
          case 'new_type': // ← เพิ่มใหม่
              return 'pdf.purchase-orders.new-type';
          case 'buy':
          default:
              return 'pdf.purchase-orders.purchase';
      }
  }

  ---
  4. ปรับแต่งข้อมูลบริษัท

  ไฟล์: PurchaseOrderPdfService.php:158-164

  'company' => [
      'name' => 'บริษัท อินโนบิค นูทริชั่น จำกัด',  // ← แก้ไขที่นี่
      'address' => '...',
      'tax_id' => '0123456789012',
      'phone' => '02-111-6289',
      'email' => 'info@innobic.com',
  ],

  หรือ ดึงจาก database/config:

  'company' => [
      'name' => config('company.name'),
      'address' => config('company.address'),
      'tax_id' => config('company.tax_id'),
      'phone' => config('company.phone'),
      'email' => config('company.email'),
  ],

  ---
  5. เปลี่ยนผู้รับ Email

  ไฟล์: SendPurchaseOrderApprovedNotification.php

  เพิ่มผู้รับใหม่:

  // เพิ่มหลังบรรทัด 424
  // ส่งให้ผู้จัดการฝ่ายจัดซื้อ (Procurement Manager)
  $procurementManager = User::role('procurement_manager')->first();

  if ($procurementManager && $procurementManager->email) {
      Mail::to($procurementManager->email)
          ->send(new PurchaseOrderApprovedMail(
              $purchaseOrder,
              $approver,
              $procurementManager,
              $pdfContent,
              $pdfFilename,
              $deliveryNotePdfContent,
              $deliveryNotePdfFilename
          ));
  }

  ---
  6. แก้ไขชื่อไฟล์ PDF

  ไฟล์: PurchaseOrderPdfService.php:176-193

  public function generateFilename(PurchaseOrder $purchaseOrder): string
  {
      $type = match ($purchaseOrder->work_type) {
          'hire' => 'HIRE',
          'rent' => 'RENT',
          'buy' => 'PURCHASE',
          default => 'PURCHASE'
      };

      $cleanPoNumber = str_replace('/', '-', $purchaseOrder->po_number);

      // เปลี่ยนรูปแบบชื่อไฟล์
      return sprintf(
          'PO_%s_%s_%s.pdf',  // ← แก้จาก SOW_ เป็น PO_
          $type,
          $cleanPoNumber,
          now()->format('YmdHis')
      );
  }

  ---
  📝 สรุป

  ✅ ไฟล์ PDF ที่แนบใน Email มี 2 ไฟล์:

  1. SOW PDF - เอกสาร Scope of Work (ตาม work_type: buy/hire/rent)
  2. Delivery Note PDF - ใบส่งของ/ใบตรวจรับ

  ✅ Email ส่งไปยัง 3 กลุ่ม:

  1. Inspection Committee (คณะกรรมการตรวจรับ) - Internal
  2. Vendor (ผู้ขาย) - External
  3. Inspection Committee (อีกครั้ง - backward compatibility)

  ✅ จุดที่สามารถปรับแต่งได้:

  - ✏️ เนื้อหา Email (HTML template)
  - ✏️ รูปแบบ PDF (Blade templates)
  - ✏️ ข้อมูลบริษัท (company info)
  - ✏️ ผู้รับ Email (recipients)
  - ✏️ ชื่อไฟล์ PDF (filename format)

  ---
  พร้อมปรับแต่งในส่วนไหนครับ? บอกมาได้เลย! 🚀