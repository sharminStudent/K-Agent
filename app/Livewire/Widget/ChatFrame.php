<?php

namespace App\Livewire\Widget;

use App\Models\Agent;
use App\Models\ChatSession;
use App\Services\ChatService;
use Livewire\Component;

class ChatFrame extends Component
{
    public Agent $agent;

    public string $bootstrapUrl;

    public string $helpUrl;

    public string $helpArticleBaseUrl;

    public string $lightLogoUrl;

    public string $darkLogoUrl;

    public function mount(
        Agent $agent,
        string $bootstrapUrl,
        string $helpUrl,
        string $helpArticleBaseUrl,
        string $lightLogoUrl,
        string $darkLogoUrl,
    ): void {
        $this->agent = $agent;
        $this->bootstrapUrl = $bootstrapUrl;
        $this->helpUrl = $helpUrl;
        $this->helpArticleBaseUrl = $helpArticleBaseUrl;
        $this->lightLogoUrl = $lightLogoUrl;
        $this->darkLogoUrl = $darkLogoUrl;
    }

    /**
     * @return array{session_id: string}
     */
    public function ensureSession(?string $sessionId = null): array
    {
        if (filled($sessionId)) {
            return [
                'session_id' => $this->resolveSession($sessionId)->public_id,
            ];
        }

        return [
            'session_id' => app(ChatService::class)->createSession([
                'widget_token' => $this->agent->widget_token,
            ])->public_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function sendMessage(string $message, string $sessionId): array
    {
        $message = trim($message);
        abort_if($message === '', 422, 'A message is required.');

        $this->resolveSession($sessionId);

        [$chatSession, $userMessage, $assistantMessage] = app(ChatService::class)->storeVisitorMessage([
            'widget_token' => $this->agent->widget_token,
            'session_id' => $sessionId,
            'message' => $message,
        ]);

        return [
            'session_id' => $chatSession->public_id,
            'message' => [
                'message_id' => $userMessage->public_id,
                'role' => $userMessage->role,
                'content' => $userMessage->content,
                'created_at' => $userMessage->created_at?->toISOString(),
            ],
            'assistant_message' => [
                'message_id' => $assistantMessage->public_id,
                'role' => $assistantMessage->role,
                'content' => $assistantMessage->content,
                'created_at' => $assistantMessage->created_at?->toISOString(),
            ],
        ];
    }

    public function render()
    {
        return view('livewire.widget.chat-frame');
    }

    protected function resolveSession(string $sessionId): ChatSession
    {
        return ChatSession::query()
            ->where('public_id', $sessionId)
            ->where('agent_id', $this->agent->id)
            ->firstOrFail();
    }
}
