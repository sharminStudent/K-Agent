<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\ChatSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_chat_session_for_a_valid_widget_token(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'K-Agent Demo',
            'widget_token' => 'demo-widget-token',
        ]);

        $response = $this->postJson('/api/chat/session', [
            'widget_token' => $agent->widget_token,
            'visitor_name' => 'Sharmin',
            'visitor_email' => 'sharmin@example.com',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['session_id', 'status', 'created_at'],
            ]);

        $this->assertDatabaseHas('chat_sessions', [
            'agent_id' => $agent->id,
            'visitor_name' => 'Sharmin',
            'visitor_email' => 'sharmin@example.com',
            'status' => 'active',
        ]);
    }

    public function test_it_stores_a_user_message_for_an_existing_chat_session(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'K-Agent Demo',
            'widget_token' => 'demo-widget-token',
        ]);

        $chatSession = ChatSession::query()->create([
            'agent_id' => $agent->id,
            'visitor_name' => 'Visitor',
        ]);

        $response = $this->postJson('/api/chat/send-message', [
            'widget_token' => $agent->widget_token,
            'session_id' => $chatSession->public_id,
            'message' => 'Hello, I need help.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.session_id', $chatSession->public_id)
            ->assertJsonPath('data.role', 'user')
            ->assertJsonPath('data.content', 'Hello, I need help.');

        $this->assertDatabaseHas('chat_messages', [
            'agent_id' => $agent->id,
            'chat_session_id' => $chatSession->id,
            'role' => 'user',
            'content' => 'Hello, I need help.',
        ]);

        $this->assertNotNull($chatSession->fresh()->last_message_at);
    }

    public function test_it_rejects_message_storage_for_the_wrong_widget_token(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'K-Agent Demo',
            'widget_token' => 'demo-widget-token',
        ]);

        $otherAgent = Agent::query()->create([
            'name' => 'Other Agent',
            'company_name' => 'Other Demo',
            'widget_token' => 'other-widget-token',
        ]);

        $chatSession = ChatSession::query()->create([
            'agent_id' => $agent->id,
            'visitor_name' => 'Visitor',
        ]);

        $response = $this->postJson('/api/chat/send-message', [
            'widget_token' => $otherAgent->widget_token,
            'session_id' => $chatSession->public_id,
            'message' => 'This should fail.',
        ]);

        $response->assertNotFound();

        $this->assertDatabaseCount('chat_messages', 0);
    }
}
