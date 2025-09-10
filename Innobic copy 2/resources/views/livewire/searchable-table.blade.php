<div class="bg-white rounded-lg shadow overflow-hidden">
    <!-- Search and Controls -->
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- Search Input -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input wire:model.live.debounce.300ms="search" 
                       type="text" 
                       placeholder="ค้นหา..." 
                       class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full sm:w-64">
            </div>
            
            <!-- Per Page Selector -->
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-700">แสดง:</span>
                <select wire:model.live="perPage" class="border border-gray-300 rounded px-3 py-1 text-sm">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="text-sm text-gray-700">รายการ</span>
            </div>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div wire:loading class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10">
        <div class="flex items-center space-x-2 text-blue-600">
            <i class="fas fa-spinner fa-spin"></i>
            <span>กำลังโหลด...</span>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    @foreach($columns as $column)
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            @if($column['sortable'] ?? true)
                                <button wire:click="sortBy('{{ $column['field'] }}')" 
                                        class="flex items-center hover:text-gray-700 focus:outline-none">
                                    <span>{{ $column['label'] }}</span>
                                    <div class="ml-1">
                                        @if($sortField === $column['field'])
                                            @if($sortDirection === 'asc')
                                                <i class="fas fa-sort-up text-blue-500"></i>
                                            @else
                                                <i class="fas fa-sort-down text-blue-500"></i>
                                            @endif
                                        @else
                                            <i class="fas fa-sort text-gray-300"></i>
                                        @endif
                                    </div>
                                </button>
                            @else
                                {{ $column['label'] }}
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($data as $row)
                    <tr class="hover:bg-gray-50">
                        @foreach($columns as $column)
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {!! $this->formatValue($row, $column) !!}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="px-6 py-12 text-center text-gray-500">
                            @if($search)
                                <i class="fas fa-search text-gray-300 text-4xl mb-4"></i>
                                <p>ไม่พบข้อมูลที่ค้นหา "{{ $search }}"</p>
                            @else
                                <i class="fas fa-inbox text-gray-300 text-4xl mb-4"></i>
                                <p>ไม่มีข้อมูล</p>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($data->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $data->links() }}
        </div>
    @endif
</div>
