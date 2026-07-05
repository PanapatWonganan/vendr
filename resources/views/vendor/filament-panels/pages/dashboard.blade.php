<x-filament-panels::page class="fi-dashboard-page">
    {{-- ตัวกรองปีถูกย้ายไปแสดงบนแถวหัวข้อแล้ว (ดู Dashboard::getHeader) --}}

    <x-filament-widgets::widgets
        :columns="$this->getColumns()"
        :data="
            [
                ...(property_exists($this, 'filters') ? ['filters' => $this->filters] : []),
                ...$this->getWidgetData(),
            ]
        "
        :widgets="$this->getVisibleWidgets()"
    />
</x-filament-panels::page>
