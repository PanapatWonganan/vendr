<div
    x-data="{ open: @entangle('isOpen') }"
    style="position:fixed;bottom:24px;right:24px;z-index:99999;font-family:'Sarabun',sans-serif;"
>
    {{-- Chat Panel --}}
    <div
        x-show="open"
        x-transition
        style="margin-bottom:16px;width:380px;height:520px;background:#fff;border-radius:16px;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);border:1px solid #e5e7eb;display:flex;flex-direction:column;overflow:hidden;"
    >
        {{-- Header --}}
        <div style="background:linear-gradient(to right,#9333ea,#7e22ce);padding:12px 16px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:32px;height:32px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <svg style="width:20px;height:20px;color:#fff;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
                    </svg>
                </div>
                <div>
                    <h3 style="color:#fff;font-weight:600;font-size:14px;margin:0;">VENDR Assistant</h3>
                    <p style="color:#d8b4fe;font-size:12px;margin:0;">AI Procurement Support</p>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:4px;">
                <button
                    wire:click="newConversation"
                    style="padding:6px;color:rgba(255,255,255,0.7);background:none;border:none;border-radius:8px;cursor:pointer;"
                    title="สนทนาใหม่"
                >
                    <svg style="width:16px;height:16px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </button>
                <button
                    wire:click="clearHistory"
                    style="padding:6px;color:rgba(255,255,255,0.7);background:none;border:none;border-radius:8px;cursor:pointer;"
                    title="ล้างประวัติ"
                >
                    <svg style="width:16px;height:16px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                </button>
                <button
                    @click="open = false"
                    style="padding:6px;color:rgba(255,255,255,0.7);background:none;border:none;border-radius:8px;cursor:pointer;"
                >
                    <svg style="width:16px;height:16px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Messages Area --}}
        <div
            style="flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:12px;"
            id="chat-messages"
            x-ref="chatMessages"
        >
            @if(count($messages) === 0)
                <div style="text-align:center;padding:32px 0;">
                    <div style="width:64px;height:64px;background:#f3e8ff;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                        <svg style="width:32px;height:32px;color:#a855f7;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                        </svg>
                    </div>
                    <p style="color:#6b7280;font-size:14px;font-weight:500;margin:0;">VENDR Assistant</p>
                    <p style="color:#9ca3af;font-size:12px;margin:4px 0 0;">ถามอะไรก็ได้เกี่ยวกับระบบจัดซื้อ</p>
                    <div style="margin-top:16px;display:flex;flex-direction:column;gap:8px;">
                        <button wire:click="$set('message', 'PR ของฉันถึงไหนแล้ว?')" style="display:block;width:100%;text-align:left;font-size:12px;background:#f9fafb;color:#4b5563;border-radius:8px;padding:8px 12px;border:none;cursor:pointer;">
                            PR ของฉันถึงไหนแล้ว?
                        </button>
                        <button wire:click="$set('message', 'งบประมาณแผนกเดือนนี้เหลือเท่าไหร่?')" style="display:block;width:100%;text-align:left;font-size:12px;background:#f9fafb;color:#4b5563;border-radius:8px;padding:8px 12px;border:none;cursor:pointer;">
                            งบประมาณแผนกเดือนนี้เหลือเท่าไหร่?
                        </button>
                        <button wire:click="$set('message', 'วิธีสร้าง PR ใหม่ทำยังไง?')" style="display:block;width:100%;text-align:left;font-size:12px;background:#f9fafb;color:#4b5563;border-radius:8px;padding:8px 12px;border:none;cursor:pointer;">
                            วิธีสร้าง PR ใหม่ทำยังไง?
                        </button>
                    </div>
                </div>
            @else
                @foreach($messages as $msg)
                    @if($msg['role'] === 'user')
                        <div style="display:flex;justify-content:flex-end;">
                            <div style="background:#9333ea;color:#fff;border-radius:16px 16px 4px 16px;padding:8px 16px;max-width:80%;font-size:14px;">
                                {{ $msg['content'] }}
                            </div>
                        </div>
                    @else
                        <div style="display:flex;justify-content:flex-start;">
                            <div style="background:#f3f4f6;color:#1f2937;border-radius:16px 16px 16px 4px;padding:8px 16px;max-width:80%;font-size:14px;white-space:pre-wrap;">
                                {{ $msg['content'] }}
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif

            {{-- Loading indicator --}}
            @if($isLoading)
                <div style="display:flex;justify-content:flex-start;">
                    <div style="background:#f3f4f6;border-radius:16px 16px 16px 4px;padding:12px 16px;">
                        <div style="display:flex;align-items:center;gap:6px;">
                            <div style="width:8px;height:8px;background:#a855f7;border-radius:50%;animation:chatBounce 1s infinite;"></div>
                            <div style="width:8px;height:8px;background:#a855f7;border-radius:50%;animation:chatBounce 1s infinite 0.15s;"></div>
                            <div style="width:8px;height:8px;background:#a855f7;border-radius:50%;animation:chatBounce 1s infinite 0.3s;"></div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Input Area --}}
        <div style="border-top:1px solid #e5e7eb;padding:12px;flex-shrink:0;">
            <form wire:submit.prevent="sendMessage" style="display:flex;align-items:center;gap:8px;">
                <input
                    type="text"
                    wire:model="message"
                    placeholder="พิมพ์ข้อความ..."
                    style="flex:1;font-size:14px;border:1px solid #d1d5db;border-radius:12px;padding:10px 16px;outline:none;background:#fff;color:#111827;"
                    @if($isLoading) disabled @endif
                    autocomplete="off"
                >
                <button
                    type="submit"
                    style="background:#9333ea;color:#fff;border:none;border-radius:12px;padding:10px;cursor:pointer;flex-shrink:0;"
                    @if($isLoading) disabled @endif
                >
                    <svg style="width:16px;height:16px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <style>
        @keyframes chatBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
    </style>

    {{-- Floating Button --}}
    <button
        @click="open = !open"
        x-show="!open"
        style="width:56px;height:56px;background:linear-gradient(to right,#9333ea,#7e22ce);color:#fff;border:none;border-radius:50%;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);cursor:pointer;display:flex;align-items:center;justify-content:center;"
    >
        <svg style="width:24px;height:24px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
        </svg>
    </button>
</div>

@script
<script>
    $wire.on('chat-updated', () => {
        setTimeout(() => {
            const el = document.getElementById('chat-messages');
            if (el) el.scrollTop = el.scrollHeight;
        }, 100);
    });
</script>
@endscript

