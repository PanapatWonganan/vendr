<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Scope of Work (ประเภทจ้าง) - {{ $purchaseOrder->po_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'THSarabunNew', 'Sarabun', Arial, sans-serif;
            font-size: 16px;
            line-height: 1.4;
            color: #000;
            background: white;
        }
        
        .container {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;
            padding: 15mm;
            background: white;
        }
        
        .header-code {
            text-align: right;
            font-size: 12px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .title-section {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .work-type {
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .content-block {
            margin-bottom: 15px;
            text-align: justify;
            line-height: 1.6;
        }
        
        .subsection {
            margin-left: 20px;
            margin-bottom: 12px;
        }
        
        .checkbox-item {
            margin-bottom: 8px;
            display: flex;
            align-items: flex-start;
        }
        
        .checkbox {
            width: 15px;
            height: 15px;
            border: 1px solid #000;
            margin-right: 8px;
            margin-top: 2px;
            flex-shrink: 0;
            position: relative;
        }
        
        .checkbox.checked::after {
            content: '☒';
            position: absolute;
            top: -2px;
            left: 1px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .underline-field {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 120px;
            text-align: center;
            padding-bottom: 2px;
            margin: 0 3px;
        }
        
        .contact-section {
            margin-top: 15px;
        }
        
        .contact-row {
            margin-bottom: 8px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .highlight-box {
            background-color: #f5f5f5;
            border: 1px solid #ccc;
            padding: 15px;
            margin: 15px 0;
        }
        
        .billing-address {
            background-color: #f9f9f9;
            padding: 10px;
            margin: 10px 0;
            font-size: 14px;
        }
        
        .penalty-note {
            font-size: 14px;
            color: #333;
            margin-top: 10px;
            text-align: justify;
        }
        
        .footer-line {
            text-align: center;
            margin-top: 40px;
            font-size: 12px;
            color: #666;
        }
        
        .italic-note {
            font-style: italic;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- หน้าที่ 1 -->
        <div class="header-code">{{ $documentCode ?? 'PCM-002' }} (Rev02_010724)</div>
        
        <div class="title-section">
            <div class="work-type">ชื่องาน : <strong>{{ $workTypeLabel ?? 'จ้าง' }}</strong></div>
            <div class="company-name">{{ $company['name'] ?? 'บริษัท อินโนบิค นูทริชั่น จำกัด' }}</div>
        </div>

        <!-- 1. ขอบเขตการดำเนินงาน -->
        <div class="section">
            <div class="section-title">1. ขอบเขตการดำเนินงาน</div>
            <div class="content-block">
                {{ $participantLabel ?? 'ผู้รับจ้าง/ผู้ให้บริการ' }}จะต้องดำเนินการให้{{ $company['name'] ?? 'บริษัท อินโนบิค นูทริชั่น จำกัด' }}ได้ตามขอบการดำเนินงานที่กำหนด (รายละเอียดตามเอกสารแนบ)
            </div>
        </div>

        <!-- 2. การส่งมอบงาน -->
        <div class="section">
            <div class="section-title">2. การส่งมอบงาน</div>
            
            <div class="subsection">
                <strong>2.1 ระยะเวลาดำเนินการ</strong>
                <div class="checkbox-item">
                    <div class="checkbox"></div>
                    <div>ระยะเวลาตั้งแต่ <span class="underline-field">{{ $purchaseOrder->start_date ?? '' }}</span> ถึง <span class="underline-field">{{ $purchaseOrder->end_date ?? '' }}</span></div>
                </div>
                <div class="checkbox-item">
                    <div class="checkbox"></div>
                    <div>ระยะเวลานับถัดจากวันที่ลงนามสัญญาจนถึง <span class="underline-field">{{ $purchaseOrder->delivery_period ?? '' }}</span></div>
                </div>
                <div class="italic-note">*เลือกเพียงอย่างใดอย่างหนึ่ง*</div>
            </div>

            <div class="subsection">
                <strong>2.2 รายละเอียดการส่งมอบงาน ซึ่งมีรายละเอียดดังนี้</strong><br>
                จำนวนงวดทั้งหมด <span class="underline-field">{{ $purchaseOrder->total_phases ?? '' }}</span><br>
                งวดที่ <span class="underline-field">1</span>/ <span class="underline-field">{{ $purchaseOrder->delivery_phases ?? 'ตามข้อ1' }}</span>
            </div>

            <div class="subsection">
                <strong>2.3 เอกสารประกอบการส่งมอบ</strong><br>
                {{ $participantLabel ?? 'ผู้รับจ้าง/ผู้ให้บริการ' }}จะต้องแนบเอกสารประกอบการส่งมอบอื่นๆ อาทิ COA, สบ.5 ., Artwork, Spec, 
                Report,คู่มือการใช้งาน หรือหลักฐานในการส่งมอบ ฯลฯ พร้อมหนังสือส่งมอบงานทุกครั้งหลังจาก
                ส่งมอบงานแล้วเสร็จ ทั้งนี้เอกสารดังกล่าวเป็นส่วนหนึ่งของการส่งมอบงานหาก{{ $participantLabel ?? 'ผู้รับจ้าง/ผู้ให้บริการ' }}
                ส่งมอบไม่ครบถ้วนจะถือว่า การส่งมอบงานในงวดนั้น ไม่สมบูรณ์
            </div>

            <div class="subsection">
                <strong>2.4 สถานที่จัดส่ง :</strong> <span class="underline-field">{{ $purchaseOrder->delivery_location ?? '' }}</span><br>
                <div class="contact-section">
                    <div class="contact-row">ผู้ติดต่อ : <span class="underline-field">{{ $purchaseOrder->contact_person ?? '' }}</span> เบอร์โทรศัพท์: <span class="underline-field">{{ $purchaseOrder->contact_phone ?? '' }}</span></div>
                </div>
            </div>

            <div class="subsection">
                <strong>2.5 ผู้ตรวจรับงาน :</strong> <span class="underline-field">{{ $purchaseOrder->inspectionCommittee->name ?? '' }}</span><br>
                <div class="contact-section">
                    <div class="contact-row">เบอร์โทรศัพท์: <span class="underline-field">{{ $purchaseOrder->inspectionCommittee->phone ?? '' }}</span> E-mail : <span class="underline-field">{{ $purchaseOrder->inspectionCommittee->email ?? '' }}</span></div>
                </div>
                ในการส่งมอบงานทุกงวดงาน{{ $participantLabel ?? 'ผู้รับจ้าง/ผู้ให้บริการ' }}จะต้องส่งมอบงานผ่านผู้รับมอบงานตามรายละเอียดงาน
                ข้างต้น เท่านั้น
            </div>
        </div>

        <!-- 3. เกณฑ์ในการพิจารณา -->
        <div class="section">
            <div class="section-title">3. เกณฑ์ในการพิจารณา</div>
            <div class="checkbox-item">
                <div class="checkbox"></div>
                <div>เกณฑ์ราคา /ผลงาน</div>
            </div>
        </div>

        <!-- Page Break -->
        <div class="page-break"></div>

        <!-- หน้าที่ 2 -->
        <div class="header-code">{{ $documentCode ?? 'PCM-002' }} (Rev02_010724)</div>

        <!-- 4. การชำระเงิน -->
        <div class="section">
            <div class="section-title">4. การชำระเงิน</div>
            
            <div class="checkbox-item">
                <div class="checkbox"></div>
                <div>ชำระเงินหลังจากลงนามในสัญญา <span class="underline-field"></span> %</div>
            </div>
            <div class="content-block" style="margin-left: 25px;">
                โดย{{ $company['name'] ?? 'บริษัท อินโนบิค นูทริชั่น จำกัด' }} จะจ่ายเงินให้{{ $participantLabel ?? 'ผู้รับจ้าง/ผู้ให้บริการ' }} เมื่อครบ 7-10 วัน นับถัดจากวันที่ผู้รับจ้างยื่นหลักฐาน
                การขอรับชำระหนี้ถ้าผู้รับจ้างยื่นหลักฐานการขอรับชำระหนี้เกินกว่าที่กำหนดเป็นระยะเวลาเท่าใด กำหนด
                วันจ่ายเงินจะยืดออกไปเท่ากับวันที่{{ $participantLabel ?? 'ผู้รับจ้าง/ผู้ให้บริการ' }}ยื่นหลักฐานการขอรับชำระหนี้เกินกำหนดเช่นกัน
            </div>

            <div class="checkbox-item">
                <div class="checkbox"></div>
                <div>ชำระเงินหลังส่งมอบงานแล้วเสร็จ <span class="underline-field"></span> %</div>
            </div>
            <div class="content-block" style="margin-left: 25px;">
                โดย{{ $company['name'] ?? 'บริษัท อินโนบิค นูทริชั่น จำกัด' }} จะจ่ายเงินผู้รับจ้าง/{{ $participantLabel ?? 'ผู้ให้บริการ' }} ตามการส่งมอบงานจริงภายใน 30 วัน นับจากวันที่ INNT 
                ได้รับใบเรียกเก็บค่าบริการและได้รับข้อมูลการส่งมอบงานถูกต้องครบถ้วนและ ผ่านการตรวจรับมอบงาน 
                จากคณะกรรมการตรวจรับเป็นที่เรียบร้อยแล้ว
            </div>

            <div class="checkbox-item">
                <div class="checkbox"></div>
                <div>อื่นๆ <span class="underline-field"></span></div>
            </div>

            <div class="billing-address">
                รายละเอียดการวางบิล {{ $company['address'] ?? 'เลขที่ 425/1 อาคาร เอนโก้เทอร์มินอล อาคาร บี ชั้น 7 ถนนกำแพงเพชร 6 แขวงดอนเมือง เขตดอนเมือง กรุงเทพมหานคร 10210' }} ติดต่อฝ่ายบัญชีเบอร์{{ $company['phone'] ?? '02-111-6289' }} (วางบิลได้ทุกวันพุธที่ 2 และ 4 ของทุกเดือน)
            </div>
        </div>

        <!-- 5. อัตราค่าปรับ -->
        <div class="section">
            <div class="section-title">5. อัตราค่าปรับ</div>
            
            <div class="checkbox-item">
                <div class="checkbox"></div>
                <div>กรณีซื้อให้ใช้อัตราค่าปรับร้อยละ0.2 ต่อวันของมูลค่าสินค้าที่ยังไม่ได้รับมอบนับถัดจากวันครบกำหนดส่งมอบสินค้าเป็นต้นไปจนถึงวันที่บริษัทได้รับมอบสินค้าถูกต้องครบถ้วน</div>
            </div>

            <div class="checkbox-item">
                <div class="checkbox checked"></div>
                <div>กรณีจ้าง ให้ใช้อัตราค่าปรับร้อยละ {{ $penaltyRate ?? '0.1' }} ต่อวันของราคาค่าจ้างงานที่บริษัทยังไม่ได้รับมอบนับถัดจากวันครบกำหนดส่งมอบงานเป็นต้น ไป จนถึงว่าที่บริษัทได้รับมอบงานถูกต้องครบถ้วน เว้นแต่งานจ้างที่หวังผลสำเร็จของงานพร้อมกันให้ปรับในอัตราร้อยละ{{ $penaltyRate ?? '0.1' }} ต่อวันของมูลค่าทั้งหมด</div>
            </div>

            <div class="checkbox-item">
                <div class="checkbox"></div>
                <div>กรณีเช่า ให้ใช้อัตราค่าปรับร้อยละ0.2 ของมูลค่าการเช่ารวมทั้งสัญญาหรือมูลค่าสินค้าที่จะเช่าหรือมูลค่าการเช่าในแต่ละงวด</div>
            </div>

            <div class="penalty-note">
                <strong>หมายเหตุ:</strong> การงดปรับหรือลดค่าปรับที่เกิดขึ้นตามสัญญา รวมถึงการขยายระยะเวลาการปฏิบัติ
                ตามสัญญาอันมีผลเป็นการงดปรับหรือลดค่าปรับจะกระทำได้เฉพาะกรณีที่มีเหตุอันสมควร โดยให้คณะกรรมการ
                ตรวจรับสินค้าเป็นผู้เสนอขออนุมัติต่อผู้มีอำนาจอนุมัติให้จัดหาสินค้า (PO/สัญญา) เห็นสมควร
            </div>
        </div>

        <!-- 6. การบอกเลิกสัญญาและผลการเลิกสัญญา -->
        <div class="section">
            <div class="section-title">6. การบอกเลิกสัญญาและผลการเลิกสัญญา</div>
            <div class="content-block">
                INNT ขอสงวนสิทธิในการบอกเลิกสัญญา และเรียกร้องค่าเสียหายอันเกิดจากการที่คู่ค้าละเลยไม่ปฏิบัติ
                ตามเงื่อนไขที่INNT กำหนดไว้
            </div>
            
            <div class="content-block">
                ในกรณีที่ INNT จำเป็นต้องจัดหาบริษัทหรือกลุ่มบุคคลหรือบุคคอื่น เพื่อดำเนินงานส่วนที่เหลือ
                ของโครงการหรืองานเต็มจำนวนหรือเฉพาะจำนวนที่ขาดส่ง หรือจัดหาเพื่อให้การอย่างหนึ่งอย่างใดแล้วแต่กรณี
                เพื่อชดเชยงานที่ขาดส่ง รวมถึงคู่ค้ายินยอมชดใช้ส่วนต่างที่เพิ่มขึ้น หรือชดใช้ค่าเสียหายในส่วนอื่นตามความเห็น
                ของ INNT
            </div>
        </div>

        <div class="footer-line">
            {{ $documentCode ?? 'PCM-002' }} (Rev02_010724)
        </div>
    </div>
</body>
</html>