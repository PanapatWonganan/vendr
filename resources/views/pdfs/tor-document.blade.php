<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<style>
    body { font-family: freeserif; font-size: 14pt; line-height: 1.45; color: #000; }
    h1.doc-title { text-align: center; font-size: 15pt; font-weight: bold; margin: 0 0 8px; }
    .preamble { text-indent: 2.5em; margin: 0 0 6px; }
    h2.section { font-size: 14pt; font-weight: bold; margin: 8px 0 2px; }
    .body-text { text-indent: 2.5em; margin: 2px 0 4px; }
    .item { margin-left: 34px; margin-bottom: 1px; }
    .no { font-weight: normal; padding-right: 8px; }
    .child { margin-left: 62px; margin-bottom: 1px; }
    ol.docs { margin: 2px 0 4px 52px; padding: 0; }
    .committee { margin-left: 50px; }
    .form-note { font-size: 11pt; color: #444; }
</style>
</head>
<body>
    <h1 class="doc-title">เงื่อนไขข้อกำหนด{{ $companyTh }} (TOR)</h1>

    @foreach ($sections as $section)
        @php $type = $section['type'] ?? 'clause'; $data = $section['data'] ?? []; @endphp

        @if (($section['key'] ?? '') === 'preamble')
            @if (!empty($section['body']))
                <p class="preamble">{{ $section['body'] }}</p>
            @endif
            @continue
        @endif

        <h2 class="section">{{ $section['render_number'] ?? $section['number'] }}. {{ $section['title'] }}</h2>

        {{-- clause / scope --}}
        @if (in_array($type, ['clause', 'scope']))
            @if (!empty($section['body']))
                @foreach (preg_split('/\n{2,}/', $section['body']) as $paragraph)
                    <p class="body-text">{{ trim($paragraph) }}</p>
                @endforeach
            @endif
            @foreach ($data['items'] ?? [] as $item)
                @if (trim($item['text'] ?? '') !== '' || !empty($item['children']))
                    <div class="item">
                        <span class="no">{{ $item['no'] }}</span>&nbsp; {{ $item['text'] }}
                        @if (($data['with_quantity'] ?? false) && trim($item['quantity'] ?? '') !== '')
                            &nbsp;&nbsp;จำนวน {{ $item['quantity'] }}
                        @endif
                    </div>
                    @foreach ($item['children'] ?? [] as $child)
                        @if (trim($child['text'] ?? '') !== '')
                            <div class="child"><span class="no">{{ $child['no'] }}</span>&nbsp; {{ $child['text'] }}</div>
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
                @endif
            </div>
        @endif

        {{-- payment: แสดงเฉพาะ option ที่เลือก --}}
        @if ($type === 'payment')
            @php
                $sub = 0;
                $pct = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
            @endphp
            @foreach ($data['options'] ?? [] as $option)
                @continue(!($option['enabled'] ?? false))
                @php $sub++; $num = ($section['render_number'] ?? $section['number']); @endphp
                <div class="item">
                    <span class="no">{{ $num }}.{{ $sub }}</span>&nbsp; {{ $option['label'] }}
                    @if (($option['has_percent'] ?? false) && ($option['percent'] ?? null) !== null)
                        &nbsp;<b>{{ $pct($option['percent']) }} เปอร์เซ็นต์</b>
                    @endif
                </div>
                @if (!empty($option['body']))
                    <p class="body-text">{{ $option['body'] }}</p>
                @endif
                @if (($option['key'] ?? '') === 'installments' && !empty($option['rows']))
                    <div class="item">ซึ่งมีจำนวนทั้งหมด {{ count($option['rows']) }} งวด</div>
                    @foreach ($option['rows'] as $row)
                        <div class="child">งวดที่ {{ $row['no'] }} คิดเป็น {{ $pct($row['percent'] ?? 0) }} เปอร์เซ็นต์</div>
                    @endforeach
                    <div class="item"><b>รวมเป็น 100 เปอร์เซ็นต์</b></div>
                @endif
            @endforeach
            @if (!empty($data['billing']['address']))
                <div class="item" style="margin-top: 4px;"><b>รายละเอียดการวางบิล</b></div>
                <p class="body-text">{{ $data['billing']['address'] }}</p>
                @if (!empty($data['billing']['contact']) || !empty($data['billing']['phone']))
                    <div class="item">ติดต่อ : {{ $data['billing']['contact'] ?? '' }}@if(!empty($data['billing']['phone']))&nbsp;&nbsp;โทร : {{ $data['billing']['phone'] }}@endif</div>
                @endif
            @endif
        @endif

        {{-- delivery --}}
        @if ($type === 'delivery')
            @if (!empty($section['body']))
                @foreach (preg_split('/\n{2,}/', $section['body']) as $paragraph)
                    <p class="body-text">{{ trim($paragraph) }}</p>
                @endforeach
            @endif
            @php $docs = collect($data['documents'] ?? [])->filter(fn ($d) => trim($d['name'] ?? '') !== ''); @endphp
            @if ($docs->isNotEmpty())
                <div class="item"><b>เอกสารประกอบการส่งมอบงาน</b></div>
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
                <div class="item"><b>คณะกรรมการตรวจรับ</b></div>
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
</body>
</html>
