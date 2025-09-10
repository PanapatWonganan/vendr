<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Procurement Reports & Analytics
        </x-slot>
        
        @if(!$hasData)
            <div class="text-center py-8">
                <div class="mx-auto h-12 w-12 text-gray-400">
                    <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $message }}</h3>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Monthly Trends -->
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Monthly Procurement Trends</h4>
                    <div class="space-y-2">
                        @foreach($monthlyData as $month)
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $month['month'] }}</span>
                                <div class="flex space-x-4 text-sm">
                                    <span class="text-blue-600">PR: {{ $month['pr_count'] }}</span>
                                    <span class="text-green-600">PO: {{ $month['po_count'] }}</span>
                                    <span class="text-purple-600">฿{{ number_format($month['po_value']) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Department Spending -->
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Top Spending Departments (This Year)</h4>
                    <div class="space-y-2">
                        @forelse($departmentSpending as $dept)
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $dept->department_name }}</span>
                                <div class="flex space-x-2 text-sm">
                                    <span class="text-orange-600">{{ $dept->order_count }} orders</span>
                                    <span class="text-green-600">฿{{ number_format($dept->total_spent) }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No data available</p>
                        @endforelse
                    </div>
                </div>

                <!-- Approval Efficiency -->
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Approval Efficiency</h4>
                    @if($avgApprovalTime && $avgApprovalTime->total_approved > 0)
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Average Approval Time:</span>
                                <span class="text-sm font-medium text-blue-600">{{ round($avgApprovalTime->avg_hours, 1) }} hours</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Total Approved:</span>
                                <span class="text-sm font-medium text-green-600">{{ $avgApprovalTime->total_approved }} PRs</span>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">No approval data available</p>
                    @endif
                </div>

                <!-- Vendor Performance -->
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Top Vendors (This Year)</h4>
                    <div class="space-y-2">
                        @forelse($vendorPerformance as $vendor)
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ Str::limit($vendor->vendor_name, 20) }}</span>
                                <div class="flex space-x-2 text-sm">
                                    <span class="text-blue-600">{{ $vendor->order_count }} POs</span>
                                    <span class="text-green-600">฿{{ number_format($vendor->total_value) }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No vendor data available</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>