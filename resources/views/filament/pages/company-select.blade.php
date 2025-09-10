<x-filament-panels::page>
    <div class="max-w-2xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="text-center mb-6">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-primary-100 dark:bg-primary-900 mb-4">
                    <x-heroicon-o-building-office-2 class="h-8 w-8 text-primary-600 dark:text-primary-400" />
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                    ยินดีต้อนรับเข้าสู่ระบบ Innobic Procurement
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    กรุณาเลือกบริษัทที่ต้องการทำงานด้วย
                </p>
            </div>

            <form wire:submit="selectCompany" class="space-y-6">
                {{ $this->form }}
                
                @if($selectedCompany)
                    <div class="rounded-md bg-blue-50 dark:bg-blue-900/20 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <x-heroicon-s-information-circle class="h-5 w-5 text-blue-400" />
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                    {{ $selectedCompany->name }}
                                </h3>
                                <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                                    <p>{{ $selectedCompany->description }}</p>
                                    <p class="mt-1">
                                        <span class="font-medium">การเชื่อมต่อ:</span> 
                                        {{ $selectedCompany->database_connection }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="flex justify-end">
                    {{ $this->getFormActions()[0] }}
                </div>
            </form>
        </div>
    </div>
</x-filament-panels::page>