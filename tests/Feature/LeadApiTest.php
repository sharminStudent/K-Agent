<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\ChatSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_a_lead_for_the_correct_agent_and_session(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Sales Agent',
            'company_name' => 'Acme',
            'widget_token' => 'acme-widget-token',
        ]);

        $chatSession = ChatSession::query()->create([
            'agent_id' => $agent->id,
            'visitor_name' => 'Prospect',
        ]);

        $response = $this->postJson('/api/lead/store', [
            'widget_token' => $agent->widget_token,
            'session_id' => $chatSession->public_id,
            'name' => 'Jane Prospect',
            'email' => 'jane@example.com',
            'phone' => '+97312345678',
            'notes' => 'Interested in pricing.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.agent_id', $agent->id)
            ->assertJsonPath('data.session_id', $chatSession->public_id)
            ->assertJsonPath('data.status', 'new');

        $this->assertDatabaseHas('leads', [
            'agent_id' => $agent->id,
            'chat_session_id' => $chatSession->id,
            'name' => 'Jane Prospect',
            'email' => 'jane@example.com',
            'phone' => '+97312345678',
            'status' => 'new',
        ]);
    }

    public function test_it_rejects_storing_a_lead_for_a_session_owned_by_another_agent(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Sales Agent',
            'company_name' => 'Acme',
            'widget_token' => 'acme-widget-token',
        ]);

        $otherAgent = Agent::query()->create([
            'name' => 'Other Agent',
            'company_name' => 'Globex',
            'widget_token' => 'globex-widget-token',
        ]);

        $chatSession = ChatSession::query()->create([
            'agent_id' => $agent->id,
            'visitor_name' => 'Prospect',
        ]);

        $response = $this->postJson('/api/lead/store', [
            'widget_token' => $otherAgent->widget_token,
            'session_id' => $chatSession->public_id,
            'name' => 'Wrong Company Lead',
        ]);

        $response->assertNotFound();

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_it_can_store_a_lead_without_a_session(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Sales Agent',
            'company_name' => 'Acme',
            'widget_token' => 'acme-widget-token',
        ]);

        $response = $this->postJson('/api/lead/store', [
            'widget_token' => $agent->widget_token,
            'name' => 'Walk-in Lead',
            'email' => 'walkin@example.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.agent_id', $agent->id)
            ->assertJsonPath('data.session_id', null);

        $this->assertDatabaseHas('leads', [
            'agent_id' => $agent->id,
            'chat_session_id' => null,
            'name' => 'Walk-in Lead',
            'email' => 'walkin@example.com',
            'status' => 'new',
        ]);
    }
}
