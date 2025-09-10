<div x-data="{ 
    notifications: @entangle('notifications'),
    show: @entangle('show')
}" 
x-show="show" 
x-transition:enter="transition ease-out duration-300"
x-transition:enter-start="opacity-0 transform -translate-y-2"
x-transition:enter-end="opacity-100 transform translate-y-0"
x-transition:leave="transition ease-in duration-200"
x-transition:leave-start="opacity-100 transform translate-y-0"
x-transition:leave-end="opacity-0 transform -translate-y-2"
class="fixed top-4 right-4 z-50 space-y-2 max-w-sm">

    @foreach($notifications as $notification)
        <div x-data="{ show: true }" 
             x-show="show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-x-full"
             x-transition:enter-end="opacity-100 transform translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform translate-x-0"
             x-transition:leave-end="opacity-0 transform translate-x-full"
             @auto-remove-notification.window="
                if ($event.detail.id === '{{ $notification['id'] }}') {
                    setTimeout(() => {
                        show = false;
                        setTimeout(() => $wire.removeNotification('{{ $notification['id'] }}'), 200);
                    }, $event.detail.duration);
                }
             "
             class="rounded-lg border shadow-lg p-4 {{ $this->getColor($notification['type']) }}">
            
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="{{ $this->getIcon($notification['type']) }} text-lg"></i>
                </div>
                
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium">{{ $notification['message'] }}</p>
                    <p class="text-xs opacity-75 mt-1">{{ \Carbon\Carbon::parse($notification['timestamp'])->diffForHumans() }}</p>
                </div>
                
                <div class="ml-4 flex-shrink-0">
                    <button @click="show = false; setTimeout(() => $wire.removeNotification('{{ $notification['id'] }}'), 200)" 
                            class="inline-flex text-gray-400 hover:text-gray-600 focus:outline-none">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            </div>
        </div>
    @endforeach

    @if(count($notifications) > 1)
        <div class="text-center">
            <button wire:click="clearAll" 
                    class="text-xs text-gray-500 hover:text-gray-700 underline">
                ล้างทั้งหมด
            </button>
        </div>
    @endif
</div>

@script
<script>
// Helper functions สำหรับใช้ใน JavaScript
window.showNotification = function(type, message, duration = 5000) {
    Livewire.dispatch('showNotification', [type, message, duration]);
};

window.showSuccess = function(message, duration = 5000) {
    window.showNotification('success', message, duration);
};

window.showError = function(message, duration = 5000) {
    window.showNotification('error', message, duration);
};

window.showWarning = function(message, duration = 5000) {
    window.showNotification('warning', message, duration);
};

window.showInfo = function(message, duration = 5000) {
    window.showNotification('info', message, duration);
};
</script>
@endscript
