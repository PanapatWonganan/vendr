{{-- Header ซ้ำทุกหน้า (ตาม header1.xml ของไฟล์ Word ต้นฉบับ) — mPDF-safe: table เท่านั้น --}}
<table width="100%" style="border-collapse: collapse; font-family: freeserif;">
    <tr>
        <td width="14%" style="vertical-align: middle;">
            <img src="{{ public_path('images/innobic-logo.png') }}" width="85">
        </td>
        <td width="51%" style="vertical-align: middle; padding-left: 6px;">
            <span style="font-size: 15pt; font-weight: bold;">{{ $companyTh }}</span><br>
            <span style="font-size: 12pt;">{{ $companyEn }}</span>
        </td>
        <td width="35%" style="vertical-align: middle; text-align: right; font-size: 15pt; font-weight: bold;">
            ข้อกำหนด (Terms of Reference: TOR)
        </td>
    </tr>
</table>

<table width="100%" style="border-collapse: collapse; font-family: freeserif; font-size: 13pt; margin-top: 6px;">
    <tr>
        <td width="12%" style="font-weight: bold;">ชื่อเรื่อง :</td>
        <td width="88%" style="border-bottom: 1px dotted #000; padding-left: 6px;">{{ $tor->title }}</td>
    </tr>
</table>

<table width="100%" style="border-collapse: collapse; font-family: freeserif; font-size: 13pt; margin-top: 4px;">
    <tr>
        <td width="32%" style="font-weight: bold; white-space: nowrap;">ผู้รับผิดชอบกำหนดร่างขอบเขตงาน :</td>
        <td width="34%" style="border-bottom: 1px dotted #000; padding-left: 6px;">{{ $responsible ?: '' }}</td>
        <td width="11%" style="font-weight: bold; text-align: center; white-space: nowrap;">หน่วยงาน</td>
        <td width="23%" style="border-bottom: 1px dotted #000; padding-left: 6px; white-space: nowrap;">{{ $tor->department?->name }}</td>
    </tr>
</table>

<div style="border-bottom: 2px solid #000; margin-top: 6px;"></div>
