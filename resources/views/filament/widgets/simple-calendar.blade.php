<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            📅 ปฏิทินกำหนดการส่งมอบงาน
        </x-slot>

        @php
            $events = $this->getCalendarEvents();
            $upcomingEvents = collect($events)->sortBy('start')->take(10);
        @endphp

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เรื่อง</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ประเภท</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($upcomingEvents as $event)
                        @php
                            $date = \Carbon\Carbon::parse($event['start']);
                            $isPast = $date->isPast();
                            $isUrgent = $date->diffInDays(now()) <= 3;
                            
                            $badgeColor = 'gray';
                            if ($event['priority'] === 'overdue' || $isPast) {
                                $badgeColor = 'red';
                            } elseif ($isUrgent) {
                                $badgeColor = 'orange';
                            } elseif (str_contains($event['type'], 'po')) {
                                $badgeColor = 'blue';
                            } else {
                                $badgeColor = 'green';
                            }
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div class="font-medium">{{ $date->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $date->diffForHumans() }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="font-medium">{{ $event['title'] }}</div>
                                <div class="text-xs text-gray-500">{{ $event['description'] ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    @if($badgeColor === 'red') bg-red-100 text-red-800
                                    @elseif($badgeColor === 'orange') bg-orange-100 text-orange-800  
                                    @elseif($badgeColor === 'blue') bg-blue-100 text-blue-800
                                    @elseif($badgeColor === 'green') bg-green-100 text-green-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    @if(str_contains($event['type'], 'po'))
                                        Purchase Order
                                    @else
                                        Purchase Request
                                    @endif
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($event['priority'] === 'overdue' || $isPast)
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-700">
                                        ⚠️ เลยกำหนด
                                    </span>
                                @elseif($isUrgent)
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-orange-100 text-orange-700">
                                        🔥 เร่งด่วน
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700">
                                        ✅ ปกติ
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                @if(isset($event['url']))
                                    <a href="{{ $event['url'] }}" class="text-indigo-600 hover:text-indigo-900">
                                        ดูรายละเอียด
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                <div class="text-sm">ไม่มีกำหนดการในขณะนี้</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(count($events) > 10)
            <div class="px-6 py-3 bg-gray-50 text-center text-sm text-gray-500">
                แสดง 10 รายการจากทั้งหมด {{ count($events) }} รายการ
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>