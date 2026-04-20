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
                                    {{ $selectedCompany->display_name }}
                                </h3>
                                @if($selectedCompany->description)
                                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                                        <p>{{ $selectedCompany->description }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <div class="flex justify-end">
                    {{ $this->getFormActions()[0] }}
                </div>
            </form>

            {{-- Logout link --}}
            <div class="mt-6 text-center border-t border-gray-200 dark:border-gray-700 pt-4">
                <button wire:click="logout" type="button"
                        class="text-sm text-gray-500 hover:text-danger-600 dark:text-gray-400 dark:hover:text-danger-400 transition-colors">
                    <x-heroicon-m-arrow-left-on-rectangle class="w-4 h-4 inline-block mr-1" />
                    ออกจากระบบ
                </button>
            </div>
        </div>
    </div>
</x-filament-panels::page>