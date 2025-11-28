<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Scope of Work (ประเภทซื้อ) - {{ $purchaseOrder->po_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'THSarabunNew', 'Sarabun', Arial, sans-serif;
            font-size: 15px;
            line-height: 1.6;
            color: #2c3e50;
            background: #ffffff;
        }

        .container {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;
            padding: 12mm;
            background: white;
        }

        /* Header Styles */
        .document-header {
            padding: 15px 0;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
        }

        .header-code {
            text-align: right;
            font-size: 12px;
            margin-bottom: 10px;
            color: #000;
        }

        .title-section {
            text-align: center;
            margin-top: 10px;
        }

        .work-type {
            font-size: 16px;
            margin-bottom: 5px;
            font-weight: normal;
        }

        .work-type strong {
            font-weight: bold;
            font-size: 18px;
            text-decoration: underline;
        }

        .company-name {
            font-size: 22px;
            font-weight: bold;
            margin-top: 8px;
        }

        /* Section Styles */
        .section {
            padding: 10px 0;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #000;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #000;
        }

        .content-block {
            margin-bottom: 10px;
            text-align: justify;
            line-height: 1.6;
            color: #000;
            padding: 5px 0;
        }

        .subsection {
            margin-left: 20px;
            margin-bottom: 12px;
            padding: 5px 0;
        }

        .subsection strong {
            color: #000;
            display: block;
            margin-bottom: 5px;
            font-size: 15px;
        }

        /* Checkbox Styles */
        .checkbox-item {
            margin-bottom: 8px;
            margin-left: 10px;
            line-height: 1.6;
            text-align: justify;
        }

        /* Field Styles */
        .underline-field {
            border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 100px;
            text-align: center;
            padding: 0 5px;
            margin: 0 3px;
            font-weight: normal;
            color: #000;
        }

        /* Contact Section */
        .contact-section {
            margin-top: 8px;
            margin-left: 20px;
            padding: 5px 0;
        }

        .contact-row {
            margin-bottom: 5px;
            color: #000;
        }

        /* Warranty Section */
        .warranty-section {
            margin: 10px 0;
        }

        .warranty-subsection {
            margin-bottom: 10px;
            text-align: justify;
            line-height: 1.6;
            padding: 3px 0;
            margin-left: 20px;
        }

        .warranty-subsection strong {
            color: #000;
            margin-right: 5px;
        }

        /* Billing Address */
        .billing-address {
            padding: 10px;
            margin: 10px 0;
            font-size: 14px;
            border: 1px solid #000;
        }

        .billing-address strong {
            color: #000;
        }

        /* Penalty Note */
        .penalty-note {
            font-size: 14px;
            color: #000;
            margin-top: 10px;
            text-align: justify;
            padding: 8px;
            border: 1px solid #000;
        }

        .penalty-note strong {
            color: #000;
        }

        /* Italic Note */
        .italic-note {
            font-style: italic;
            font-size: 13px;
            color: #000;
            margin-top: 5px;
            padding-left: 10px;
        }

        /* Page Break */
        .page-break {
            page-break-before: always;
        }

        /* Footer */
        .footer-line {
            text-align: center;
            margin-top: 25px;
            padding-top: 10px;
            font-size: 11px;
            color: #000;
            border-top: 1px solid #000;
        }

        /* Info Box */
        .info-box {
            border: 1px solid #000;
            padding: 8px;
            margin: 8px 0;
            font-style: italic;
        }

        .info-box strong {
            color: #000;
        }

        /* Warning Box */
        .warning-box {
            border: 1px solid #000;
            padding: 8px;
            margin: 8px 0;
        }

        .warning-box strong {
            color: #000;
        }

        /* Page 2 Header */
        .page-header {
            padding: 10px 0;
            margin: -12mm -12mm 15px 0;
            border-bottom: 2px solid #000;
        }

        .page-header .header-code {
            text-align: right;
            color: #000;
            font-weight: normal;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- หน้าที่ 1 -->
        <div class="document-header">
            <div class="header-code">{{ $documentCode ?? 'PCMN-002-FO' }} (Rev02_010724)</div>
            <div class="title-section">
                <div class="work-type">ชื่องาน : <strong>{{ $workTypeLabel ?? 'ซื้อ' }}</strong></div>
                <div class="company-name">{{ $company['name'] ?? 'บริษัท อินโนบิค นูทริชั่น จำกัด' }}</div>
            </div>
        </div>

        <!-- 1. ขอบเขตการดำเนินงาน -->
        <div class="section">
            <div class="section-title">1. ขอบเขตการดำเนินงาน</div>
            <div class="content-block">
                {{ $participantLabel ?? 'ผู้ขาย' }}จะต้องดำเนินการให้{{ $company['name'] ?? 'บริษัท อินโนบิค นูทริชั่น จำกัด' }}ได้ตามขอบการดำเนินงานที่กำหนด (รายละเอียดตามเอกสารแนบ)
            </div>
        </div>

        <!-- 2. การส่งมอบงาน -->
        <div class="section">
            <div class="section-title">2. การส่งมอบงาน</div>

            <div class="subsection">
                <strong>2.1 ระยะเวลาดำเนินการ</strong> <span class="italic-note" style="display: inline; padding-left: 5px;">*เลือกเพียงอย่างใดอย่างหนึ่ง*</span>
                <div class="checkbox-item">
                    [ ] ระยะเวลาตั้งแต่ <span class="underline-field">{{ $purchaseOrder->start_date ?? '........................' }}</span> ถึง <span class="underline-field">{{ $purchaseOrder->end_date ?? '........................' }}</span>
                </div>
                <div class="checkbox-item">
                    [ ] ระยะเวลานับถัดจากวันที่ลงนามสัญญาจนถึง <span class="underline-field">{{ $purchaseOrder->delivery_period ?? '........................' }}</span>
                </div>
            </div>

            <div class="subsection">
                <strong>2.2 รายละเอียดการส่งมอบงาน</strong>
                จำนวนงวดทั้งหมด <span class="underline-field">{{ $purchaseOrder->total_phases ?? '........................' }}</span><br>
                งวดที่ <span class="underline-field">1</span> / <span class="underline-field">{{ $purchaseOrder->delivery_phases ?? 'ตามข้อ1' }}</span>
            </div>

            <div class="subsection">
                <strong>2.3 เอกสารประกอบการส่งมอบ</strong>
                {{ $participantLabel ?? 'ผู้ขาย' }}จะต้องแนบเอกสารประกอบการส่งมอบอื่นๆ อาทิ COA, สบ.5, Artwork, Spec, Report, คู่มือการใช้งาน หรือหลักฐานในการส่งมอบ ฯลฯ พร้อมหนังสือส่งมอบงานทุกครั้งหลังจากส่งมอบงานแล้วเสร็จ ทั้งนี้เอกสารดังกล่าวเป็นส่วนหนึ่งของการส่งมอบงาน หาก{{ $participantLabel ?? 'ผู้ขาย' }}ส่งมอบไม่ครบถ้วนจะถือว่า การส่งมอบงานในงวดนั้นไม่สมบูรณ์
            </div>

            <div class="subsection">
                <strong>2.4 สถานที่จัดส่ง</strong>
                <span class="underline-field">{{ $purchaseOrder->delivery_location ?? '........................' }}</span>
                <div class="contact-section">
                    <div class="contact-row">ผู้ติดต่อ: <span class="underline-field">{{ $purchaseOrder->contact_person ?? '........................' }}</span></div>
                    <div class="contact-row">เบอร์โทรศัพท์: <span class="underline-field">{{ $purchaseOrder->contact_phone ?? '........................' }}</span></div>
                </div>
            </div>

            <div class="subsection">
                <strong>2.5 ผู้ตรวจรับงาน</strong>
                <span class="underline-field">{{ $purchaseOrder->inspectionCommittee->name ?? '........................' }}</span>
                <div class="contact-section">
                    <div class="contact-row">เบอร์โทรศัพท์: <span class="underline-field">{{ $purchaseOrder->inspectionCommittee->phone ?? '........................' }}</span></div>
                    <div class="contact-row">E-mail: <span class="underline-field">{{ $purchaseOrder->inspectionCommittee->email ?? '........................' }}</span></div>
                </div>
                <div class="info-box" style="margin-top: 10px;">
                    ในการส่งมอบงานทุกงวดงาน {{ $participantLabel ?? 'ผู้ขาย' }}จะต้องส่งมอบงานผ่านผู้รับมอบงานตามรายละเอียดงานข้างต้นเท่านั้น
                </div>
            </div>
        </div>

        <!-- 3. เกณฑ์ในการพิจารณา -->
        <div class="section">
            <div class="section-title">3. เกณฑ์ในการพิจารณา</div>
            <div class="checkbox-item">
                [ ] เกณฑ์ราคา / ผลงาน
            </div>
        </div>

        <!-- Page Break -->
        <div class="page-break"></div>

        <!-- หน้าที่ 2 -->
        <div class="page-header">
            <div class="header-code">{{ $documentCode ?? 'PCMN-002-FO' }} (Rev02_010724)</div>
        </div>

        <!-- 4. การรับประกันสินค้า -->
        <div class="section">
            <div class="section-title">4. การรับประกันสินค้า</div>

            <div class="warranty-section">
                <div class="warranty-subsection">
                    <strong>4.1</strong> สินค้าที่ซื้อจะต้องมีคุณภาพไม่ต่ำกว่าที่กำหนดไว้ตามรายการละเอียดใบเสนอราคาและเอกสารแนบท้าย ซึ่งเป็นของแท้ ของใหม่ และไม่เคยถูกใช้มาก่อน
                </div>

                <div class="warranty-subsection">
                    <strong>4.2</strong> ในกรณีที่เป็นสินค้าที่จะต้องตรวจทดลอง ผู้รับจ้าง/{{ $participantLabel ?? 'ผู้ขาย' }} ยินยอมรับรองว่า เมื่อตรวจทดลองแล้วต้องมีคุณภาพไม่ต่ำกว่าที่กำหนดไว้ด้วย
                </div>

                <div class="warranty-subsection">
                    <strong>4.3</strong> บริษัทฯ สงวนสิทธิ์ที่จะไม่รับมอบถ้าปรากฏว่า สินค้านั้นมีลักษณะชำรุดบกพร่องหรือไม่ตรงตามรายการที่ระบุไว้ตามใบสั่งซื้อสั่งจ้างและเอกสารที่เกี่ยวข้อง กรณีนี้ผู้รับจ้าง/{{ $participantLabel ?? 'ผู้ขาย' }} จะต้องดำเนินการเปลี่ยนใหม่ให้ถูกต้องตามใบสั่งซื้อสั่งจ้างและเอกสารที่เกี่ยวข้องทุกประการ
                </div>

                <div class="warranty-subsection">
                    <strong>4.4</strong> ท่านยินยอมรับประกันความชำรุดบกพร่องหรือขัดข้องของสิ่งของที่สั่งซื้อ หากตรวจพบภายหลังว่าสิ่งของตามใบสั่งซื้อนี้เกิดชำรุดบกพร่องอันเนื่องมาจากการใช้งานตามปกติ ท่านยอมรับจัดการซ่อมแซม เปลี่ยนใหม่ หรือแก้ไขให้อยู่ในสภาพที่ใช้การได้ดีดังเดิมภายใน <span class="underline-field">{{ $purchaseOrder->warranty_days ?? '7' }}</span> วัน นับแต่วันที่ได้รับแจ้งจากทางบริษัทฯ โดยไม่คิดค่าใช้จ่ายใดๆ ทั้งสิ้น
                </div>

                <div class="warranty-subsection">
                    <strong>4.5</strong> ในกรณีสินค้าที่ตรวจรับไปแล้วนั้นมีความชำรุดบกพร่อง {{ $participantLabel ?? 'ผู้ขาย' }} จะต้องยืดระยะเวลาในการรับประกันสินค้าเป็นจำนวน <span class="underline-field">{{ $purchaseOrder->extended_warranty_days ?? '.......................' }}</span> วัน นับตั้งแต่วันที่บริษัทฯ ได้รับมอบสินค้าที่ซ่อมแซม เปลี่ยนใหม่ หรือแก้ไขให้อยู่ในสภาพที่ใช้การได้ดีดังเดิม
                </div>

                <div class="warranty-subsection">
                    <strong>4.6</strong> INNT ขอสงวนสิทธิในการบอกเลิกสัญญาและเรียกร้องค่าเสียหายอันเกิดจากการที่คู่ค้าละเลยไม่ปฏิบัติตามเงื่อนไขที่ INNT กำหนดไว้
                </div>

                <div class="warranty-subsection">
                    <strong>4.7</strong> ในกรณีที่ INNT จำเป็นต้องจัดหากับบริษัทหรือกลุ่มบุคคลหรือบุคคลอื่น เพื่อดำเนินงานส่วนที่เหลือของโครงการหรืองานเต็มจำนวนหรือเฉพาะจำนวนที่ขาดส่ง หรือจัดหาเพื่อให้การอย่างหนึ่งอย่างใดแล้วแต่กรณี เพื่อชดเชยงานที่ขาดส่ง คู่ค้ายินยอมชดใช้ส่วนต่างที่เพิ่มขึ้น หรือชดใช้ค่าเสียหายในส่วนอื่น
                </div>
            </div>
        </div>

        <!-- 5. การชำระเงิน -->
        <div class="section">
            <div class="section-title">5. การชำระเงิน</div>

            <div class="checkbox-item">
                [ ] ชำระเงินหลังจากลงนามในสัญญา <span class="underline-field">................</span> %
            </div>
            <div class="content-block" style="margin-left: 30px;">
                โดยบริษัทฯ จะจ่ายเงินให้{{ $participantLabel ?? 'ผู้ขาย' }} เมื่อครบ 7-10 วัน นับถัดจากวันที่ผู้รับจ้างยื่นหลักฐานการขอรับชำระหนี้ ถ้าผู้รับจ้างยื่นหลักฐานการขอรับชำระหนี้เกินกว่าที่กำหนดเป็นระยะเวลาเท่าใด กำหนดวันจ่ายเงินจะยืดออกไปเท่ากับวันที่{{ $participantLabel ?? 'ผู้ขาย' }}ยื่นหลักฐานการขอรับชำระหนี้เกินกำหนดเช่นกัน
            </div>

            <div class="checkbox-item">
                [ ] ชำระเงินหลังส่งมอบงานแล้วเสร็จ <span class="underline-field">................</span> %
            </div>
            <div class="content-block" style="margin-left: 30px;">
                โดยบริษัทฯ จะจ่ายเงินให้แก่{{ $participantLabel ?? 'ผู้ขาย' }} ตามการส่งมอบงานจริงภายใน 30 วัน นับจากวันที่ INNT ได้รับใบเรียกเก็บค่าบริการและได้รับข้อมูลการส่งมอบงานถูกต้องครบถ้วนและผ่านการตรวจรับมอบงานจากคณะกรรมการตรวจรับเป็นที่เรียบร้อยแล้ว
            </div>

            <div class="checkbox-item">
                [ ] อื่นๆ <span class="underline-field">.......................................................</span>
            </div>

            <div class="billing-address">
                <strong>รายละเอียดการวางบิล</strong><br>
                {{ $company['address'] ?? 'เลขที่ 425/1 อาคาร เอนโก้เทอร์มินอล อาคาร บี ชั้น 7 ถนนกำแพงเพชร 6 แขวงดอนเมือง เขตดอนเมือง กรุงเทพมหานคร 10210' }}<br>
                ติดต่อฝ่ายบัญชี: {{ $company['phone'] ?? '02-111-6289' }}<br>
                <em>(วางบิลได้ทุกวันพุธที่ 2 และ 4 ของทุกเดือน)</em>
            </div>
        </div>

        <!-- 6. อัตราค่าปรับ -->
        <div class="section">
            <div class="section-title">6. อัตราค่าปรับ</div>

            <div class="checkbox-item">
                [{{ $purchaseOrder->work_type == 'buy' ? 'x' : ' ' }}] <strong>กรณีซื้อ:</strong> ให้ใช้อัตราค่าปรับร้อยละ {{ $penaltyRate ?? '0.2' }} ต่อวันของมูลค่าสินค้าที่ยังไม่ได้รับมอบ นับถัดจากวันครบกำหนดส่งมอบสินค้าเป็นต้นไปจนถึงวันที่บริษัทได้รับมอบสินค้าถูกต้องครบถ้วน
            </div>

            <div class="checkbox-item">
                [{{ $purchaseOrder->work_type == 'hire' ? 'x' : ' ' }}] <strong>กรณีจ้าง:</strong> ให้ใช้อัตราค่าปรับร้อยละ 0.1 ต่อวันของราคาค่าจ้างงานที่บริษัทยังไม่ได้รับมอบ นับถัดจากวันครบกำหนดส่งมอบงานเป็นต้นไปจนถึงวันที่บริษัทได้รับมอบงานถูกต้องครบถ้วน เว้นแต่งานจ้างที่หวังผลสำเร็จของงานพร้อมกันให้ปรับในอัตราร้อยละ 0.1 ต่อวันของมูลค่าทั้งหมด
            </div>

            <div class="checkbox-item">
                [{{ $purchaseOrder->work_type == 'rent' ? 'x' : ' ' }}] <strong>กรณีเช่า:</strong> ให้ใช้อัตราค่าปรับร้อยละ 0.2 ของมูลค่าการเช่ารวมทั้งสัญญาหรือมูลค่าสินค้าที่จะเช่าหรือมูลค่าการเช่าในแต่ละงวด
            </div>

            <div class="penalty-note">
                <strong>หมายเหตุ:</strong> การงดปรับหรือลดค่าปรับที่เกิดขึ้นตามสัญญา รวมถึงการขยายระยะเวลาการปฏิบัติตามสัญญาอันมีผลเป็นการงดปรับหรือลดค่าปรับจะกระทำได้เฉพาะกรณีที่มีเหตุอันสมควร โดยให้คณะกรรมการตรวจรับสินค้าเป็นผู้เสนอขออนุมัติต่อผู้มีอำนาจอนุมัติให้จัดหาสินค้า (PO/สัญญา) เห็นสมควร
            </div>
        </div>

        <!-- 7. การบอกเลิกสัญญาและผลการเลิกสัญญา -->
        <div class="section">
            <div class="section-title">7. การบอกเลิกสัญญาและผลการเลิกสัญญา</div>

            <div class="content-block">
                INNT ขอสงวนสิทธิในการบอกเลิกสัญญา และเรียกร้องค่าเสียหายอันเกิดจากการที่คู่ค้าละเลยไม่ปฏิบัติตามเงื่อนไขที่ INNT กำหนดไว้
            </div>

            <div class="content-block">
                ในกรณีที่ INNT จำเป็นต้องจัดหาบริษัทหรือกลุ่มบุคคลหรือบุคคลอื่น เพื่อดำเนินงานส่วนที่เหลือของโครงการหรืองานเต็มจำนวนหรือเฉพาะจำนวนที่ขาดส่ง หรือจัดหาเพื่อให้การอย่างหนึ่งอย่างใดแล้วแต่กรณี เพื่อชดเชยงานที่ขาดส่ง รวมถึงคู่ค้ายินยอมชดใช้ส่วนต่างที่เพิ่มขึ้น หรือชดใช้ค่าเสียหายในส่วนอื่นตามความเห็นของ INNT
            </div>
        </div>

        <div class="footer-line">
            {{ $documentCode ?? 'PCMN-002-FO' }} (Rev02_010724) | Generated: {{ $printDate ?? now()->format('d/m/Y H:i') }}
        </div>
    </div>
</body>
</html>
