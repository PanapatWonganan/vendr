{{-- หัวข้อชิดซ้าย + ตัวกรองปีชิดขวาสุดของแถว (ตรงขอบขวาของ stat cards) แบบ responsive --}}
<div class="w-full flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="fi-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
        {{ $heading }}
    </h1>

    <div class="w-full sm:w-56 shrink-0 sm:ms-auto">
        {{ $filtersForm }}
    </div>
</div>
