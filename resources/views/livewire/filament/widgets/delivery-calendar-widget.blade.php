<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            📅 ปฏิทินกำหนดส่งมอบ
        </x-slot>
        
        <div class="space-y-4">
            <!-- Summary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-blue-600 font-medium">Purchase Orders</p>
                            <p class="text-2xl font-bold text-blue-700">{{ $totalPOs }}</p>
                        </div>
                        <div class="text-blue-500 text-2xl">📋</div>
                    </div>
                </div>
                
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-green-600 font-medium">Purchase Requisitions</p>
                            <p class="text-2xl font-bold text-green-700">{{ $totalPRs }}</p>
                        </div>
                        <div class="text-green-500 text-2xl">📝</div>
                    </div>
                </div>
                
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-red-600 font-medium">เร่งด่วน</p>
                            <p class="text-2xl font-bold text-red-700">{{ $urgentCount }}</p>
                        </div>
                        <div class="text-red-500 text-2xl">🚨</div>
                    </div>
                </div>
                
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-purple-600 font-medium">เดือนนี้</p>
                            <p class="text-lg font-bold text-purple-700">{{ $currentMonth->format('M Y') }}</p>
                        </div>
                        <div class="text-purple-500 text-2xl">📅</div>
                    </div>
                </div>
            </div>
            
            <!-- Upcoming Events List -->
            @php
                $upcomingEvents = $events->flatten(1)
                    ->filter(function($event) {
                        return isset($event['days_until']) && $event['days_until'] >= 0 && $event['days_until'] <= 30;
                    })
                    ->sortBy('days_until')
                    ->take(8);
            @endphp
            
            @if($upcomingEvents->isNotEmpty())
                <div class="bg-white rounded-lg border p-4">
                    <h3 class="text-lg font-semibold mb-3">🔔 กำหนดการที่ใกล้เข้ามา</h3>
                    
                    <div class="space-y-2">
                        @foreach($upcomingEvents as $event)
                            <div class="flex items-center justify-between p-3 rounded-lg border hover:bg-gray-50
                                {{ $event['color'] === 'red' ? 'border-red-200 bg-red-50' : '' }}
                                {{ $event['color'] === 'orange' ? 'border-orange-200 bg-orange-50' : '' }}
                                {{ $event['color'] === 'yellow' ? 'border-yellow-200 bg-yellow-50' : '' }}
                                {{ $event['color'] === 'green' ? 'border-green-200 bg-green-50' : '' }}">
                                
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-medium">{{ $event['title'] }}</h4>
                                        
                                        @if($event['days_until'] <= 1)
                                            <span class="bg-red-100 text-red-600 text-xs px-2 py-1 rounded-full font-medium">
                                                เร่งด่วน
                                            </span>
                                        @elseif($event['days_until'] <= 7)
                                            <span class="bg-orange-100 text-orange-600 text-xs px-2 py-1 rounded-full font-medium">
                                                สำคัญ
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <p class="text-sm text-gray-600">{{ $event['subtitle'] }}</p>
                                    
                                    @if($event['amount'] ?? false)
                                        <p class="text-sm font-medium">{{ $event['amount'] }} บาท</p>
                                    @endif
                                </div>
                                
                                <div class="text-right">
                                    <div class="text-sm font-medium 
                                        {{ $event['color'] === 'red' ? 'text-red-600' : '' }}
                                        {{ $event['color'] === 'orange' ? 'text-orange-600' : '' }}
                                        {{ $event['color'] === 'yellow' ? 'text-yellow-600' : '' }}
                                        {{ $event['color'] === 'green' ? 'text-green-600' : '' }}">
                                        {{ $event['display_date'] }}
                                    </div>
                                    
                                    <div class="text-xs text-gray-500">
                                        @if($event['days_until'] == 0)
                                            วันนี้
                                        @elseif($event['days_until'] == 1)
                                            พรุ่งนี้
                                        @else
                                            อีก {{ $event['days_until'] }} วัน
                                        @endif
                                    </div>
                                </div>
                                
                                @if($event['url'] ?? false)
                                    <a href="{{ $event['url'] }}" class="ml-2 text-blue-600 hover:text-blue-800">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="bg-green-50 border border-green-200 rounded-lg p-8 text-center">
                    <div class="text-green-500 text-4xl mb-2">✅</div>
                    <h3 class="text-lg font-semibold text-green-700 mb-1">ไม่มีกำหนดการที่เร่งด่วน</h3>
                    <p class="text-green-600">ทุกอย่างอยู่ในกำหนดเวลาที่เหมาะสม</p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>