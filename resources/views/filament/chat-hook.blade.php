@auth
    @if(session('company_id'))
        @livewire('chat-widget')
    @endif
@endauth
