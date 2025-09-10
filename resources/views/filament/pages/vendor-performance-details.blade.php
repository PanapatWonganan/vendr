<div class="space-y-6">
    {{-- Vendor Information --}}
    <div class="bg-white rounded-lg border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Vendor Information</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <span class="text-sm font-medium text-gray-500">Company Name:</span>
                <p class="text-sm text-gray-900">{{ $vendor->company_name }}</p>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-500">Tax ID:</span>
                <p class="text-sm text-gray-900">{{ $vendor->tax_id }}</p>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-500">Work Category:</span>
                <p class="text-sm text-gray-900">{{ $vendor->work_category }}</p>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-500">Contact:</span>
                <p class="text-sm text-gray-900">{{ $vendor->contact_name }} ({{ $vendor->contact_phone }})</p>
            </div>
        </div>
    </div>

    {{-- Current Score --}}
    @if($score)
    <div class="bg-white rounded-lg border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Current Performance Score</h3>
        <div class="grid grid-cols-4 gap-4">
            <div class="text-center">
                <div class="text-3xl font-bold 
                    @if($score->current_score >= 3.5) text-green-600 
                    @elseif($score->current_score >= 2.5) text-yellow-600 
                    @else text-red-600 @endif">
                    {{ number_format($score->current_score, 2) }}
                </div>
                <p class="text-sm text-gray-500">Overall Score</p>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold 
                    @if($score->current_grade === 'A') text-green-600 
                    @elseif($score->current_grade === 'B') text-blue-600 
                    @elseif($score->current_grade === 'C') text-yellow-600 
                    @else text-red-600 @endif">
                    {{ $score->current_grade }}
                </div>
                <p class="text-sm text-gray-500">Current Grade</p>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-900">{{ $score->evaluation_count }}</div>
                <p class="text-sm text-gray-500">Total Evaluations</p>
            </div>
            <div class="text-center">
                <div class="text-sm text-gray-600">{{ $score->updated_at->format('d/m/Y') }}</div>
                <p class="text-sm text-gray-500">Last Updated</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Recent Evaluations --}}
    @if($evaluations->count() > 0)
    <div class="bg-white rounded-lg border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Evaluations</h3>
        <div class="overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overall Score</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items Count</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($evaluations as $evaluation)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $evaluation->evaluation_date->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $evaluation->overall_score ? number_format($evaluation->overall_score, 1) . '%' : 'N/A' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ $evaluation->score_grade }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $evaluation->evaluationItems->count() }} items
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="bg-white rounded-lg border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Evaluations</h3>
        <p class="text-gray-500">No evaluations found for this vendor.</p>
    </div>
    @endif

    {{-- Performance Trends --}}
    @if($evaluations->count() > 1)
    <div class="bg-white rounded-lg border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance Trends</h3>
        <div class="grid grid-cols-2 gap-4">
            <div class="text-center">
                <div class="text-lg font-semibold text-blue-600">
                    {{ $evaluations->whereNotNull('overall_score')->count() > 0 ? number_format($evaluations->whereNotNull('overall_score')->avg('overall_score'), 1) . '%' : 'N/A' }}
                </div>
                <p class="text-sm text-gray-500">Avg Overall Score</p>
            </div>
            <div class="text-center">
                <div class="text-lg font-semibold text-green-600">
                    {{ $evaluations->sum('applicable_criteria') }}
                </div>
                <p class="text-sm text-gray-500">Total Items Evaluated</p>
            </div>
        </div>
    </div>
    @endif
</div>