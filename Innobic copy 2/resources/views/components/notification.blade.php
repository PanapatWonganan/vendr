<div x-data="notification" 
     x-show="visible" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform translate-y-2"
     x-transition:enter-end="opacity-100 transform translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 transform translate-y-0"
     x-transition:leave-end="opacity-0 transform translate-y-2"
     class="fixed top-4 right-4 z-50 max-w-sm"
     style="display: none;">
    
    <div class="bg-white rounded-lg shadow-lg border-l-4 p-4"
         :class="{
             'border-green-500': type === 'success',
             'border-red-500': type === 'error', 
             'border-yellow-500': type === 'warning',
             'border-blue-500': type === 'info'
         }">
        
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i :class="{
                    'fas fa-check-circle text-green-500': type === 'success',
                    'fas fa-exclamation-circle text-red-500': type === 'error',
                    'fas fa-exclamation-triangle text-yellow-500': type === 'warning',
                    'fas fa-info-circle text-blue-500': type === 'info'
                }"></i>
            </div>
            
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium text-gray-900" x-text="message"></p>
            </div>
            
            <div class="ml-4 flex-shrink-0">
                <button @click="hide()" 
                        class="inline-flex text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 transition ease-in-out duration-150">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
</div> 