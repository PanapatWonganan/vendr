# TOR Document Builder — Schema Design (Draft)

> ร่างตาม Workflow.docx + template 5 ไฟล์ของลูกค้า (2026-08) — ยังไม่รัน migration
> ไฟล์ที่เกี่ยวข้อง:
> - `database/migrations/2026_08_20_100000_create_tor_templates_tables.php`
> - `database/migrations/2026_08_20_100001_add_document_builder_to_terms_of_references.php`
> - `app/Models/TorTemplate.php`, `app/Models/TorTemplateSection.php`
> - `database/seeders/TorTemplateSeeder.php` (clause จริงจากไฟล์ลูกค้า)

## แนวคิด

จากการเทียบ template ทั้ง 5 ไฟล์: เนื้อหา ~90% เป็น boilerplate เดียวกัน ต่างแค่ **คำเรียกคู่ค้า**
(ผู้ขาย/ผู้รับจ้าง/ผู้ให้เช่า), **อัตราค่าปรับ** (0.2 vs 0.1), **ชุด+ลำดับหัวข้อ** และ **default
เฉพาะจุด** (ข้อ 5 ของจ้างผลิต, checklist เอกสารข้อ 8.1.1) จึงออกแบบเป็น:

1. **Clause library** (`tor_templates` + `tor_template_sections`) — เก็บข้อความกลางครั้งเดียว
   ใช้ placeholder แล้วให้แต่ละประเภทประกาศชุดหัวข้อของตัวเอง
2. **Document snapshot** (`terms_of_references.document_sections` JSON) — ตอนสร้าง TOR
   ระบบ resolve placeholder + copy default มาเป็นของ TOR ใบนั้น user แก้อะไรก็แก้บน snapshot
   (template เปลี่ยนทีหลังไม่กระทบ TOR เดิม — เหมือน convertToPrData ที่ copy ค่า)

## Placeholders

| Placeholder | ค่า | มาจาก |
|---|---|---|
| `{{party}}` | ผู้ขาย / ผู้รับจ้าง / ผู้ให้เช่า | `tor_templates.party_term` |
| `{{company_full}}` | บริษัท อินโนบิก นูทริชั่น จำกัด ฯลฯ | บริษัทที่เลือกตอนสร้าง (INNT/INBA/INBL) |
| `{{company_short}}` | INNT / INBA / INBL | บริษัทที่เลือก |
| `{{penalty_rate}}` | 0.2 / 0.1 | `tor_templates.penalty_rate` |
| `{{penalty_base}}` | มูลค่าสินค้า / มูลค่าค่าจ้าง | `tor_templates.penalty_base` |

Resolve ที่ `TorTemplate::buildDocumentSections($context)` ตอนสร้าง TOR (ไม่ resolve ตอน render
เพื่อให้ user แก้ข้อความหลัง resolve ได้อิสระ)

## ชุดหัวข้อต่อประเภท (จากไฟล์จริง)

| section_key | ซื้อ (ทั่วไป/Inventory) | จ้างบริการ (ทั่วไป/Bidding) | จ้างผลิต Inventory | เช่า |
|---|---|---|---|---|
| preamble | ✓ | ✓ | ✓ | ✓ |
| definitions | 1 | 1 | 1 | 1 |
| bidder_qualifications | 2 | 2 | 2 | 2 |
| proposal_documents | 3 | 3 | 3 | 3 |
| evaluation_criteria | 4 | 4 | 4 | 4 |
| scope_of_work | 5 (มีช่องจำนวน) | 5 | 5 (โครง 4 หมวดผลิต) | 5 |
| timeline | 6 | 6 | 6 | 6 |
| payment | 7 | 7 | 7 | 7 |
| delivery | 8 (Spec+คู่มือ) | 8 (เอกสารตามที่กำหนด) | 8 (7 รายการ + Tolerance ±5%) | 8 (ว่าง) |
| warranty | 9 (สินค้า) | 9 (สินค้า) | 9 (สินค้า) | 11 (การเช่า, ว่าง) |
| penalty | 10 (0.2%) | 10 (0.1%) | 10 (0.1%) | 9 (0.2%) |
| confidentiality | — | 11 | 11 | — |
| termination | 11 | 12 | 12 | 10 |
| tax_duty | 12 | — | — | 12 |
| contract_security | 13 | — | — | 13 |

## JSON: `terms_of_references.document_sections`

Array ตามลำดับ render แต่ละ element:

```jsonc
{
  "key": "definitions",        // section_key จาก template
  "number": "1",               // เลขข้อ default (renderer คำนวณใหม่ถ้ามี hidden)
  "title": "คำนิยาม",           // แก้ได้
  "type": "clause",            // clause | scope | timeline | payment | delivery
  "hidden": false,             // "หากงานดังกล่าวไม่ใช่งานงวดให้ตัดออก" → ซ่อนได้ + renumber
  "body": "…",                 // ย่อหน้า (resolve placeholder แล้ว, แก้ได้)
  "data": { }                  // โครงสร้างตาม type (ด้านล่าง)
}
```

### type: `clause` — `data.items`
ข้อย่อยมีเลข ("เพิ่มหัวข้อใหม่" = push item / child ที่ตำแหน่งใดก็ได้ แล้ว renumber):
```jsonc
"data": {"items": [
  {"no": "1.1", "text": "…"},
  {"no": "1.2", "text": "…", "children": [{"no": "1.2.1", "text": "…"}]}
]}
```

### type: `scope` — ข้อ 5
```jsonc
"data": {
  "with_quantity": true,       // ประเภทซื้อ: แสดงช่อง "จำนวน" ต่อรายการ
  "items": [{"no": "5.1", "text": "…", "quantity": "2", "children": []}]
}
```

### type: `timeline` — ข้อ 6 (เลือก 1 mode, PDF แสดงเฉพาะที่เลือก)
```jsonc
"data": {
  "mode": "date_range",        // date_range | from_signing | other
  "start_date": "2026-01-01", "end_date": "2026-12-31",
  "until_date": null, "other_text": null
}
```

### type: `payment` — ข้อ 7 (เลือกได้หลาย option, ระบบ validate ผลรวม % = 100)
```jsonc
"data": {
  "options": {
    "after_signing":    {"enabled": true,  "percent": 25, "body": "…"},
    "after_completion": {"enabled": false, "percent": null, "body": "…"},
    "installments":     {"enabled": true,  "body": "…", "total": 2,
                         "rows": [{"no": 1, "percent": 50}, {"no": 2, "percent": 25}]}
  },
  "billing": {"address": "…", "contact": "", "phone": ""}
}
// validation: sum(enabled percent) + sum(installments.rows.percent) == 100
```

### type: `delivery` — ข้อ 8
```jsonc
"data": {
  "total_installments": 4,
  "documents": [{"name": "Spec", "milestone_ref": "1,3"}],   // 8.1.1 อ้างงวดข้อ 7.3
  "tolerance_clause": "…",                                   // จ้างผลิตเท่านั้น (8.1.3)
  "committee": [{"name": "…", "phone": "…", "email": "…"}]   // 8.3 เพิ่มได้ไม่จำกัด
}
```

## Flow การใช้งาน

1. User เลือก บริษัท + แบบฟอร์ม + ประเภท + วิธี → ระบบหา `TorTemplate` จาก `code`
2. `buildDocumentSections()` → resolve placeholder → เซฟลง `document_sections` พร้อม
   `tor_template_id`, `procurement_type`, `party_term`
3. Editor แก้/เพิ่มข้อย่อย/ซ่อน section บน JSON | Copy TOR เก่า = copy `document_sections` ทั้งก้อน
   (ต่อยอด pattern `parent_tor_id` เดิม)
4. PDF/Word render จาก JSON — แสดงเฉพาะ option/mode ที่เลือก, renumber section ที่ hidden

## Audit comment/ตัวแดงของลูกค้าในไฟล์ template (เช็คครบ 2026-08-20)

| Comment (Khanitha) | ตำแหน่ง | รองรับโดย |
|---|---|---|
| เลือกอย่างใดอย่างหนึ่ง แล้วลบข้อที่ไม่ใช่ออก | ข้อ 6 | timeline 1-of-3 mode, PDF แสดงเฉพาะที่เลือก |
| สามารถเปลี่ยนไปตามงานฯ (จำนวนวันจ่าย …./30 วัน) | ข้อ 7 | body แก้ไขได้ |
| ใส่ชื่อคนที่รับวางบิล | ท้ายข้อ 7 | billing.contact / billing.phone |
| หากงานไม่ใช่งานงวดให้ตัดออก | ข้อ 8.1 | body แก้ได้ + hide section ได้ |
| สามารถปรับเปลี่ยนไปตามงานได้ (checklist / Tolerance) | ข้อ 8.1.1, 8.1.3 | documents repeater + tolerance แก้ได้ |
| สามารถปรับเปลี่ยนตามงานของตัวเอง (โครง 5.1–5.4) | จ้างผลิต ข้อ 5 | scope items แก้/เพิ่ม/ลบได้ |
| ใส่ชื่อตาม Material* | ซื้อ ข้อ 5.1–5.5 | `config.item_hint` = "ใส่ชื่อสินค้าตาม Material" (placeholder ในช่องกรอก) |
| ตัวแดง "และ ISO ที่กำหนด" ×4 | จ้างผลิต 5.1.1/5.2.1/5.3.1/5.4.2 | อยู่ใน default text (แก้/ลบได้) — สีแดงต้นฉบับหมายถึงส่วนที่ปรับตามงาน |

## ประเด็นค้าง confirm ลูกค้า

1. จ้างบริการ Bidding ใช้เนื้อหาเดียวกับจ้างบริการทั่วไป (diff กันแค่ชื่อบริษัทในหัวเอกสาร ซึ่งน่าจะ
   เป็น copy-paste พลาด) → seed ให้ใช้ชุดเดียวกันไปก่อน
2. ซื้อทั่วไป vs ซื้อ Inventory: template รวมไฟล์เดียว → seed แยก 2 code แต่เนื้อหาเดียวกัน
   รอลูกค้าระบุความต่าง
3. ประเภทจ้าง ไม่มีข้อภาษี/หลักประกันสัญญา (จบที่บอกเลิกสัญญา) — ตกหล่นหรือตั้งใจ?
4. Typo ในต้นฉบับที่ seed แก้ให้แล้ว: ข้อ 1.3 คู่ค้า (find-replace พัง), เลขข้อ 2 (พิมพ์เป็น 1.1–1.4),
   "บรรลุภาวะ" → "บรรลุนิติภาวะ"
5. วิธีจัดหา 6 แบบใหม่ (เฉพาะเจาะจง/ประกาศเชิญชวนทั่วไป) ผูกกับแบบฟอร์ม (เชิงพาณิชย์ vs พ.ร.บ.)
   หรือเลือกอิสระ? ยังไม่แตะ enum `procurement_method` จนกว่าจะ confirm

## สิ่งที่ยังไม่ทำ (รอ phase ถัดไป)

- Filament UI (editor + ปุ่มเพิ่มหัวข้อ/ซ่อน section) และการ map ลง Wizard 5 step เดิม
- Renderer PDF (mPDF) + export Word
- Migration ข้อมูล TOR เดิม (คอลัมน์ flat เดิมยังอยู่ครบ ไม่พัง backward compat)
- ผูก `procurement_type` ใหม่กับ `work_type`/PR flow
