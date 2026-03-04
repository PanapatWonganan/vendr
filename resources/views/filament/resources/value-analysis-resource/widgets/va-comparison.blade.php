<x-filament-widgets::widget>
    <x-filament::section heading="เปรียบเทียบกับ VA ต้นฉบับ" icon="heroicon-o-arrows-right-left" collapsible>
        @if($parentVa && $changes)
            {{-- Summary --}}
            <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">VA ต้นฉบับ:</span>
                        <span class="font-medium">{{ $parentVa->va_number }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">VA Revision:</span>
                        <span class="font-medium">{{ $currentVa->va_number }} (Rev.{{ $currentVa->revision_number }})</span>
                    </div>
                    <div>
                        <span class="text-gray-500">ประเภทแก้ไข:</span>
                        <span class="font-medium">{{ $currentVa->amendment_type_label }}</span>
                    </div>
                </div>
                @if($currentVa->amendment_reason)
                    <div class="mt-2 text-sm">
                        <span class="text-gray-500">เหตุผล:</span>
                        <span>{{ $currentVa->amendment_reason }}</span>
                    </div>
                @endif
            </div>

            {{-- Amount Comparison --}}
            @if(isset($changes['agreed_amount']))
                <div class="mb-4 p-4 border rounded-lg {{ $changes['agreed_amount']['diff'] > 0 ? 'border-red-200 bg-red-50 dark:bg-red-900/20' : 'border-green-200 bg-green-50 dark:bg-green-900/20' }}">
                    <h4 class="font-medium mb-2">วงเงินรวม</h4>
                    <div class="grid grid-cols-3 gap-4 text-sm">
                        <div>
                            <div class="text-gray-500">เดิม</div>
                            <div class="font-medium line-through text-gray-400">{{ number_format($changes['agreed_amount']['old'], 2) }} THB</div>
                        </div>
                        <div>
                            <div class="text-gray-500">ใหม่</div>
                            <div class="font-bold">{{ number_format($changes['agreed_amount']['new'], 2) }} THB</div>
                        </div>
                        <div>
                            <div class="text-gray-500">ส่วนต่าง</div>
                            <div class="font-bold {{ $changes['agreed_amount']['diff'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                                {{ $changes['agreed_amount']['diff'] > 0 ? '+' : '' }}{{ number_format($changes['agreed_amount']['diff'], 2) }} THB
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Item Changes --}}
            @if(isset($changes['items']))
                <h4 class="font-medium mb-2">รายการที่เปลี่ยนแปลง</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-700">
                                <th class="p-2 text-left border">สถานะ</th>
                                <th class="p-2 text-left border">รายการ</th>
                                <th class="p-2 text-left border">รายละเอียดการเปลี่ยนแปลง</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($changes['items'] as $itemChange)
                                <tr class="border-b">
                                    <td class="p-2 border">
                                        @if($itemChange['type'] === 'added')
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded">เพิ่มใหม่</span>
                                        @elseif($itemChange['type'] === 'removed')
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded">ลบออก</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded">แก้ไข</span>
                                        @endif
                                    </td>
                                    <td class="p-2 border">
                                        @if($itemChange['type'] === 'added')
                                            {{ $itemChange['item']->description ?? '-' }}
                                        @elseif($itemChange['type'] === 'removed')
                                            <span class="line-through text-gray-400">{{ $itemChange['item']->description ?? '-' }}</span>
                                        @else
                                            Line {{ $itemChange['line_number'] }}
                                        @endif
                                    </td>
                                    <td class="p-2 border">
                                        @if($itemChange['type'] === 'changed' && isset($itemChange['diffs']))
                                            @foreach($itemChange['diffs'] as $field => $diff)
                                                <div class="mb-1">
                                                    <span class="text-gray-500">{{ match($field) {
                                                        'agreed_unit_price' => 'ราคาต่อหน่วย',
                                                        'quantity' => 'จำนวน',
                                                        'description' => 'รายละเอียด',
                                                        default => $field
                                                    } }}:</span>
                                                    <span class="line-through text-red-400">{{ is_numeric($diff['old']) ? number_format($diff['old'], 2) : $diff['old'] }}</span>
                                                    <span class="mx-1">&rarr;</span>
                                                    <span class="font-medium text-green-600">{{ is_numeric($diff['new']) ? number_format($diff['new'], 2) : $diff['new'] }}</span>
                                                </div>
                                            @endforeach
                                        @elseif($itemChange['type'] === 'added')
                                            จำนวน: {{ number_format($itemChange['item']->quantity, 2) }} | ราคา: {{ number_format($itemChange['item']->agreed_unit_price, 2) }}
                                        @elseif($itemChange['type'] === 'removed')
                                            <span class="text-gray-400">รายการนี้ถูกลบออก</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @elseif($parentVa)
            <div class="text-center text-gray-500 py-4">
                ไม่มีการเปลี่ยนแปลงจาก VA ต้นฉบับ ({{ $parentVa->va_number }})
            </div>
        @else
            <div class="text-center text-gray-500 py-4">
                VA นี้ไม่ใช่ revision
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
