<x-filament-panels::page>
    {{-- ── Setup form ────────────────────────────────────────────── --}}
    <form wire:submit.prevent="save">
        {{ $this->form }}

        <div class="mt-4 flex flex-wrap items-center gap-3">
            <x-filament::button color="gray" icon="heroicon-o-sparkles" wire:click="loadTemplate" type="button">
                สร้างเอกสารจาก Template
            </x-filament::button>

            @if (count($sections))
                <x-filament::button type="submit" icon="heroicon-o-check">
                    💾 บันทึก
                </x-filament::button>
            @endif

            @if ($this->previewUrl)
                <x-filament::button tag="a" href="{{ $this->previewUrl }}" target="_blank" color="info" icon="heroicon-o-eye" type="button">
                    Preview
                </x-filament::button>
                <x-filament::button tag="a" href="{{ $this->pdfUrl }}" target="_blank" color="warning" icon="heroicon-o-document-arrow-down" type="button">
                    ⬇️ PDF
                </x-filament::button>
            @endif

            @if ($torId && $torStatus === 'draft')
                <x-filament::button color="success" icon="heroicon-o-paper-airplane" wire:click="submitTor" type="button"
                                    wire:confirm="ส่ง TOR นี้เข้าสู่การพิจารณา?">
                    📤 ส่งพิจารณา
                </x-filament::button>
            @endif

            @if ($torId)
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    กำลังแก้ไข TOR #{{ $torId }} @if($torStatus) (สถานะ: {{ $torStatus }}) @endif
                </span>
            @endif
        </div>
    </form>

    {{-- ── Document editor ───────────────────────────────────────── --}}
    @if (count($sections))
        @php
            $inputCls = 'w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200';
            $smallCls = 'w-24 rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200';
        @endphp

        <div class="mt-6 space-y-4">
            @foreach ($sections as $si => $section)
                @php $hidden = $section['hidden'] ?? false; @endphp

                <div class="rounded-xl border bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 {{ $hidden ? 'opacity-50' : '' }}">
                    {{-- Section header --}}
                    <div class="mb-3 flex items-center gap-3">
                        @if (!empty($section['number']))
                            <span class="rounded bg-primary-100 px-2 py-1 text-sm font-bold text-primary-700 dark:bg-primary-900 dark:text-primary-300">
                                ข้อ {{ $section['number'] }}
                            </span>
                        @endif
                        <input type="text" wire:model="sections.{{ $si }}.title"
                               class="{{ $inputCls }} font-semibold" style="max-width: 32rem" />
                        <div class="ms-auto">
                            @if (!empty($section['number']))
                                <button type="button" wire:click="toggleSection({{ $si }})"
                                        class="text-xs text-gray-500 underline hover:text-danger-600 dark:text-gray-400">
                                    {{ $hidden ? 'แสดงหัวข้อนี้' : 'ตัดหัวข้อนี้ออก' }}
                                </button>
                            @endif
                        </div>
                    </div>

                    @unless ($hidden)
                        {{-- Body paragraph --}}
                        @if (array_key_exists('body', $section) && $section['body'] !== null)
                            <textarea wire:model="sections.{{ $si }}.body" rows="3" class="{{ $inputCls }} mb-3"></textarea>
                        @endif

                        {{-- ── clause / scope: numbered items ── --}}
                        @if (in_array($section['type'], ['clause', 'scope']) && isset($section['data']['items']))
                            <div class="space-y-2">
                                @foreach ($section['data']['items'] as $ii => $item)
                                    <div class="flex items-start gap-2">
                                        <input type="text" wire:model="sections.{{ $si }}.data.items.{{ $ii }}.no"
                                               class="{{ $smallCls }}" />
                                        <textarea wire:model="sections.{{ $si }}.data.items.{{ $ii }}.text" rows="2"
                                                  placeholder="{{ $section['data']['item_hint'] ?? '' }}"
                                                  class="{{ $inputCls }}"></textarea>
                                        @if (($section['data']['with_quantity'] ?? false))
                                            <input type="text" wire:model="sections.{{ $si }}.data.items.{{ $ii }}.quantity"
                                                   placeholder="จำนวน" class="{{ $smallCls }}" />
                                        @endif
                                        <button type="button" wire:click="addItem({{ $si }}, {{ $ii }})"
                                                class="mt-1 whitespace-nowrap text-xs text-primary-600 hover:underline" title="เพิ่มหัวข้อย่อย">＋ย่อย</button>
                                        <button type="button" wire:click="removeItem({{ $si }}, {{ $ii }})"
                                                class="mt-1 text-xs text-danger-500 hover:underline">ลบ</button>
                                    </div>
                                    {{-- children --}}
                                    @foreach ($item['children'] ?? [] as $ci => $child)
                                        <div class="ms-10 flex items-start gap-2">
                                            <input type="text" wire:model="sections.{{ $si }}.data.items.{{ $ii }}.children.{{ $ci }}.no"
                                                   class="{{ $smallCls }}" />
                                            <textarea wire:model="sections.{{ $si }}.data.items.{{ $ii }}.children.{{ $ci }}.text" rows="2"
                                                      class="{{ $inputCls }}"></textarea>
                                            <button type="button" wire:click="removeItem({{ $si }}, {{ $ii }}, {{ $ci }})"
                                                    class="mt-1 text-xs text-danger-500 hover:underline">ลบ</button>
                                        </div>
                                    @endforeach
                                @endforeach
                                <button type="button" wire:click="addItem({{ $si }})"
                                        class="text-sm text-primary-600 hover:underline">＋ เพิ่มหัวข้อใหม่</button>
                            </div>
                        @endif

                        {{-- ── timeline: เลือก 1 ใน 3 ── --}}
                        @if ($section['type'] === 'timeline')
                            <div class="space-y-3">
                                @foreach ($section['data']['modes'] ?? [] as $mode)
                                    <label class="flex items-center gap-2 text-sm dark:text-gray-200">
                                        <input type="radio" value="{{ $mode['key'] }}"
                                               wire:model.live="sections.{{ $si }}.data.mode"
                                               class="text-primary-600" />
                                        {{ preg_replace('/\{[a-z_]+\}/', '……', $mode['label']) }}
                                    </label>
                                @endforeach

                                @php $tmode = $section['data']['mode'] ?? null; @endphp
                                <div class="ms-6 flex flex-wrap gap-3">
                                    @if ($tmode === 'date_range')
                                        <input type="date" wire:model="sections.{{ $si }}.data.start_date" class="{{ $smallCls }} w-44" />
                                        <span class="self-center text-sm dark:text-gray-300">ถึง</span>
                                        <input type="date" wire:model="sections.{{ $si }}.data.end_date" class="{{ $smallCls }} w-44" />
                                    @elseif ($tmode === 'from_signing')
                                        <span class="self-center text-sm dark:text-gray-300">นับถัดจากวันลงนามสัญญา จนถึงวันที่</span>
                                        <input type="date" wire:model="sections.{{ $si }}.data.until_date" class="{{ $smallCls }} w-44" />
                                    @elseif ($tmode === 'other')
                                        <input type="text" wire:model="sections.{{ $si }}.data.other_text"
                                               placeholder="ระบุระยะเวลาแบบอื่น" class="{{ $inputCls }}" />
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- ── payment: multi-option + งวด รวม 100% ── --}}
                        @if ($section['type'] === 'payment')
                            <div class="space-y-4">
                                @foreach ($section['data']['options'] ?? [] as $oi => $option)
                                    <div class="rounded-lg border p-3 dark:border-gray-700">
                                        <label class="flex items-center gap-2 font-medium text-sm dark:text-gray-200">
                                            <input type="checkbox"
                                                   wire:model.live="sections.{{ $si }}.data.options.{{ $oi }}.enabled"
                                                   class="rounded text-primary-600" />
                                            {{ $option['label'] }}
                                            @if ($option['has_percent'] ?? false)
                                                <input type="number" step="0.01" min="0" max="100"
                                                       wire:model.live="sections.{{ $si }}.data.options.{{ $oi }}.percent"
                                                       class="{{ $smallCls }}" /> <span class="text-sm">เปอร์เซ็นต์</span>
                                            @endif
                                        </label>

                                        @if ($option['enabled'] ?? false)
                                            <textarea wire:model="sections.{{ $si }}.data.options.{{ $oi }}.body" rows="3"
                                                      class="{{ $inputCls }} mt-2"></textarea>

                                            @if (($option['key'] ?? '') === 'installments')
                                                <div class="mt-3 space-y-2">
                                                    @foreach ($option['rows'] ?? [] as $ri => $row)
                                                        <div class="flex items-center gap-2 text-sm dark:text-gray-200">
                                                            <span>งวดที่ {{ $row['no'] }}</span>
                                                            <span>คิดเป็น</span>
                                                            <input type="number" step="0.01" min="0" max="100"
                                                                   wire:model.live="sections.{{ $si }}.data.options.{{ $oi }}.rows.{{ $ri }}.percent"
                                                                   class="{{ $smallCls }}" />
                                                            <span>เปอร์เซ็นต์</span>
                                                            <button type="button" wire:click="removeInstallment({{ $si }}, {{ $ri }})"
                                                                    class="text-xs text-danger-500 hover:underline">ลบ</button>
                                                        </div>
                                                    @endforeach
                                                    <button type="button" wire:click="addInstallment({{ $si }})"
                                                            class="text-sm text-primary-600 hover:underline">＋ เพิ่มงวด</button>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                @endforeach

                                @php $ptotal = $this->paymentTotal; @endphp
                                <div class="text-sm font-semibold {{ abs($ptotal - 100) < 0.01 ? 'text-success-600' : 'text-danger-600' }}">
                                    รวมเป็น {{ rtrim(rtrim(number_format($ptotal, 2), '0'), '.') }} เปอร์เซ็นต์ (ต้องเท่ากับ 100%)
                                </div>

                                <div class="rounded-lg border p-3 dark:border-gray-700">
                                    <div class="mb-1 text-sm font-medium dark:text-gray-200">รายละเอียดการวางบิล</div>
                                    <textarea wire:model="sections.{{ $si }}.data.billing.address" rows="2" class="{{ $inputCls }} mb-2"></textarea>
                                    <div class="flex gap-3">
                                        <input type="text" wire:model="sections.{{ $si }}.data.billing.contact" placeholder="ติดต่อ" class="{{ $inputCls }}" />
                                        <input type="text" wire:model="sections.{{ $si }}.data.billing.phone" placeholder="โทร" class="{{ $inputCls }}" />
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- ── delivery: เอกสารส่งมอบ + กรรมการตรวจรับ ── --}}
                        @if ($section['type'] === 'delivery')
                            <div class="space-y-4">
                                <div>
                                    <div class="mb-1 text-sm font-medium dark:text-gray-200">เอกสารประกอบการส่งมอบงาน (อ้างอิงงวดตามข้อ 7.3 ได้)</div>
                                    <div class="space-y-2">
                                        @foreach ($section['data']['documents'] ?? [] as $ri => $doc)
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm dark:text-gray-300">({{ $ri + 1 }})</span>
                                                <input type="text" wire:model="sections.{{ $si }}.data.documents.{{ $ri }}.name" class="{{ $inputCls }}" />
                                                <input type="text" wire:model="sections.{{ $si }}.data.documents.{{ $ri }}.milestone_ref"
                                                       placeholder="งวดที่" class="{{ $smallCls }}" />
                                                <button type="button" wire:click="removeRow({{ $si }}, 'documents', {{ $ri }})"
                                                        class="text-xs text-danger-500 hover:underline">ลบ</button>
                                            </div>
                                        @endforeach
                                        <button type="button" wire:click="addRow({{ $si }}, 'documents')"
                                                class="text-sm text-primary-600 hover:underline">＋ เพิ่มเอกสาร</button>
                                    </div>
                                </div>

                                @if (isset($section['data']['tolerance_clause']))
                                    <div>
                                        <div class="mb-1 text-sm font-medium dark:text-gray-200">เงื่อนไข Tolerance</div>
                                        <textarea wire:model="sections.{{ $si }}.data.tolerance_clause" rows="3" class="{{ $inputCls }}"></textarea>
                                    </div>
                                @endif

                                <div>
                                    <div class="mb-1 text-sm font-medium dark:text-gray-200">คณะกรรมการตรวจรับ</div>
                                    <div class="space-y-2">
                                        @foreach ($section['data']['committee'] ?? [] as $ri => $member)
                                            <div class="flex items-center gap-2">
                                                <input type="text" wire:model="sections.{{ $si }}.data.committee.{{ $ri }}.name" placeholder="ชื่อ-นามสกุล" class="{{ $inputCls }}" />
                                                <input type="text" wire:model="sections.{{ $si }}.data.committee.{{ $ri }}.phone" placeholder="โทรศัพท์" class="{{ $inputCls }}" />
                                                <input type="email" wire:model="sections.{{ $si }}.data.committee.{{ $ri }}.email" placeholder="E-mail" class="{{ $inputCls }}" />
                                                <button type="button" wire:click="removeRow({{ $si }}, 'committee', {{ $ri }})"
                                                        class="text-xs text-danger-500 hover:underline">ลบ</button>
                                            </div>
                                        @endforeach
                                        <button type="button" wire:click="addRow({{ $si }}, 'committee')"
                                                class="text-sm text-primary-600 hover:underline">＋ เพิ่มกรรมการ</button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endunless
                </div>
            @endforeach

            <div class="flex gap-3">
                <x-filament::button wire:click="save" icon="heroicon-o-check">💾 บันทึก</x-filament::button>
                @if ($this->previewUrl)
                    <x-filament::button tag="a" href="{{ $this->previewUrl }}" target="_blank" color="info" type="button">👁 Preview</x-filament::button>
                    <x-filament::button tag="a" href="{{ $this->pdfUrl }}" target="_blank" color="warning" type="button">⬇️ PDF</x-filament::button>
                @endif
            </div>
        </div>
    @endif
</x-filament-panels::page>
