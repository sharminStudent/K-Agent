<?php

namespace Tests\Feature;

use App\Mail\LeadCapturedMail;
use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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

    public function test_it_sends_a_notification_email_when_lead_notifications_are_enabled(): void
    {
        Mail::fake();

        $agent = Agent::query()->create([
            'name' => 'Sales Agent',
            'company_name' => 'Acme',
            'widget_token' => 'acme-widget-token',
            'settings' => [
                'notifications' => [
                    'lead_capture' => [
                        'enabled' => true,
                        'email' => 'leads@acme.com',
                    ],
                ],
            ],
        ]);

        $this->postJson('/api/lead/store', [
            'widget_token' => $agent->widget_token,
            'name' => 'Jane Prospect',
            'email' => 'jane@example.com',
        ])->assertCreated();

        Mail::assertSent(LeadCapturedMail::class, function (LeadCapturedMail $mail): bool {
            return $mail->hasTo('leads@acme.com')
                && $mail->lead->name === 'Jane Prospect'
                && $mail->lead->email === 'jane@example.com';
        });
    }

    public function test_it_creates_an_in_app_notification_for_workspace_users_when_a_lead_is_captured(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Sales Agent',
            'company_name' => 'Acme',
            'widget_token' => 'acme-widget-token',
        ]);

        $user = User::factory()->create([
            'agent_id' => $agent->id,
            'is_super_admin' => false,
            'is_active' => true,
        ]);

        $this->postJson('/api/lead/store', [
            'widget_token' => $agent->widget_token,
            'name' => 'Jane Prospect',
            'email' => 'jane@example.com',
        ])->assertCreated();

        $notification = $user->fresh()->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('lead_captured', $notification->data['type'] ?? null);
        $this->assertSame('Jane Prospect', $notification->data['lead_name'] ?? null);
        $this->assertSame('/admin/leads', $notification->data['url'] ?? null);
    }

    public function test_it_records_an_activity_log_when_a_lead_is_captured(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Sales Agent',
            'company_name' => 'Acme',
            'widget_token' => 'acme-widget-token',
        ]);

        $this->postJson('/api/lead/store', [
            'widget_token' => $agent->widget_token,
            'name' => 'Jane Prospect',
            'email' => 'jane@example.com',
        ])->assertCreated();

        $this->assertDatabaseHas('activity_logs', [
            'agent_id' => $agent->id,
            'category' => 'system',
            'event' => 'lead.captured',
        ]);

        $log = ActivityLog::query()->where('agent_id', $agent->id)->latest()->first();

        $this->assertNotNull($log);
        $this->assertSame('A new lead was captured from the widget.', $log->description);
    }
}
