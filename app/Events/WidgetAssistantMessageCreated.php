<?php

namespace App\Events;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WidgetAssistantMessageCreated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public ChatSession $chatSession,
        public ChatMessage $assistantMessage,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new Channel('widget-chat.'.$this->chatSession->public_id)];
    }

    public function broadcastAs(): string
    {
        return 'widget.assistant-message';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->chatSession->public_id,
            'assistant_message' => [
                'message_id' => $this->assistantMessage->public_id,
                'role' => $this->assistantMessage->role,
                'content' => $this->assistantMessage->content,
                'created_at' => $this->assistantMessage->created_at?->toISOString(),
            ],
        ];
    }
}
