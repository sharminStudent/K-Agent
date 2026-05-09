<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\ActivityLog;
use App\Models\PaymentRecord;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_account_is_created_by_migration(): void
    {
        $user = User::query()->where('email', 'super@agent.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->isSuperAdmin());
    }

    public function test_guest_is_redirected_to_the_shared_admin_login(): void
    {
        $this->get('/super-admin')->assertRedirect('/super-admin/login');
    }

    public function test_super_admin_can_view_global_pages_from_the_super_admin_panel(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Acme Assistant',
            'company_name' => 'Acme Demo',
            'widget_token' => 'demo-widget-token',
        ]);

        $client = User::factory()->create([
            'agent_id' => $agent->id,
        ]);

        $superAdmin = User::query()->where('email', 'super@agent.com')->firstOrFail();

        foreach ([
            '/super-admin',
            '/super-admin/clients',
            '/super-admin/workspace-users',
            '/super-admin/admins',
            '/super-admin/agent-settings',
            '/super-admin/notifications',
            '/super-admin/profile',
            '/super-admin/activity-logs',
            '/super-admin/all-chat-sessions',
            '/super-admin/all-leads',
            '/super-admin/all-knowledge-files',
        ] as $path) {
            $this->actingAs($superAdmin)
                ->get($path)
                ->assertOk();
        }

        $this->assertSame($agent->id, $client->agent_id);
    }

    public function test_regular_workspace_user_cannot_access_global_super_admin_resources(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Acme Assistant',
            'company_name' => 'Acme Demo',
            'widget_token' => 'demo-widget-token',
        ]);

        $user = User::factory()->create([
            'agent_id' => $agent->id,
            'is_super_admin' => false,
        ]);

        $this->actingAs($user)
            ->get('/super-admin/clients')
            ->assertForbidden();
    }

    public function test_super_admin_workspace_users_and_admins_are_separated_and_have_view_pages(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Acme Assistant',
            'company_name' => 'Acme Demo',
            'widget_token' => 'demo-widget-token',
        ]);

        $client = User::factory()->create([
            'name' => 'Client User',
            'email' => 'client@example.com',
            'agent_id' => $agent->id,
            'is_super_admin' => false,
        ]);

        $superAdmin = User::query()->where('email', 'super@agent.com')->firstOrFail();

        $this->actingAs($superAdmin)
            ->get('/super-admin/workspace-users')
            ->assertOk()
            ->assertSee('Client User')
            ->assertDontSee('super@agent.com');

        $this->actingAs($superAdmin)
            ->get('/super-admin/admins')
            ->assertOk()
            ->assertSee('super@agent.com')
            ->assertDontSee('client@example.com');

        $this->actingAs($superAdmin)
            ->get('/super-admin/workspace-users/'.$client->getKey())
            ->assertOk()
            ->assertSee('client@example.com');

        $this->actingAs($superAdmin)
            ->get('/super-admin/admins/'.$superAdmin->getKey())
            ->assertOk()
            ->assertSee('super@agent.com');
    }

    public function test_super_admin_can_view_client_provider_api_keys_in_agent_settings(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Acme Assistant',
            'company_name' => 'Acme Demo',
            'widget_token' => 'demo-widget-token',
            'settings' => [
                'provider_credentials' => [
                    'openai' => [
                        'enabled' => true,
                        'api_key' => Crypt::encryptString('openai-secret-key'),
                    ],
                    'qdrant' => [
                        'enabled' => true,
                        'api_key' => Crypt::encryptString('qdrant-secret-key'),
                        'base_url' => 'http://qdrant.test:6333',
                        'collection' => 'client_collection',
                    ],
                    'railway' => [
                        'enabled' => true,
                        'api_key' => Crypt::encryptString('railway-secret-key'),
                        'project_id' => 'railway-project-id',
                        'environment_id' => 'railway-environment-id',
                        'service_id' => 'railway-service-id',
                    ],
                ],
            ],
        ]);

        $superAdmin = User::query()->where('email', 'super@agent.com')->firstOrFail();

        $this->actingAs($superAdmin)
            ->get('/super-admin/agent-settings')
            ->assertOk()
            ->assertSee('openai-secret-key')
            ->assertSee('http://qdrant.test:6333')
            ->assertSee('client_collection')
            ->assertSee('railway-project-id')
            ->assertSee('railway-environment-id')
            ->assertSee('railway-service-id')
            ->assertDontSee('Qdrant API Key')
            ->assertDontSee('Railway API Key')
            ->assertDontSee('Use Client OpenAI Override')
            ->assertDontSee('Use Client Qdrant Override')
            ->assertDontSee('Use Client Railway Override');
    }

    public function test_super_admin_can_view_resolved_platform_provider_api_keys_in_agent_settings(): void
    {
        config()->set('services.openai.api_key', 'platform-openai-key');
        config()->set('services.qdrant.url', 'http://platform-qdrant.test:6333');
        config()->set('services.qdrant.collection', 'platform_collection');
        config()->set('services.railway.project_id', 'platform-project-id');
        config()->set('services.railway.environment_id', 'platform-environment-id');
        config()->set('services.railway.service_id', 'platform-service-id');

        $agent = Agent::query()->create([
            'name' => 'Acme Assistant',
            'company_name' => 'Acme Demo',
            'widget_token' => 'demo-widget-token',
            'settings' => [
                'provider_credentials' => [
                    'openai' => [
                        'enabled' => false,
                    ],
                    'qdrant' => [
                        'enabled' => false,
                    ],
                    'railway' => [
                        'enabled' => false,
                    ],
                ],
            ],
        ]);

        $superAdmin = User::query()->where('email', 'super@agent.com')->firstOrFail();

        $this->actingAs($superAdmin)
            ->get('/super-admin/agent-settings')
            ->assertOk()
            ->assertSee('platform-openai-key')
            ->assertSee('http://platform-qdrant.test:6333')
            ->assertSee('platform_collection')
            ->assertSee('platform-project-id')
            ->assertSee('platform-environment-id')
            ->assertSee('platform-service-id')
            ->assertDontSee('Qdrant API Key')
            ->assertDontSee('Railway API Key');
    }

    public function test_invalid_client_openai_placeholder_falls_back_to_platform_key(): void
    {
        config()->set('services.openai.api_key', 'platform-openai-key');

        Agent::query()->create([
            'name' => 'Acme Assistant',
            'company_name' => 'Acme Demo',
            'widget_token' => 'demo-widget-token',
            'settings' => [
                'provider_credentials' => [
                    'openai' => [
                        'api_key' => Crypt::encryptString('client@agent'),
                        'chat_model' => 'gpt-5.3-chat-latest',
                        'embedding_model' => 'text-embedding-3-large',
                    ],
                ],
            ],
        ]);

        $superAdmin = User::query()->where('email', 'super@agent.com')->firstOrFail();

        $this->actingAs($superAdmin)
            ->get('/super-admin/agent-settings')
            ->assertOk()
            ->assertSee('platform-openai-key')
            ->assertDontSee('client@agent');
    }

    public function test_super_admin_notifications_page_shows_overdue_billing_and_tracking_alerts(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Acme Assistant',
            'company_name' => 'Acme Demo',
            'widget_token' => 'demo-widget-token',
            'payment_status' => Agent::PAYMENT_STATUS_PAST_DUE,
            'subscription_plan' => 'growth',
            'api_request_count' => 24,
            'last_error_at' => now()->subHour(),
        ]);

        PaymentRecord::query()->create([
            'agent_id' => $agent->id,
            'reference' => 'INV-1001',
            'amount' => 49.95,
            'currency' => 'BHD',
            'status' => PaymentRecord::STATUS_PENDING,
            'due_at' => now()->subDays(3),
        ]);

        $superAdmin = User::query()->where('email', 'super@agent.com')->firstOrFail();

        $this->actingAs($superAdmin)
            ->get('/super-admin/notifications')
            ->assertOk()
            ->assertSee('Client')
            ->assertSee('Category')
            ->assertSee('Severity')
            ->assertSee('Acme Demo payment is overdue')
            ->assertSee('INV-1001')
            ->assertSee('Acme Demo account requires billing attention')
            ->assertSee('Acme Demo has recent runtime errors');
    }

    public function test_overdue_failed_billing_record_creates_super_admin_notification_on_save(): void
    {
        $superAdmin = User::query()->where('email', 'super@agent.com')->firstOrFail();

        $agent = Agent::query()->create([
            'name' => 'Client B Assistant',
            'company_name' => 'Client B',
            'widget_token' => 'client-b-widget-token',
            'payment_status' => Agent::PAYMENT_STATUS_ACTIVE,
        ]);

        $paymentRecord = PaymentRecord::query()->create([
            'agent_id' => $agent->id,
            'reference' => 'INV-B-1002',
            'amount' => 60,
            'currency' => 'BHD',
            'status' => PaymentRecord::STATUS_PENDING,
            'due_at' => now()->addDay(),
        ]);

        $paymentRecord->update([
            'status' => PaymentRecord::STATUS_FAILED,
            'due_at' => now()->subDay(),
        ]);

        $notification = $superAdmin->fresh()
            ->notifications
            ->first(function ($notification): bool {
                return ($notification->data['type'] ?? null) === 'billing_overdue';
            });

        $this->assertNotNull($notification);
        $this->assertSame('Client B payment is overdue', $notification->data['title'] ?? null);
        $this->assertSame('Client B', $notification->data['client_name'] ?? null);
        $this->assertSame('critical', $notification->data['severity'] ?? null);
    }

    public function test_super_admin_can_download_a_payment_record_invoice_pdf(): void
    {
        $superAdmin = User::query()->where('email', 'super@agent.com')->firstOrFail();

        $agent = Agent::query()->create([
            'name' => 'Acme Assistant',
            'company_name' => 'Acme Demo',
            'widget_token' => 'acme-demo-widget',
            'contact_email' => 'billing@acme.test',
            'website_url' => 'https://acme.test',
        ]);

        $paymentRecord = PaymentRecord::query()->create([
            'agent_id' => $agent->id,
            'amount' => 149.50,
            'currency' => 'BHD',
            'status' => PaymentRecord::STATUS_PAID,
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->endOfMonth()->toDateString(),
            'paid_at' => now(),
            'notes' => 'Professional plan renewal',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.payment-records.invoice', $paymentRecord))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename="invoice-'.strtolower((string) $paymentRecord->fresh()->reference).'.pdf"');

        $this->assertDatabaseHas(ActivityLog::class, [
            'event' => 'billing.invoice.generated',
            'user_id' => $superAdmin->id,
            'subject_id' => $paymentRecord->id,
        ]);
    }

    public function test_creating_a_payment_record_logs_a_billing_activity(): void
    {
        $superAdmin = User::query()->where('email', 'super@agent.com')->firstOrFail();

        $agent = Agent::query()->create([
            'name' => 'Acme Assistant',
            'company_name' => 'Acme Demo',
            'widget_token' => 'acme-demo-widget',
        ]);

        $this->actingAs($superAdmin);

        $paymentRecord = PaymentRecord::query()->create([
            'agent_id' => $agent->id,
            'amount' => 89.00,
            'currency' => 'BHD',
            'status' => PaymentRecord::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas(ActivityLog::class, [
            'event' => 'billing.record.created',
            'user_id' => $superAdmin->id,
            'subject_id' => $paymentRecord->id,
        ]);
    }
}
