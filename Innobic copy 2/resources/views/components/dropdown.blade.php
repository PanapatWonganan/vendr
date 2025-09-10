@props(['align' => 'right', 'width' => '48'])

@php
$alignmentClasses = match($align) {
    'left' => 'origin-top-left left-0',
    'top' => 'origin-top',
    default => 'origin-top-right right-0',
};

$widthClasses = match($width) {
    '48' => 'w-48',
    '56' => 'w-56',
    '64' => 'w-64',
    '72' => 'w-72',
    default => $width,
};
@endphp

<div x-data="dropdown" class="relative inline-block text-left">
    <!-- Trigger -->
    <div @click="toggle()">
        {{ $trigger }}
    </div>

    <!-- Dropdown Menu -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95"
         @click.outside="close()"
         @keydown.escape.window="close()"
         class="absolute z-50 mt-2 {{ $widthClasses }} rounded-lg bg-white py-2 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none {{ $alignmentClasses }}"
         style="display: none;">
        
        {{ $slot }}
    </div>
</div>
