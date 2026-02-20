<x-filament-panels::page>
    @push('styles')
    <style>
        .gauge-container {
            position: relative;
            width: 200px;
            height: 120px;
            margin: 0 auto;
        }
        .gauge-bg {
            position: absolute;
            top: 0;
            left: 0;
        }
        .gauge-needle {
            position: absolute;
            bottom: 10px;
            left: 50%;
            width: 4px;
            height: 80px;
            background: #1f2937;
            transform-origin: bottom center;
            border-radius: 2px;
            transition: transform 1s ease-out;
        }
        .dark .gauge-needle {
            background: #e5e7eb;
        }
        .gauge-center {
            position: absolute;
            bottom: 2px;
            left: 50%;
            transform: translateX(-50%);
            width: 16px;
            height: 16px;
            background: #1f2937;
            border-radius: 50%;
        }
        .dark .gauge-center {
            background: #e5e7eb;
        }
        .dimension-bar {
            transition: width 0.8s ease-out;
        }
    </style>
    @endpush

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Panel: Search & Assess --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Search Form --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-magnifying-glass class="w-5 h-5 text-primary-500" />
                        <span>ค้นหา Vendor</span>
                    </div>
                </x-slot>

                <form wire:submit.prevent="runAssessment" class="space-y-4">
                    {{ $this->form }}

                    <div class="pt-2">
                        <x-filament::button
                            type="submit"
                            color="primary"
                            icon="heroicon-o-shield-check"
                            class="w-full"
                            wire:loading.attr="disabled"
                            wire:target="runAssessment"
                        >
                            <span wire:loading.remove wire:target="runAssessment">
                                ประเมินความเสี่ยง
                            </span>
                            <span wire:loading wire:target="runAssessment">
                                กำลังประเมิน...
                            </span>
                        </x-filament::button>
                    </div>
                </form>
            </x-filament::section>

            {{-- Quick Info --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-information-circle class="w-5 h-5 text-gray-500" />
                        <span>ระดับความเสี่ยง</span>
                    </div>
                </x-slot>

                <div class="space-y-3 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-green-500"></span>
                        <span class="font-medium">Low (0-25):</span>
                        <span class="text-gray-500 dark:text-gray-400">ความเสี่ยงต่ำ เหมาะสม</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                        <span class="font-medium">Medium (26-50):</span>
                        <span class="text-gray-500 dark:text-gray-400">ปานกลาง ควรติดตาม</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-red-500"></span>
                        <span class="font-medium">High (51-75):</span>
                        <span class="text-gray-500 dark:text-gray-400">สูง ระมัดระวัง</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-red-800"></span>
                        <span class="font-medium">Critical (76-100):</span>
                        <span class="text-gray-500 dark:text-gray-400">วิกฤต ไม่แนะนำ</span>
                    </div>
                </div>
            </x-filament::section>

            {{-- Assessment History --}}
            @if(count($assessmentHistory) > 0)
            <x-filament::section collapsible collapsed>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clock class="w-5 h-5 text-gray-500" />
                        <span>ประวัติการประเมิน ({{ count($assessmentHistory) }})</span>
                    </div>
                </x-slot>

                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @foreach($assessmentHistory as $history)
                    <div
                        wire:click="viewAssessment({{ $history['id'] }})"
                        class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium truncate">
                                {{ $history['vendor']['company_name'] ?? $history['dbd_name_th'] ?? $history['tax_id'] }}
                            </span>
                            @php
                                $riskColor = match($history['overall_risk_level'] ?? 'unknown') {
                                    'low' => 'success',
                                    'medium' => 'warning',
                                    'high' => 'danger',
                                    'critical' => 'danger',
                                    default => 'gray',
                                };
                            @endphp
                            <x-filament::badge :color="$riskColor" size="sm">
                                {{ $history['overall_risk_score'] ?? '-' }}/100
                            </x-filament::badge>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ \Carbon\Carbon::parse($history['created_at'])->diffForHumans() }}
                            @if($history['assessed_by'])
                                &middot; {{ $history['assessed_by']['name'] ?? '' }}
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-filament::section>
            @endif
        </div>

        {{-- Right Panel: Assessment Results --}}
        <div class="lg:col-span-2 space-y-6">
            @if($currentAssessment)
                {{-- Risk Score Header with Gauge --}}
                @php
                    $riskLevel = $currentAssessment->overall_risk_level ?? $currentAssessment->risk_level ?? 'unknown';
                    $riskScore = $currentAssessment->overall_risk_score ?? $currentAssessment->risk_score ?? 0;
                    $safeScore = 100 - $riskScore;
                @endphp
                <div class="rounded-xl p-6 {{ match($riskLevel) {
                    'low' => 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800',
                    'medium' => 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800',
                    'high' => 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800',
                    'critical' => 'bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700',
                    default => 'bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700',
                } }}">
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <div class="flex-1 min-w-0">
                            <h2 class="text-2xl font-bold {{ match($riskLevel) {
                                'low' => 'text-green-700 dark:text-green-300',
                                'medium' => 'text-yellow-700 dark:text-yellow-300',
                                'high' => 'text-red-700 dark:text-red-300',
                                'critical' => 'text-red-800 dark:text-red-200',
                                default => 'text-gray-700 dark:text-gray-300',
                            } }}">
                                {{ $currentAssessment->risk_level_label }}
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ $currentAssessment->vendor?->company_name ?? $currentAssessment->dbd_name_th ?? $currentAssessment->tax_id }}
                            </p>

                            @if($currentAssessment->ai_summary)
                            <div class="mt-3 p-3 bg-white/60 dark:bg-gray-900/40 rounded-lg">
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ $currentAssessment->ai_summary }}
                                </p>
                            </div>
                            @endif

                            <div class="mt-3 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                <span>Model: {{ $currentAssessment->ai_model_used ?? '-' }}</span>
                                <span>&middot;</span>
                                <span>{{ $currentAssessment->created_at?->format('d/m/Y H:i') }}</span>
                                @if($currentAssessment->assessedBy)
                                <span>&middot;</span>
                                <span>โดย {{ $currentAssessment->assessedBy->name }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Gauge Score --}}
                        <div class="flex-shrink-0">
                            <div class="gauge-container" id="gauge-container">
                                <svg width="200" height="120" viewBox="0 0 200 120" class="gauge-bg">
                                    {{-- Background arc --}}
                                    <path d="M 20 110 A 80 80 0 0 1 180 110" fill="none" stroke="#e5e7eb" stroke-width="16" stroke-linecap="round" />
                                    {{-- Colored arc segments --}}
                                    <path d="M 20 110 A 80 80 0 0 1 60 38" fill="none" stroke="#22c55e" stroke-width="16" stroke-linecap="round" />
                                    <path d="M 60 38 A 80 80 0 0 1 100 30" fill="none" stroke="#eab308" stroke-width="16" />
                                    <path d="M 100 30 A 80 80 0 0 1 140 38" fill="none" stroke="#f97316" stroke-width="16" />
                                    <path d="M 140 38 A 80 80 0 0 1 180 110" fill="none" stroke="#ef4444" stroke-width="16" stroke-linecap="round" />
                                </svg>
                                {{-- Needle --}}
                                <div class="gauge-needle" id="gauge-needle" style="transform: rotate(-90deg);"></div>
                                <div class="gauge-center"></div>
                            </div>
                            <div class="text-center -mt-2" id="gauge-risk-score" data-score="{{ $riskScore }}">
                                <span class="text-4xl font-black {{ match($riskLevel) {
                                    'low' => 'text-green-600 dark:text-green-400',
                                    'medium' => 'text-yellow-600 dark:text-yellow-400',
                                    'high' => 'text-red-600 dark:text-red-400',
                                    'critical' => 'text-red-700 dark:text-red-300',
                                    default => 'text-gray-600 dark:text-gray-400',
                                } }}">{{ $riskScore }}</span>
                                <span class="text-sm text-gray-400">/100</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Radar Chart --}}
                    <x-filament::section>
                        <x-slot name="heading">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-chart-bar class="w-5 h-5 text-indigo-500" />
                                <span>คะแนนรายด้าน (Radar Chart)</span>
                            </div>
                        </x-slot>

                        @if($currentAssessment->dimension_scores)
                            <div style="position: relative; height: 300px;">
                                <canvas id="radarChart"></canvas>
                                <div id="radar-data" data-scores="{{ json_encode($currentAssessment->dimension_scores) }}" class="hidden"></div>
                            </div>

                            {{-- Dimension Score Bars --}}
                            @php
                                $dimensions = $currentAssessment->dimension_scores;
                                $dimensionLabels = [
                                    'legal_status' => 'สถานะนิติบุคคล',
                                    'company_age' => 'อายุบริษัท',
                                    'capital' => 'ทุนจดทะเบียน',
                                    'objective_match' => 'ความสอดคล้องกิจการ',
                                    'internal_performance' => 'ประเมินภายใน',
                                    'po_history' => 'ประวัติสั่งซื้อ',
                                ];
                            @endphp
                            <div class="mt-4 space-y-2">
                                @foreach($dimensionLabels as $key => $label)
                                    @php
                                        $score = $dimensions[$key] ?? 0;
                                        $barColor = match(true) {
                                            $score >= 75 => 'bg-green-500',
                                            $score >= 50 => 'bg-yellow-500',
                                            $score >= 25 => 'bg-orange-500',
                                            default => 'bg-red-500',
                                        };
                                    @endphp
                                    <div>
                                        <div class="flex justify-between text-xs mb-1">
                                            <span class="text-gray-600 dark:text-gray-400">{{ $label }}</span>
                                            <span class="font-semibold">{{ $score }}</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="dimension-bar {{ $barColor }} h-2 rounded-full" style="width: {{ $score }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <x-heroicon-o-chart-bar class="w-8 h-8 mx-auto mb-2 opacity-50" />
                                <p class="text-sm">ไม่มีข้อมูลคะแนนรายด้าน</p>
                            </div>
                        @endif
                    </x-filament::section>

                    {{-- DBD Info --}}
                    <x-filament::section>
                        <x-slot name="heading">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-building-library class="w-5 h-5 text-blue-500" />
                                <span>ข้อมูลจาก DBD</span>
                            </div>
                        </x-slot>

                        @if($currentAssessment->hasDbdData() && $currentAssessment->dbd_status)
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">สถานะ:</span>
                                <x-filament::badge :color="$currentAssessment->dbd_status_color">
                                    {{ $currentAssessment->dbd_status }}
                                </x-filament::badge>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">ชื่อ (TH):</span>
                                <span class="font-medium text-right">{{ $currentAssessment->dbd_name_th ?? '-' }}</span>
                            </div>
                            @if($currentAssessment->dbd_name_en)
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">ชื่อ (EN):</span>
                                <span class="font-medium text-right">{{ $currentAssessment->dbd_name_en }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">ประเภท:</span>
                                <span>{{ $currentAssessment->dbd_entity_type ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">ทุนจดทะเบียน:</span>
                                <span class="font-medium">
                                    {{ $currentAssessment->dbd_registered_capital ? number_format($currentAssessment->dbd_registered_capital, 2) . ' THB' : '-' }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">วันจดทะเบียน:</span>
                                <span>{{ $currentAssessment->dbd_registration_date?->format('d/m/Y') ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">อายุบริษัท:</span>
                                <span>{{ $currentAssessment->dbd_company_age ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Tax ID:</span>
                                <span class="font-mono">{{ $currentAssessment->tax_id }}</span>
                            </div>
                            @if($currentAssessment->dbd_address)
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 block mb-1">ที่อยู่:</span>
                                <span class="text-xs">{{ $currentAssessment->dbd_address }}</span>
                            </div>
                            @endif
                        </div>
                        @else
                        <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                            <x-heroicon-o-exclamation-triangle class="w-8 h-8 mx-auto mb-2 text-yellow-500" />
                            <p>ไม่สามารถดึงข้อมูลจาก DBD ได้</p>
                            <p class="text-xs mt-1">กรุณาตรวจสอบเลขทะเบียนนิติบุคคลอีกครั้ง</p>
                        </div>
                        @endif
                    </x-filament::section>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Risk Factors --}}
                    @if(!empty($currentAssessment->ai_risk_factors))
                    <x-filament::section>
                        <x-slot name="heading">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-red-500" />
                                <span>ปัจจัยเสี่ยง ({{ count($currentAssessment->ai_risk_factors) }})</span>
                            </div>
                        </x-slot>

                        <ul class="space-y-2">
                            @foreach($currentAssessment->ai_risk_factors as $factor)
                            <li class="flex items-start gap-2 text-sm">
                                <span class="text-red-500 mt-0.5 shrink-0">&#9679;</span>
                                <span>{{ $factor }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </x-filament::section>
                    @endif

                    {{-- Strengths --}}
                    @if(!empty($currentAssessment->ai_strengths))
                    <x-filament::section>
                        <x-slot name="heading">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-check-circle class="w-5 h-5 text-green-500" />
                                <span>จุดแข็ง ({{ count($currentAssessment->ai_strengths) }})</span>
                            </div>
                        </x-slot>

                        <ul class="space-y-2">
                            @foreach($currentAssessment->ai_strengths as $strength)
                            <li class="flex items-start gap-2 text-sm">
                                <span class="text-green-500 mt-0.5 shrink-0">&#9679;</span>
                                <span>{{ $strength }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </x-filament::section>
                    @endif
                </div>

                {{-- Recommendations --}}
                @if(!empty($currentAssessment->ai_recommendations))
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-light-bulb class="w-5 h-5 text-yellow-500" />
                            <span>คำแนะนำ</span>
                        </div>
                    </x-slot>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($currentAssessment->ai_recommendations as $index => $rec)
                        <div class="flex items-start gap-3 p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/10 border border-yellow-100 dark:border-yellow-800/30">
                            <span class="flex items-center justify-center w-6 h-6 rounded-full bg-yellow-200 dark:bg-yellow-800 text-yellow-800 dark:text-yellow-200 text-xs font-bold shrink-0">
                                {{ $index + 1 }}
                            </span>
                            <span class="text-sm">{{ $rec }}</span>
                        </div>
                        @endforeach
                    </div>
                </x-filament::section>
                @endif

            @else
                {{-- Empty State --}}
                <div class="flex flex-col items-center justify-center py-24 text-center">
                    <div class="bg-gray-100 dark:bg-gray-800 rounded-full p-6 mb-6">
                        <x-heroicon-o-shield-check class="w-16 h-16 text-gray-400 dark:text-gray-500" />
                    </div>
                    <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300">Vendor Risk Assessment</h3>
                    <p class="text-gray-500 dark:text-gray-400 mt-2 max-w-md">
                        เลือก Vendor หรือกรอกเลขทะเบียนนิติบุคคล 13 หลัก
                        แล้วกด "ประเมินความเสี่ยง" เพื่อตรวจสอบข้อมูลจาก DBD และวิเคราะห์ด้วย AI
                    </p>
                    <div class="mt-6 grid grid-cols-3 gap-4 text-center">
                        <div class="p-4">
                            <x-heroicon-o-building-library class="w-8 h-8 text-blue-500 mx-auto mb-2" />
                            <p class="text-xs text-gray-500 dark:text-gray-400">ข้อมูลจาก<br>กรมพัฒนาธุรกิจการค้า</p>
                        </div>
                        <div class="p-4">
                            <x-heroicon-o-cpu-chip class="w-8 h-8 text-purple-500 mx-auto mb-2" />
                            <p class="text-xs text-gray-500 dark:text-gray-400">วิเคราะห์ด้วย<br>AI</p>
                        </div>
                        <div class="p-4">
                            <x-heroicon-o-chart-bar class="w-8 h-8 text-green-500 mx-auto mb-2" />
                            <p class="text-xs text-gray-500 dark:text-gray-400">ข้อมูลประเมิน<br>ภายในองค์กร</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Chart.js CDN - always loaded --}}
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        let radarChartInstance = null;

        function initCharts() {
            // Gauge needle animation
            const needle = document.getElementById('gauge-needle');
            const scoreEl = document.getElementById('gauge-risk-score');
            if (needle && scoreEl) {
                const riskScore = parseInt(scoreEl.dataset.score || 0);
                const angle = -90 + (riskScore / 100) * 180;
                needle.style.transform = 'rotate(-90deg)';
                setTimeout(() => {
                    needle.style.transform = `rotate(${angle}deg)`;
                }, 200);
            }

            // Radar Chart
            const ctx = document.getElementById('radarChart');
            const dataEl = document.getElementById('radar-data');
            if (ctx && dataEl) {
                // Destroy existing chart if present
                if (radarChartInstance) {
                    radarChartInstance.destroy();
                    radarChartInstance = null;
                }

                const isDark = document.documentElement.classList.contains('dark');
                const textColor = isDark ? '#9ca3af' : '#6b7280';

                try {
                    const dimensionScores = JSON.parse(dataEl.dataset.scores || '{}');

                    radarChartInstance = new Chart(ctx, {
                        type: 'radar',
                        data: {
                            labels: [
                                'สถานะนิติบุคคล',
                                'อายุบริษัท',
                                'ทุนจดทะเบียน',
                                'ความสอดคล้องกิจการ',
                                'ประเมินภายใน',
                                'ประวัติสั่งซื้อ'
                            ],
                            datasets: [{
                                label: 'คะแนนรายด้าน',
                                data: [
                                    dimensionScores.legal_status || 0,
                                    dimensionScores.company_age || 0,
                                    dimensionScores.capital || 0,
                                    dimensionScores.objective_match || 0,
                                    dimensionScores.internal_performance || 0,
                                    dimensionScores.po_history || 0
                                ],
                                fill: true,
                                backgroundColor: 'rgba(99, 102, 241, 0.2)',
                                borderColor: 'rgb(99, 102, 241)',
                                pointBackgroundColor: 'rgb(99, 102, 241)',
                                pointBorderColor: '#fff',
                                pointHoverBackgroundColor: '#fff',
                                pointHoverBorderColor: 'rgb(99, 102, 241)',
                                borderWidth: 2,
                                pointRadius: 4,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.label + ': ' + context.raw + '/100';
                                        }
                                    }
                                }
                            },
                            scales: {
                                r: {
                                    beginAtZero: true,
                                    max: 100,
                                    min: 0,
                                    ticks: {
                                        stepSize: 25,
                                        color: textColor,
                                        backdropColor: 'transparent',
                                        font: { size: 10 },
                                    },
                                    pointLabels: {
                                        color: textColor,
                                        font: { size: 11, family: 'Noto Sans Thai, sans-serif' },
                                    },
                                    grid: {
                                        color: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
                                    },
                                    angleLines: {
                                        color: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
                                    }
                                }
                            }
                        }
                    });
                } catch (e) {
                    console.error('Radar chart error:', e);
                }
            }
        }

        // Init on page load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initCharts, 100);
        });

        // Re-init after Livewire morphs the DOM
        document.addEventListener('livewire:morph.updated', function() {
            setTimeout(initCharts, 200);
        });

        // Fallback: use MutationObserver to detect when chart canvas appears
        const observer = new MutationObserver(function(mutations) {
            const canvas = document.getElementById('radarChart');
            const needle = document.getElementById('gauge-needle');
            if (canvas || needle) {
                setTimeout(initCharts, 200);
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    </script>
    @endpush
</x-filament-panels::page>
