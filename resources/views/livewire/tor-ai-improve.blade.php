<div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-700">
    <div class="flex items-center gap-2 mb-3">
        <x-heroicon-o-light-bulb class="w-5 h-5 text-amber-500" />
        <h3 class="text-sm font-semibold text-amber-700 dark:text-amber-300">AI ปรับปรุงเนื้อหา</h3>
    </div>

    @if(!$improvedContent)
        <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">
            เลือกฟิลด์ที่ต้องการปรับปรุง AI จะช่วยปรับเนื้อหาให้เป็นไปตามหลัก SMART และกฎหมายจัดซื้อจัดจ้าง
        </p>

        <div class="space-y-2 mb-3">
            <select
                wire:model.live="field"
                class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
            >
                <option value="">-- เลือกฟิลด์ --</option>
                <option value="background">ความเป็นมา/หลักการและเหตุผล</option>
                <option value="objectives">วัตถุประสงค์</option>
                <option value="scope_of_work">ขอบเขตของงาน</option>
                <option value="deliverables">ผลงานที่ต้องส่งมอบ</option>
                <option value="specifications">ข้อกำหนดทางเทคนิค</option>
                <option value="qualification_requirements">คุณสมบัติผู้เสนอราคา</option>
                <option value="payment_terms">เงื่อนไขการจ่ายเงิน</option>
                <option value="warranty_requirements">การรับประกัน</option>
                <option value="penalty_clause">เงื่อนไขเบี้ยปรับ</option>
            </select>

            <textarea
                wire:model.blur="content"
                rows="4"
                placeholder="วางเนื้อหาปัจจุบันของฟิลด์ที่เลือก เพื่อให้ AI ปรับปรุง"
                class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
            ></textarea>
        </div>

        <button
            wire:click="improveField"
            wire:loading.attr="disabled"
            @if(empty($field) || empty($content)) disabled @endif
            class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
        >
            <span wire:loading.remove wire:target="improveField">
                <x-heroicon-o-light-bulb class="w-4 h-4" />
            </span>
            <span wire:loading wire:target="improveField">
                <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </span>
            <span wire:loading.remove wire:target="improveField">ปรับปรุงเนื้อหา</span>
            <span wire:loading wire:target="improveField">กำลังปรับปรุง...</span>
        </button>
    @endif

    @if($errorMessage)
        <div class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
            <p class="text-sm text-red-600 dark:text-red-400">{{ $errorMessage }}</p>
        </div>
    @endif

    @if($improvedContent)
        <div class="mt-3 space-y-3">
            <div class="flex items-center justify-between">
                <p class="text-sm text-green-600 dark:text-green-400 font-medium">ปรับปรุงเรียบร้อยแล้ว</p>
                <button
                    wire:click="applyImproved"
                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition"
                >
                    <x-heroicon-o-check class="w-3 h-3" />
                    ใช้เนื้อหาที่ปรับปรุง
                </button>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-600 max-h-48 overflow-y-auto">
                <p class="text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $improvedContent }}</p>
            </div>

            <button
                wire:click="$set('improvedContent', null)"
                class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
            >
                <x-heroicon-o-arrow-path class="w-3 h-3" />
                ปรับปรุงอีกครั้ง
            </button>
        </div>
    @endif
</div>
