<x-filament-widgets::widget>
    @php
        $stats = $this->getGrStats();
    @endphp
    <x-filament::section>
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-purple-50 dark:bg-purple-500/10">
                    <x-heroicon-o-calendar-days class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        ปฏิทินการตรวจรับสินค้า (GR)
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        GR ทั้งหมด {{ $stats['total'] }} รายการ
                        @if($stats['recent'] > 0)
                            | ล่าสุด {{ $stats['recent'] }} รายการ
                        @endif
                    </p>
                </div>
            </div>
            <x-filament::button
                href="{{ url('/admin/calendar-page') }}"
                tag="a"
                icon="heroicon-m-calendar-days"
                size="sm"
                color="info"
            >
                ดูปฏิทิน
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
