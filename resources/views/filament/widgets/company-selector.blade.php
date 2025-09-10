<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <x-heroicon-o-building-office-2 class="h-5 w-5 text-gray-500" />
                บริษัทที่ทำงานอยู่
            </div>
        </x-slot>

        <div class="space-y-3">
            @if($currentCompany)
                <div class="flex items-center justify-between p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg border border-primary-200 dark:border-primary-800">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900">
                            <x-heroicon-s-building-office class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">
                                {{ $currentCompany->display_name }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $currentCompany->name }}
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                            เชื่อมต่อแล้ว
                        </span>
                    </div>
                </div>
                
                @if($companies->count() > 1)
                    <details class="group">
                        <summary class="flex cursor-pointer items-center gap-2 text-sm font-medium text-gray-700 hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400">
                            <x-heroicon-o-arrow-path class="h-4 w-4" />
                            เปลี่ยนบริษัท
                            <x-heroicon-s-chevron-down class="h-4 w-4 transition-transform group-open:rotate-180" />
                        </summary>
                        
                        <div class="mt-3 space-y-2">
                            @foreach($companies as $company)
                                @if($company->id !== $currentCompany->id)
                                    <button 
                                        wire:click="switchCompany({{ $company->id }})"
                                        class="flex w-full items-center gap-3 rounded-lg border border-gray-200 p-3 text-left transition-colors hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                                    >
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                                            <x-heroicon-o-building-office class="h-4 w-4 text-gray-600 dark:text-gray-400" />
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $company->display_name }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $company->name }}
                                            </div>
                                        </div>
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </details>
                @endif
            @else
                <div class="text-center py-6">
                    <x-heroicon-o-exclamation-triangle class="mx-auto h-12 w-12 text-yellow-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                        ยังไม่ได้เลือกบริษัท
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        กรุณาเลือกบริษัทที่ต้องการทำงานด้วย
                    </p>
                    <div class="mt-6">
                        <a 
                            href="/admin/company-select" 
                            class="inline-flex items-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
                        >
                            <x-heroicon-o-plus class="mr-1.5 h-4 w-4" />
                            เลือกบริษัท
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>