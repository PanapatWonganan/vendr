<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Report Form -->
        {{ $this->form }}

        <!-- Report Results -->
        @if(!empty($reportData))
            <x-filament::section>
                <x-slot name="heading">
                    Report Results - {{ ucfirst(str_replace('_', ' ', $reportType)) }}
                </x-slot>

                <div class="space-y-6">
                    @if($reportType === 'pr_summary')
                        <!-- PR Summary Results -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                                <h4 class="text-lg font-medium text-blue-900 dark:text-blue-100">Total PRs</h4>
                                <p class="text-2xl font-bold text-blue-600">{{ number_format($reportData['total_count']) }}</p>
                            </div>
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                                <h4 class="text-lg font-medium text-green-900 dark:text-green-100">Total Amount</h4>
                                <p class="text-2xl font-bold text-green-600">฿{{ number_format($reportData['total_amount'], 2) }}</p>
                            </div>
                        </div>

                        <!-- Status Breakdown -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <h4 class="text-lg font-medium mb-4">By Status</h4>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead>
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Count</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($reportData['by_status'] as $status)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ ucfirst($status->status) }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ number_format($status->count) }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">฿{{ number_format($status->amount, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    @elseif($reportType === 'vendor_performance')
                        <!-- Vendor Performance Results -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <h4 class="text-lg font-medium mb-4">Vendor Performance</h4>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead>
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Vendor</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Orders</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Value</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Avg Order Value</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Success Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($reportData as $vendor)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $vendor['vendor_name'] }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ number_format($vendor['total_orders']) }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">฿{{ number_format($vendor['total_value'], 2) }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">฿{{ number_format($vendor['avg_order_value'], 2) }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                    {{ $vendor['total_orders'] > 0 ? round(($vendor['completed_orders'] / $vendor['total_orders']) * 100, 1) : 0 }}%
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    @elseif($reportType === 'department_spending')
                        <!-- Department Spending Results -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <h4 class="text-lg font-medium mb-4">Department Spending Analysis</h4>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead>
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Department</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Orders</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Spending</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Avg Order Value</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($reportData as $dept)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $dept['department_name'] }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ number_format($dept['total_orders']) }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">฿{{ number_format($dept['total_spending'], 2) }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">฿{{ number_format($dept['avg_order_value'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    @elseif($reportType === 'approval_efficiency')
                        <!-- Approval Efficiency Results -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                                <h4 class="text-lg font-medium text-blue-900 dark:text-blue-100">PR Approval Efficiency</h4>
                                <div class="mt-2 space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-blue-700 dark:text-blue-300">Total Approved:</span>
                                        <span class="font-medium">{{ number_format($reportData['pr_efficiency']['total_approved']) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-blue-700 dark:text-blue-300">Avg Approval Time:</span>
                                        <span class="font-medium">{{ round($reportData['pr_efficiency']['avg_approval_hours'], 1) }} hours</span>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                                <h4 class="text-lg font-medium text-green-900 dark:text-green-100">PO Approval Efficiency</h4>
                                <div class="mt-2 space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-green-700 dark:text-green-300">Total Approved:</span>
                                        <span class="font-medium">{{ number_format($reportData['po_efficiency']['total_approved']) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-green-700 dark:text-green-300">Avg Approval Time:</span>
                                        <span class="font-medium">{{ round($reportData['po_efficiency']['avg_approval_hours'], 1) }} hours</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    @else
                        <!-- Generic Results Display -->
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <pre class="text-sm">{{ json_encode($reportData, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>