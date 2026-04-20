<div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-700">
    <div class="flex items-center gap-2 mb-3">
        <x-heroicon-o-clipboard-document-check class="w-5 h-5 text-purple-500" />
        <h3 class="text-sm font-semibold text-purple-700 dark:text-purple-300">AI ตรวจสอบ TOR</h3>
    </div>

    @if(!$result)
        <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">
            ตรวจสอบ TOR ตามระเบียบจัดซื้อจัดจ้าง หลัก SMART และความครบถ้วนขององค์ประกอบ
        </p>

        <button
            wire:click="reviewTor"
            wire:loading.attr="disabled"
            class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
        >
            <span wire:loading.remove wire:target="reviewTor">
                <x-heroicon-o-clipboard-document-check class="w-4 h-4" />
            </span>
            <span wire:loading wire:target="reviewTor">
                <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </span>
            <span wire:loading.remove wire:target="reviewTor">ตรวจสอบ TOR</span>
            <span wire:loading wire:target="reviewTor">กำลังตรวจสอบ...</span>
        </button>
    @endif

    @if($errorMessage)
        <div class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
            <p class="text-sm text-red-600 dark:text-red-400">{{ $errorMessage }}</p>
        </div>
    @endif

    @if($result)
        <div class="mt-3 space-y-3">
            {{-- Score & Grade --}}
            <div class="flex items-center gap-4">
                @php
                    $gradeColor = match($result['grade'] ?? 'F') {
                        'A' => 'green',
                        'B' => 'blue',
                        'C' => 'yellow',
                        'D' => 'orange',
                        default => 'red',
                    };
                @endphp
                <div class="flex items-center gap-2">
                    <span class="text-3xl font-bold text-{{ $gradeColor }}-600 dark:text-{{ $gradeColor }}-400">
                        {{ $result['grade'] ?? 'N/A' }}
                    </span>
                    <div>
                        <span class="text-lg font-semibold text-gray-700 dark:text-gray-300">{{ $result['score'] ?? 0 }}/100</span>
                        <p class="text-xs text-gray-500">คะแนนรวม</p>
                    </div>
                </div>
            </div>

            {{-- Summary --}}
            @if(!empty($result['summary']))
                <div class="p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $result['summary'] }}</p>
                </div>
            @endif

            {{-- Issues --}}
            @if(!empty($result['issues']))
                <div class="space-y-2">
                    <h4 class="text-xs font-semibold text-red-600 dark:text-red-400 uppercase">ปัญหาที่พบ</h4>
                    @foreach($result['issues'] as $issue)
                        <div class="p-2 rounded-lg border
                            {{ ($issue['severity'] ?? '') === 'error' ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-700' : '' }}
                            {{ ($issue['severity'] ?? '') === 'warning' ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-700' : '' }}
                            {{ ($issue['severity'] ?? '') === 'info' ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700' : '' }}
                        ">
                            <div class="flex items-start gap-2">
                                @if(($issue['severity'] ?? '') === 'error')
                                    <x-heroicon-o-x-circle class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" />
                                @elseif(($issue['severity'] ?? '') === 'warning')
                                    <x-heroicon-o-exclamation-triangle class="w-4 h-4 text-yellow-500 flex-shrink-0 mt-0.5" />
                                @else
                                    <x-heroicon-o-information-circle class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5" />
                                @endif
                                <div>
                                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $issue['message'] ?? '' }}</p>
                                    @if(!empty($issue['suggestion']))
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $issue['suggestion'] }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Passed --}}
            @if(!empty($result['passed']))
                <div class="space-y-2">
                    <h4 class="text-xs font-semibold text-green-600 dark:text-green-400 uppercase">ผ่านเกณฑ์</h4>
                    @foreach($result['passed'] as $passed)
                        <div class="flex items-start gap-2 p-2 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-700">
                            <x-heroicon-o-check-circle class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" />
                            <p class="text-xs text-gray-700 dark:text-gray-300">{{ $passed['message'] ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            <button
                wire:click="reviewTor"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
            >
                <x-heroicon-o-arrow-path class="w-3 h-3" />
                ตรวจสอบอีกครั้ง
            </button>
        </div>
    @endif
</div>
