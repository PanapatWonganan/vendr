<x-filament-panels::page>
    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        {{-- Critical Open --}}
        <div class="rounded-xl p-4 border {{ ($summary['critical_open'] ?? 0) > 0 ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' : 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700' }}">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg {{ ($summary['critical_open'] ?? 0) > 0 ? 'bg-red-100 dark:bg-red-800' : 'bg-gray-100 dark:bg-gray-700' }}">
                    <x-heroicon-o-exclamation-circle class="w-6 h-6 {{ ($summary['critical_open'] ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-400' }}" />
                </div>
                <div>
                    <div class="text-2xl font-black {{ ($summary['critical_open'] ?? 0) > 0 ? 'text-red-700 dark:text-red-300' : 'text-gray-600 dark:text-gray-400' }}">
                        {{ $summary['critical_open'] ?? 0 }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">วิกฤต</div>
                </div>
            </div>
        </div>

        {{-- Total Open --}}
        <div class="rounded-xl p-4 border bg-yellow-50 dark:bg-yellow-900/10 border-yellow-200 dark:border-yellow-800">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-yellow-100 dark:bg-yellow-800">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                </div>
                <div>
                    <div class="text-2xl font-black text-yellow-700 dark:text-yellow-300">{{ $summary['total_open'] ?? 0 }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">รอตรวจสอบ</div>
                </div>
            </div>
        </div>

        {{-- Total Unresolved --}}
        <div class="rounded-xl p-4 border bg-blue-50 dark:bg-blue-900/10 border-blue-200 dark:border-blue-800">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-800">
                    <x-heroicon-o-clipboard-document-list class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <div class="text-2xl font-black text-blue-700 dark:text-blue-300">{{ $summary['total_unresolved'] ?? 0 }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">ยังไม่แก้ไข</div>
                </div>
            </div>
        </div>

        {{-- Resolved This Month --}}
        <div class="rounded-xl p-4 border bg-green-50 dark:bg-green-900/10 border-green-200 dark:border-green-800">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-green-100 dark:bg-green-800">
                    <x-heroicon-o-check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <div class="text-2xl font-black text-green-700 dark:text-green-300">{{ $summary['resolved_this_month'] ?? 0 }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">แก้ไขเดือนนี้</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Toolbar: Filters + Scan Button --}}
    <div class="flex flex-wrap items-center gap-3 mb-6">
        {{-- Type Filter --}}
        <select wire:model.live="filterType" class="fi-select-input rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            <option value="all">ทุกประเภท</option>
            <option value="price_anomaly">ราคาผิดปกติ</option>
            <option value="split_pr">แยก PR</option>
            <option value="budget_overrun">งบเกิน</option>
            <option value="vendor_concentration">Vendor กระจุกตัว</option>
            <option value="approval_delay">อนุมัติล่าช้า</option>
        </select>

        {{-- Severity Filter --}}
        <select wire:model.live="filterSeverity" class="fi-select-input rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            <option value="all">ทุกระดับ</option>
            <option value="critical">วิกฤต</option>
            <option value="warning">เตือน</option>
            <option value="info">แจ้งเตือน</option>
        </select>

        {{-- Status Filter --}}
        <select wire:model.live="filterStatus" class="fi-select-input rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            <option value="unresolved">ยังไม่แก้ไข</option>
            <option value="open">รอตรวจสอบ</option>
            <option value="acknowledged">รับทราบแล้ว</option>
            <option value="resolved">แก้ไขแล้ว</option>
            <option value="dismissed">ยกเลิก</option>
            <option value="all">ทั้งหมด</option>
        </select>

        <div class="ml-auto">
            <x-filament::button
                wire:click="runScan"
                icon="heroicon-o-magnifying-glass"
                color="primary"
                wire:loading.attr="disabled"
                wire:target="runScan"
            >
                <span wire:loading.remove wire:target="runScan">สแกนตอนนี้</span>
                <span wire:loading wire:target="runScan">กำลังสแกน...</span>
            </x-filament::button>
        </div>
    </div>

    {{-- Anomaly breakdown by type (mini stats) --}}
    @if(!empty($summary['by_type']))
    <div class="flex flex-wrap gap-2 mb-6">
        @php
            $typeConfig = [
                'price_anomaly' => ['label' => 'ราคาผิดปกติ', 'color' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300'],
                'split_pr' => ['label' => 'แยก PR', 'color' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300'],
                'budget_overrun' => ['label' => 'งบเกิน', 'color' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'],
                'vendor_concentration' => ['label' => 'Vendor กระจุก', 'color' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'],
                'approval_delay' => ['label' => 'อนุมัติล่าช้า', 'color' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300'],
            ];
        @endphp
        @foreach($summary['by_type'] as $type => $count)
            @php $cfg = $typeConfig[$type] ?? ['label' => $type, 'color' => 'bg-gray-100 text-gray-700']; @endphp
            <span class="px-3 py-1.5 rounded-full text-xs font-semibold {{ $cfg['color'] }}">
                {{ $cfg['label'] }}: {{ $count }}
            </span>
        @endforeach
    </div>
    @endif

    {{-- Anomaly List --}}
    @if(count($anomalies) > 0)
        <div class="space-y-3">
            @foreach($anomalies as $anomaly)
                @php
                    $severityBg = match($anomaly['severity']) {
                        'critical' => 'border-l-4 border-l-red-500 bg-red-50/50 dark:bg-red-900/10',
                        'warning' => 'border-l-4 border-l-yellow-500 bg-yellow-50/50 dark:bg-yellow-900/10',
                        default => 'border-l-4 border-l-blue-500 bg-blue-50/50 dark:bg-blue-900/10',
                    };
                    $severityIcon = match($anomaly['severity']) {
                        'critical' => 'text-red-500',
                        'warning' => 'text-yellow-500',
                        default => 'text-blue-500',
                    };
                    $typeIcon = match($anomaly['type']) {
                        'price_anomaly' => 'heroicon-o-currency-dollar',
                        'split_pr' => 'heroicon-o-scissors',
                        'budget_overrun' => 'heroicon-o-exclamation-triangle',
                        'vendor_concentration' => 'heroicon-o-building-storefront',
                        'approval_delay' => 'heroicon-o-clock',
                        default => 'heroicon-o-question-mark-circle',
                    };
                    $typeLabel = match($anomaly['type']) {
                        'price_anomaly' => 'ราคาผิดปกติ',
                        'split_pr' => 'แยก PR',
                        'budget_overrun' => 'งบเกิน',
                        'vendor_concentration' => 'Vendor กระจุก',
                        'approval_delay' => 'อนุมัติล่าช้า',
                        default => $anomaly['type'],
                    };
                    $severityLabel = match($anomaly['severity']) {
                        'critical' => 'วิกฤต',
                        'warning' => 'เตือน',
                        default => 'แจ้งเตือน',
                    };
                    $statusLabel = match($anomaly['status']) {
                        'open' => 'รอตรวจสอบ',
                        'acknowledged' => 'รับทราบแล้ว',
                        'resolved' => 'แก้ไขแล้ว',
                        'dismissed' => 'ยกเลิก',
                        default => $anomaly['status'],
                    };
                    $statusColor = match($anomaly['status']) {
                        'open' => 'danger',
                        'acknowledged' => 'warning',
                        'resolved' => 'success',
                        'dismissed' => 'gray',
                        default => 'gray',
                    };
                @endphp

                <div class="rounded-lg border border-gray-200 dark:border-gray-700 {{ $severityBg }} overflow-hidden">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                {{-- Type Icon --}}
                                <div class="p-2 rounded-lg bg-white dark:bg-gray-800 shadow-sm shrink-0">
                                    <x-dynamic-component :component="$typeIcon" class="w-5 h-5 {{ $severityIcon }}" />
                                </div>

                                <div class="flex-1 min-w-0">
                                    {{-- Header --}}
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded {{ match($anomaly['severity']) {
                                            'critical' => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
                                            'warning' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
                                            default => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                                        } }}">
                                            {{ $severityLabel }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $typeLabel }}</span>
                                        <x-filament::badge :color="$statusColor" size="sm">{{ $statusLabel }}</x-filament::badge>
                                    </div>

                                    {{-- Title --}}
                                    <h4 class="font-semibold mt-1 text-gray-900 dark:text-white">{{ $anomaly['title'] }}</h4>

                                    {{-- Description --}}
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $anomaly['description'] }}</p>

                                    {{-- Meta --}}
                                    <div class="flex flex-wrap items-center gap-3 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        @if($anomaly['department'])
                                            <span>{{ $anomaly['department']['name'] ?? '' }}</span>
                                        @endif
                                        @if($anomaly['vendor'])
                                            <span>{{ $anomaly['vendor']['company_name'] ?? '' }}</span>
                                        @endif
                                        @if($anomaly['amount'])
                                            <span>{{ number_format($anomaly['amount'], 2) }} THB</span>
                                        @endif
                                        @if($anomaly['deviation_percent'])
                                            <span class="{{ $anomaly['deviation_percent'] > 0 ? 'text-red-500' : 'text-green-500' }}">
                                                {{ $anomaly['deviation_percent'] > 0 ? '+' : '' }}{{ number_format($anomaly['deviation_percent'], 1) }}%
                                            </span>
                                        @endif
                                        <span>{{ \Carbon\Carbon::parse($anomaly['created_at'])->diffForHumans() }}</span>
                                    </div>

                                    {{-- Details (expandable via click) --}}
                                    @if(!empty($anomaly['details']))
                                    <details class="mt-2">
                                        <summary class="text-xs text-primary-600 dark:text-primary-400 cursor-pointer hover:underline">
                                            ดูรายละเอียด
                                        </summary>
                                        <div class="mt-2 p-2 bg-white dark:bg-gray-800 rounded text-xs font-mono overflow-x-auto">
                                            @foreach($anomaly['details'] as $key => $value)
                                                <div class="flex gap-2 py-0.5">
                                                    <span class="text-gray-500 shrink-0">{{ $key }}:</span>
                                                    <span class="text-gray-900 dark:text-gray-100">
                                                        @if(is_array($value))
                                                            {{ json_encode($value, JSON_UNESCAPED_UNICODE) }}
                                                        @else
                                                            {{ $value }}
                                                        @endif
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                    @endif
                                </div>
                            </div>

                            {{-- Actions --}}
                            @if(in_array($anomaly['status'], ['open', 'acknowledged']))
                            <div class="flex flex-col gap-1 shrink-0">
                                @if($anomaly['status'] === 'open')
                                <x-filament::button
                                    wire:click="acknowledgeAnomaly({{ $anomaly['id'] }})"
                                    size="xs"
                                    color="warning"
                                    icon="heroicon-o-eye"
                                >
                                    รับทราบ
                                </x-filament::button>
                                @endif
                                <x-filament::button
                                    wire:click="resolveAnomaly({{ $anomaly['id'] }})"
                                    size="xs"
                                    color="success"
                                    icon="heroicon-o-check"
                                >
                                    แก้ไขแล้ว
                                </x-filament::button>
                                <x-filament::button
                                    wire:click="dismissAnomaly({{ $anomaly['id'] }})"
                                    size="xs"
                                    color="gray"
                                    icon="heroicon-o-x-mark"
                                >
                                    ยกเลิก
                                </x-filament::button>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- Empty State --}}
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="bg-green-100 dark:bg-green-900/20 rounded-full p-6 mb-4">
                <x-heroicon-o-shield-check class="w-12 h-12 text-green-500" />
            </div>
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">ไม่พบความผิดปกติ</h3>
            <p class="text-gray-500 dark:text-gray-400 mt-1 text-sm">
                @if($filterStatus === 'unresolved' && $filterType === 'all' && $filterSeverity === 'all')
                    ทุกอย่างปกติดี กด "สแกนตอนนี้" เพื่อตรวจสอบล่าสุด
                @else
                    ไม่พบรายการตาม filter ที่เลือก ลองเปลี่ยน filter ดู
                @endif
            </p>
        </div>
    @endif
</x-filament-panels::page>
