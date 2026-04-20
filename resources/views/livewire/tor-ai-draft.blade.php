<div
    x-data="{
        syncFromParent() {
            // Read form state from the parent Livewire component (Filament page)
            const parent = Livewire.all().find(c => c.snapshot && c.snapshot.data && c.snapshot.data.data)
            if (!parent) return

            const data = JSON.parse(parent.snapshot.data.data)
            if (data) {
                if (data.title) $wire.set('title', data.title)
                if (data.tor_type) $wire.set('torType', data.tor_type)
                if (data.work_type) $wire.set('workType', data.work_type)
                if (data.procurement_method) $wire.set('procurementMethod', data.procurement_method)
                if (data.budget_estimate) $wire.set('budgetEstimate', parseFloat(data.budget_estimate))
                if (data.category) $wire.set('category', data.category)
            }

            // Try to get department name from select text
            const deptSelect = document.querySelector('[wire\\:model\\.live=data\\.department_id], select[name=data\\.department_id]')
            if (deptSelect && deptSelect.selectedOptions && deptSelect.selectedOptions[0]) {
                $wire.set('department', deptSelect.selectedOptions[0].text)
            }
        }
    }"
    x-init="$nextTick(() => syncFromParent())"
    class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700"
>
    <div class="flex items-center gap-2 mb-3">
        <x-heroicon-o-sparkles class="w-5 h-5 text-blue-500" />
        <h3 class="text-sm font-semibold text-blue-700 dark:text-blue-300">AI ช่วยร่าง TOR</h3>
    </div>

    @if(!$result)
        <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">
            กรอกข้อมูลพื้นฐานในขั้นตอนที่ 1 แล้วกดปุ่มด้านล่างเพื่อให้ AI ช่วยร่างเนื้อหา TOR
        </p>

        <button
            x-on:click="syncFromParent()"
            wire:click="generateDraft"
            wire:loading.attr="disabled"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
        >
            <span wire:loading.remove wire:target="generateDraft">
                <x-heroicon-o-sparkles class="w-4 h-4" />
            </span>
            <span wire:loading wire:target="generateDraft">
                <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </span>
            <span wire:loading.remove wire:target="generateDraft">สร้าง TOR ด้วย AI</span>
            <span wire:loading wire:target="generateDraft">กำลังสร้าง... (อาจใช้เวลา 30-60 วินาที)</span>
        </button>
    @endif

    @if($errorMessage)
        <div class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
            <p class="text-sm text-red-600 dark:text-red-400">{{ $errorMessage }}</p>
        </div>
    @endif

    @if($result)
        <div class="mt-3 space-y-3">
            <div class="flex items-center justify-between">
                <p class="text-sm text-green-600 dark:text-green-400 font-medium">AI สร้างเนื้อหาเรียบร้อยแล้ว</p>
                <div class="flex gap-2">
                    <button
                        wire:click="applyAll"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition"
                    >
                        <x-heroicon-o-check class="w-3 h-3" />
                        ใช้ทั้งหมด
                    </button>
                    <button
                        wire:click="generateDraft"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                    >
                        <x-heroicon-o-arrow-path class="w-3 h-3" />
                        <span wire:loading.remove wire:target="generateDraft">สร้างใหม่</span>
                        <span wire:loading wire:target="generateDraft">กำลังสร้าง...</span>
                    </button>
                </div>
            </div>

            @php
                $fields = [
                    'background' => 'ความเป็นมา',
                    'objectives' => 'วัตถุประสงค์',
                    'scope_of_work' => 'ขอบเขตงาน',
                    'deliverables' => 'ผลงานส่งมอบ',
                    'specifications' => 'ข้อกำหนดเทคนิค',
                    'qualification_requirements' => 'คุณสมบัติผู้เสนอราคา',
                    'payment_terms' => 'เงื่อนไขจ่ายเงิน',
                    'warranty_requirements' => 'การรับประกัน',
                    'penalty_clause' => 'เบี้ยปรับ',
                ];
            @endphp

            @foreach($fields as $key => $label)
                @if(!empty($result[$key]))
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $label }}</span>
                            <button
                                wire:click="applyField('{{ $key }}')"
                                class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 font-medium px-2 py-1 rounded hover:bg-blue-50 dark:hover:bg-blue-900/30 transition"
                            >
                                <x-heroicon-o-arrow-down-tray class="w-3 h-3" />
                                ใช้ฟิลด์นี้
                            </button>
                        </div>
                        <div class="text-xs text-gray-700 dark:text-gray-300 whitespace-pre-line max-h-32 overflow-y-auto">{{ Str::limit($result[$key], 500) }}</div>
                    </div>
                @endif
            @endforeach

            @if(!empty($result['evaluation_criteria']))
                <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">เกณฑ์ประเมิน</span>
                        <button
                            wire:click="applyField('evaluation_criteria')"
                            class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 font-medium px-2 py-1 rounded hover:bg-blue-50 dark:hover:bg-blue-900/30 transition"
                        >
                            <x-heroicon-o-arrow-down-tray class="w-3 h-3" />
                            ใช้ฟิลด์นี้
                        </button>
                    </div>
                    <div class="space-y-1">
                        @foreach($result['evaluation_criteria'] as $criteria)
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-700 dark:text-gray-300">{{ $criteria['name'] ?? '' }}</span>
                                <span class="text-gray-500 font-medium">{{ $criteria['weight'] ?? 0 }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
