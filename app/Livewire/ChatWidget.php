<?php

namespace App\Livewire;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\AiChatService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ChatWidget extends Component
{
    public bool $isOpen = false;
    public string $message = '';
    public bool $isLoading = false;
    public ?int $conversationId = null;
    public array $messages = [];

    public function mount(): void
    {
        if (!Auth::check() || !session('company_id')) {
            return;
        }

        $this->loadOrCreateConversation();
    }

    public function toggleChat(): void
    {
        $this->isOpen = !$this->isOpen;
    }

    public function sendMessage(): void
    {
        $text = trim($this->message);
        if ($text === '' || $this->isLoading) {
            return;
        }

        $this->isLoading = true;
        $this->message = '';

        // Save user message
        $userMsg = ChatMessage::create([
            'conversation_id' => $this->conversationId,
            'role' => 'user',
            'content' => $text,
        ]);

        $this->messages[] = [
            'role' => 'user',
            'content' => $text,
        ];

        // Update conversation title from first message
        $conversation = ChatConversation::find($this->conversationId);
        if ($conversation && !$conversation->title) {
            $conversation->update(['title' => mb_substr($text, 0, 50)]);
        }

        // Build history for context
        $history = collect($this->messages)->map(fn($m) => [
            'role' => $m['role'],
            'content' => $m['content'],
        ])->toArray();

        // Call AI
        $chatService = app(AiChatService::class);
        $response = $chatService->chat($text, array_slice($history, 0, -1));

        // Save assistant message
        ChatMessage::create([
            'conversation_id' => $this->conversationId,
            'role' => 'assistant',
            'content' => $response['content'],
            'metadata' => $response['metadata'] ?? null,
        ]);

        $this->messages[] = [
            'role' => 'assistant',
            'content' => $response['content'],
        ];

        $conversation?->update(['last_message_at' => now()]);

        $this->isLoading = false;

        $this->dispatch('chat-updated');
    }

    public function newConversation(): void
    {
        $conversation = ChatConversation::create([
            'user_id' => Auth::id(),
            'company_id' => session('company_id'),
        ]);

        $this->conversationId = $conversation->id;
        $this->messages = [];
    }

    public function clearHistory(): void
    {
        if ($this->conversationId) {
            ChatMessage::where('conversation_id', $this->conversationId)->delete();
            $this->messages = [];
        }
    }

    private function loadOrCreateConversation(): void
    {
        $conversation = ChatConversation::where('user_id', Auth::id())
            ->where('company_id', session('company_id'))
            ->latest('last_message_at')
            ->first();

        if (!$conversation) {
            $conversation = ChatConversation::create([
                'user_id' => Auth::id(),
                'company_id' => session('company_id'),
            ]);
        }

        $this->conversationId = $conversation->id;

        $this->messages = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->oldest()
            ->limit(50)
            ->get(['role', 'content'])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.chat-widget');
    }
}
