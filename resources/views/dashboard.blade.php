@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-100">
    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Dashboard</h1>
            <p class="text-gray-600">ภาพรวมระบบจัดซื้อจัดจ้างและการประเมินผู้ขาย</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Purchase Orders -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">ใบสั่งซื้อทั้งหมด</h3>
                        <p class="text-2xl font-bold text-gray-800">{{ number_format($totalPOs) }}</p>
                    </div>
                </div>
            </div>

            <!-- Pending Approvals -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">รออนุมัติ</h3>
                        <p class="text-2xl font-bold text-gray-800">{{ number_format($pendingApprovalPOs) }}</p>
                    </div>
                </div>
            </div>

            <!-- Purchase Requisitions -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">ใบขอซื้อทั้งหมด</h3>
                        <p class="text-2xl font-bold text-gray-800">{{ number_format($totalPRs) }}</p>
                    </div>
                </div>
            </div>

            <!-- Vendors Evaluated -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">ผู้ขายที่ประเมินแล้ว</h3>
                        <p class="text-2xl font-bold text-gray-800">{{ number_format($vendorStats['totalVendorsEvaluated']) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vendor Performance Section -->
        @if($vendorStats['totalVendorsEvaluated'] > 0)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Grade Distribution Chart -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">การกระจายเกรดผู้ขาย</h3>
                <div class="relative">
                    <canvas id="vendorGradeChart" width="400" height="300"></canvas>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                    @foreach($vendorStats['gradeDistribution'] as $grade => $data)
                    <div class="flex justify-between items-center p-2 rounded @if($grade === 'A') bg-green-50 @elseif($grade === 'B') bg-blue-50 @elseif($grade === 'C') bg-yellow-50 @else bg-red-50 @endif">
                        <span class="font-medium grade-{{ strtolower($grade) }}">เกรด {{ $grade }}</span>
                        <span class="font-bold">{{ $data['percentage'] }}%</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Top Performers -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">ผู้ขายที่มีผลงานดีเด่น</h3>
                @if(count($vendorStats['topPerformers']) > 0)
                <div class="space-y-3">
                    @foreach($vendorStats['topPerformers'] as $performer)
                    <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-800">{{ $performer['vendor_name'] }}</p>
                            <p class="text-sm text-gray-600">{{ $performer['evaluation_count'] }} การประเมิน</p>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                เกรด {{ $performer['grade'] }}
                            </span>
                            <p class="text-sm text-gray-600 mt-1">{{ number_format($performer['score'], 2) }}/4.00</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-gray-500 text-center py-4">ยังไม่มีผู้ขายเกรด A</p>
                @endif
            </div>
        </div>

        <!-- Needs Improvement Section -->
        @if(count($vendorStats['needsImprovement']) > 0)
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">ผู้ขายที่ควรปรับปรุง</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($vendorStats['needsImprovement'] as $vendor)
                <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-800">{{ $vendor['vendor_name'] }}</p>
                        <p class="text-sm text-gray-600">{{ $vendor['evaluation_count'] }} การประเมิน</p>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium @if($vendor['grade'] === 'C') bg-yellow-100 text-yellow-800 @else bg-red-100 text-red-800 @endif">
                            เกรด {{ $vendor['grade'] }}
                        </span>
                        <p class="text-sm text-gray-600 mt-1">{{ number_format($vendor['score'], 2) }}/4.00</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        @endif

        <!-- Summary Stats -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <p class="text-2xl font-bold text-blue-600">{{ number_format($vendorStats['averageScore'], 2) }}/4.00</p>
                    <p class="text-sm text-gray-600">คะแนนเฉลี่ยของผู้ขาย</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-green-600">{{ number_format($pendingPRs) }}</p>
                    <p class="text-sm text-gray-600">PR รออนุมัติ</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-purple-600">{{ number_format($completedPRs) }}</p>
                    <p class="text-sm text-gray-600">PR เสร็จสิ้นแล้ว</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if($vendorStats['totalVendorsEvaluated'] > 0)
    // Vendor Grade Distribution Chart
    const ctx = document.getElementById('vendorGradeChart').getContext('2d');
    
    const gradeData = @json($vendorStats['gradeDistribution']);
    const labels = Object.keys(gradeData);
    const data = labels.map(grade => gradeData[grade].percentage);
    const colors = {
        'A': '#10b981', // Green
        'B': '#3b82f6', // Blue  
        'C': '#f59e0b', // Yellow
        'D': '#ef4444'  // Red
    };
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels.map(grade => `เกรด ${grade}`),
            datasets: [{
                data: data,
                backgroundColor: labels.map(grade => colors[grade]),
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        font: {
                            family: 'Sarabun',
                            size: 14
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            const count = gradeData[labels[context.dataIndex]].count;
                            return `${label}: ${value}% (${count} ราย)`;
                        }
                    }
                }
            }
        }
    });
    @endif
});
</script>
@endpush

<style>
.grade-a { color: #10b981; }
.grade-b { color: #3b82f6; }
.grade-c { color: #f59e0b; }
.grade-d { color: #ef4444; }
</style>
@endsection  