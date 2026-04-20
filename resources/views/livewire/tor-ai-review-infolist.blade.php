@php
    $torId = $getRecord()?->id;
@endphp
@if($torId)
    @livewire('tor-ai-review', ['torId' => $torId], key('tor-ai-review-' . $torId))
@endif
