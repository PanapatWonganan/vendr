<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>{{ $tor->tor_number }} — ข้อกำหนด (TOR)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    {{-- Sarabun = ฟอนต์ตระกูลเดียวกับ TH Sarabun New ที่ใช้ในไฟล์ Word ต้นฉบับ --}}
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Sarabun', 'TH Sarabun New', sans-serif; font-size: 15px; line-height: 1.55; color: #000; margin: 0; background: #e5e7eb; }
        .page { max-width: 210mm; margin: 1.25rem auto; background: #fff; padding: 12mm 18mm 16mm; box-shadow: 0 1px 6px rgba(0,0,0,.18); position: relative; }

        /* ── Header ตามไฟล์ Word: ตาราง 2 คอลัมน์ ไม่มีเส้นขอบ ── */
        .doc-header { display: flex; align-items: flex-start; gap: 10px; }
        .doc-header .left { flex: 0 0 65%; display: flex; align-items: center; gap: 12px; }
        .doc-header .left img { height: 46px; }
        .doc-header .company .th { font-weight: 700; font-size: 16px; }
        .doc-header .company .en { font-size: 14px; }
        .doc-header .right { flex: 1; text-align: right; font-weight: 700; font-size: 17px; padding-top: 6px; white-space: nowrap; }

        .meta-line { margin-top: 10px; font-size: 15px; }
        .meta-line .lbl { font-weight: 600; }
        .dotted { display: inline-block; border-bottom: 1px dotted #000; min-width: 8rem; padding: 0 .5rem; }
        .meta-sep { border: 0; border-top: 1.5px solid #000; margin: 10px 0 14px; }

        /* ── ชื่อเรื่องกลางหน้า + เนื้อหา ── */
        h1.doc-title { text-align: center; font-size: 16px; font-weight: 700; margin: 4px 0 10px; }
        .preamble { text-indent: 2.5em; margin: 0 0 8px; }
        h2.section { font-size: 15px; font-weight: 700; margin: 10px 0 2px; }
        h2.section .no { display: inline-block; min-width: 1.6em; }
        .body-text { white-space: pre-line; text-indent: 2.5em; margin: 2px 0 4px; }
        .item { margin-left: 2.7em; text-indent: 0; }
        .item .no { font-weight: 500; margin-right: .6em; }
        .child { margin-left: 5.2em; }
        .qty { margin-left: 1em; }
        ol.docs { margin: 2px 0 4px 4.2em; padding: 0; }
        ol.docs li { margin: 1px 0; }
        .committee { margin-left: 4em; }

        /* ── Footer: รหัสแบบฟอร์ม (ตาม footer ของไฟล์ Word) ── */
        .form-code { margin-top: 18px; text-align: right; font-size: 12px; color: #444; }

        .toolbar { max-width: 210mm; margin: 1rem auto 0; display: flex; gap: 8px; }
        .toolbar a, .toolbar button { font-family: inherit; font-size: 13px; padding: 6px 14px; border-radius: 6px; border: 1px solid #d1d5db; background: #fff; cursor: pointer; text-decoration: none; color: #111; }

        @media print {
            body { background: #fff; }
            .page { box-shadow: none; margin: 0; max-width: none; padding: 6mm 4mm; }
            .toolbar { display: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">🖨 Print / Save PDF</button>
        <a href="{{ route('tor-builder.pdf', $tor) }}" target="_blank">⬇️ ดาวน์โหลด PDF</a>
        <a href="{{ url('/admin/tor-builder?tor='.$tor->id) }}">✏️ กลับไปแก้ไข</a>
    </div>

    @php
        $preamble = collect($sections)->firstWhere('key', 'preamble');
        $companyLine = trim(str_replace(['เงื่อนไขข้อกำหนด', '(TOR)'], '', $preamble['title'] ?? ''));
        $companyTh = $companyLine !== '' ? $companyLine : 'บริษัท อินโนบิก นูทริชั่น จำกัด';
        $companyEn = [
            'บริษัท อินโนบิก นูทริชั่น จำกัด' => 'Innobic Nutrition Company Limited',
            'บริษัท อินโนบิก (เอเซีย) จำกัด' => 'Innobic (Asia) Company Limited',
            'บริษัท อินโนบิก แอลแอล จำกัด' => 'Innobic LL Company Limited',
        ][$companyTh] ?? 'Innobic Company Limited';
        $responsible = $preamble['data']['responsible_name'] ?? null;
    @endphp

    <div class="page">
        {{-- ── Header (ตาม header1.xml ของไฟล์ต้นฉบับ) ── --}}
        <div class="doc-header">
            <div class="left">
                <img src="{{ asset('images/innobic-logo.png') }}" alt="Innobic">
                <div class="company">
                    <div class="th">{{ $companyTh }}</div>
                    <div class="en">{{ $companyEn }}</div>
                </div>
            </div>
            <div class="right">ข้อกำหนด (Terms of Reference: TOR)</div>
        </div>

        <div class="meta-line">
            <span class="lbl">ชื่อเรื่อง :</span>
            <span class="dotted" style="min-width: 70%">{{ $tor->title }}</span>
        </div>
        <div class="meta-line">
            <span class="lbl">ผู้รับผิดชอบกำหนดร่างขอบเขตงาน :</span>
            <span class="dotted">{{ $responsible ?: '' }}</span>
            <span class="lbl" style="margin-left:1rem">หน่วยงาน</span>
            <span class="dotted">{{ $tor->department?->name }}</span>
        </div>
        <hr class="meta-sep">

        {{-- ── ชื่อเรื่องเอกสาร + เนื้อหา ── --}}
        <h1 class="doc-title">เงื่อนไขข้อกำหนด{{ $companyTh }} (TOR)</h1>

        @foreach ($sections as $section)
            @php $type = $section['type'] ?? 'clause'; $data = $section['data'] ?? []; @endphp

            @if (($section['key'] ?? '') === 'preamble')
                @if (!empty($section['body']))
                    <p class="preamble">{{ $section['body'] }}</p>
                @endif
                @continue
            @endif

            <h2 class="section"><span class="no">{{ $section['render_number'] ?? $section['number'] }}.</span>{{ $section['title'] }}</h2>

            {{-- clause / scope --}}
            @if (in_array($type, ['clause', 'scope']))
                @if (!empty($section['body']))
                    <p class="body-text">{{ $section['body'] }}</p>
                @endif
                @foreach ($data['items'] ?? [] as $item)
                    @if (trim($item['text'] ?? '') !== '' || !empty($item['children']))
                        <div class="item">
                            <span class="no">{{ $item['no'] }}</span>{{ $item['text'] }}
                            @if (($data['with_quantity'] ?? false) && trim($item['quantity'] ?? '') !== '')
                                <span class="qty">จำนวน {{ $item['quantity'] }}</span>
                            @endif
                        </div>
                        @foreach ($item['children'] ?? [] as $child)
                            @if (trim($child['text'] ?? '') !== '')
                                <div class="child"><span class="no">{{ $child['no'] }}</span>{{ $child['text'] }}</div>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            @endif

            {{-- timeline: แสดงเฉพาะ mode ที่เลือก --}}
            @if ($type === 'timeline')
                @php $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('d/m/Y') : '……………………'; @endphp
                <div class="item">
                    @if (($data['mode'] ?? '') === 'date_range')
                        ระยะเวลาดำเนินงานเริ่มวันที่ {{ $fmt($data['start_date'] ?? null) }} ถึงวันที่ {{ $fmt($data['end_date'] ?? null) }}
                    @elseif (($data['mode'] ?? '') === 'from_signing')
                        ระยะเวลาดำเนินงานนับถัดจากวันที่ลงนามสัญญาจนถึงวันที่ {{ $fmt($data['until_date'] ?? null) }}
                    @elseif (($data['mode'] ?? '') === 'other')
                        {{ $data['other_text'] ?? '' }}
                    @else
                        <em>— ยังไม่ได้เลือกรูปแบบระยะเวลา —</em>
                    @endif
                </div>
            @endif

            {{-- payment: แสดงเฉพาะ option ที่เลือก --}}
            @if ($type === 'payment')
                @php $sub = 0; @endphp
                @foreach ($data['options'] ?? [] as $option)
                    @continue(!($option['enabled'] ?? false))
                    @php $sub++; $num = ($section['render_number'] ?? $section['number']); @endphp
                    <div class="item">
                        <span class="no">{{ $num }}.{{ $sub }}</span>{{ $option['label'] }}
                        @if (($option['has_percent'] ?? false) && ($option['percent'] ?? null) !== null)
                            &nbsp;<strong>{{ rtrim(rtrim(number_format((float) $option['percent'], 2), '0'), '.') }} เปอร์เซ็นต์</strong>
                        @endif
                    </div>
                    @if (!empty($option['body']))
                        <p class="body-text">{{ $option['body'] }}</p>
                    @endif
                    @if (($option['key'] ?? '') === 'installments' && !empty($option['rows']))
                        <div class="item">ซึ่งมีจำนวนทั้งหมด {{ count($option['rows']) }} งวด</div>
                        @foreach ($option['rows'] as $row)
                            <div class="child">งวดที่ {{ $row['no'] }} คิดเป็น {{ rtrim(rtrim(number_format((float) ($row['percent'] ?? 0), 2), '0'), '.') }} เปอร์เซ็นต์</div>
                        @endforeach
                        <div class="item"><strong>รวมเป็น 100 เปอร์เซ็นต์</strong></div>
                    @endif
                @endforeach
                @if (!empty($data['billing']['address']))
                    <div class="item" style="margin-top:6px"><strong>รายละเอียดการวางบิล</strong></div>
                    <p class="body-text">{{ $data['billing']['address'] }}</p>
                    @if (!empty($data['billing']['contact']) || !empty($data['billing']['phone']))
                        <div class="item">ติดต่อ : {{ $data['billing']['contact'] ?? '' }} @if(!empty($data['billing']['phone'])) โทร : {{ $data['billing']['phone'] }} @endif</div>
                    @endif
                @endif
            @endif

            {{-- delivery --}}
            @if ($type === 'delivery')
                @if (!empty($section['body']))
                    <p class="body-text">{{ $section['body'] }}</p>
                @endif
                @php $docs = collect($data['documents'] ?? [])->filter(fn ($d) => trim($d['name'] ?? '') !== ''); @endphp
                @if ($docs->isNotEmpty())
                    <div class="item"><strong>เอกสารประกอบการส่งมอบงาน</strong></div>
                    <ol class="docs">
                        @foreach ($docs as $doc)
                            <li>{{ $doc['name'] }}@if(trim($doc['milestone_ref'] ?? '') !== '') (อ้างอิงตามข้อ 7.3 งวดที่ {{ $doc['milestone_ref'] }})@endif</li>
                        @endforeach
                    </ol>
                @endif
                @if (!empty($data['tolerance_clause']))
                    <p class="body-text">{{ $data['tolerance_clause'] }}</p>
                @endif
                @php $committee = collect($data['committee'] ?? [])->filter(fn ($m) => trim($m['name'] ?? '') !== ''); @endphp
                @if ($committee->isNotEmpty())
                    <div class="item"><strong>คณะกรรมการตรวจรับ</strong></div>
                    @foreach ($committee as $member)
                        <div class="committee">
                            {{ $loop->iteration }}. คุณ{{ $member['name'] }}
                            @if(trim($member['phone'] ?? '') !== '') หมายเลขโทรศัพท์: {{ $member['phone'] }} @endif
                            @if(trim($member['email'] ?? '') !== '') E-mail: {{ $member['email'] }} @endif
                        </div>
                    @endforeach
                @endif
            @endif
        @endforeach

        <div class="form-code">PCM A-002-FO (Rev03)</div>
    </div>
</body>
</html>
