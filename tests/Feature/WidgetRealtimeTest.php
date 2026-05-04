<?php

namespace Tests\Feature;

use App\Events\WidgetAssistantMessageCreated;
use App\Models\Agent;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Services\WidgetRealtimeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WidgetRealtimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_realtime_event_payload_includes_chunk_metadata(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'Acme Demo',
            'widget_token' => 'demo-widget-token',
        ]);

        $session = ChatSession::query()->create([
            'agent_id' => $agent->id,
        ]);

        $assistantMessage = ChatMessage::query()->create([
            'agent_id' => $agent->id,
            'chat_session_id' => $session->id,
            'role' => 'assistant',
            'content' => 'Acme onboarding takes two business days.',
            'meta' => [
                'auto_close' => false,
            ],
        ]);

        $event = new WidgetAssistantMessageCreated(
            chatSession: $session,
            assistantMessage: $assistantMessage,
            streamedContent: 'Acme onboarding takes two',
            chunkIndex: 0,
            chunkCount: 2,
            isFinalChunk: false,
        );

        $payload = $event->broadcastWith();

        $this->assertSame($session->public_id, $payload['session_id']);
        $this->assertSame($assistantMessage->public_id, $payload['assistant_message']['message_id']);
        $this->assertSame('Acme onboarding takes two', $payload['assistant_message']['content']);
        $this->assertSame(0, $payload['assistant_message']['meta']['stream']['chunk_index']);
        $this->assertSame(2, $payload['assistant_message']['meta']['stream']['chunk_count']);
        $this->assertFalse($payload['assistant_message']['meta']['stream']['is_final']);
    }

    public function test_widget_realtime_service_splits_long_messages_into_multiple_chunks(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'Acme Demo',
            'widget_token' => 'demo-widget-token',
        ]);

        $session = ChatSession::query()->create([
            'agent_id' => $agent->id,
        ]);

        $assistantMessage = ChatMessage::query()->create([
            'agent_id' => $agent->id,
            'chat_session_id' => $session->id,
            'role' => 'assistant',
            'content' => 'This is a long assistant answer that should be broadcast over several widget chunks so the frontend can render it progressively instead of waiting for one final payload.',
        ]);

        $service = new class extends WidgetRealtimeService
        {
            public function exposeChunks(string $content): array
            {
                return $this->chunkAssistantMessage($content);
            }
        };

        $chunks = $service->exposeChunks($assistantMessage->content);

        $this->assertGreaterThan(1, count($chunks));
        $this->assertSame($assistantMessage->content, implode(' ', $chunks));
    }
}
