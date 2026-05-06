<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WidgetWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_serves_the_widget_embed_script_for_an_active_agent(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'Acme Demo',
            'widget_token' => 'demo-widget-token',
        ]);

        $response = $this->get('/widget/'.$agent->widget_token.'/embed.js');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/javascript; charset=UTF-8');
        $response->assertSee('widget\\/'.$agent->widget_token.'\\/frame', false);
        $response->assertSee("frame.style.width = '360px';", false);
        $response->assertSee("var isSmallScreen = window.matchMedia('(max-width: 640px)').matches;", false);
        $response->assertSee("frame.style.width = compactScreen ? 'calc(100vw - 16px)' : '360px';", false);
        $response->assertSee("frame.style.height = '500px';", false);
        $response->assertSee("var compactScreen = isSmallScreen;", false);
        $response->assertSee("frame.style.bottom = compactScreen ? 'max(76px, calc(env(safe-area-inset-bottom) + 68px))' : (isShortScreen ? '24px' : '88px');", false);
        $response->assertSee("frame.style.height = compactScreen ? 'calc(100dvh - 92px - env(safe-area-inset-top) - env(safe-area-inset-bottom))' : '500px';", false);
        $response->assertSee("frame.style.maxHeight = compactScreen ? 'calc(100dvh - 92px - env(safe-area-inset-top) - env(safe-area-inset-bottom))' : 'calc(100vh - 48px)';", false);
        $response->assertSee("launcher.style.pointerEvents = isOpen ? 'none' : 'auto';", false);
        $response->assertSee("launcher.style.bottom = compactScreen ? 'max(12px, env(safe-area-inset-bottom))' : '24px';", false);
        $response->assertSee('event.source !== frame.contentWindow', false);
        $response->assertSee("event.key === 'Escape'", false);
    }

    public function test_it_renders_the_widget_frame_for_an_active_agent(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'Acme Demo',
            'widget_token' => 'demo-widget-token',
            'welcome_message' => 'Ask us anything about your business.',
        ]);

        $response = $this->get('/widget/'.$agent->widget_token.'/frame');

        $response->assertOk();
        $response->assertSee('Acme Demo');
        $response->assertSee('Ask us anything about your business.');
        $response->assertSee('Support Agent');
        $response->assertSee('Ask a question....');
        $response->assertSee('Search help articles...');
        $response->assertSee('privacy policy');
        $response->assertDontSee('Voice input');
        $response->assertSee("helpUrl: 'http:\\/\\/localhost\\/widget\\/{$agent->widget_token}\\/help'", false);
        $response->assertSee('@media (max-width: 640px)', false);
    }

    public function test_it_renders_privacy_policy_url_from_agent_settings(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'Acme Demo',
            'widget_token' => 'demo-widget-token',
            'settings' => [
                'privacy_url' => 'https://example.com/privacy',
            ],
        ]);

        $response = $this->get('/widget/'.$agent->widget_token.'/frame');

        $response->assertOk()
            ->assertSee("privacyUrl: 'https:\\/\\/example.com\\/privacy'", false);
    }

    public function test_it_renders_a_preview_page_that_loads_the_embed_script(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'Acme Demo',
            'widget_token' => 'demo-widget-token',
        ]);

        $response = $this->get('/widget/'.$agent->widget_token.'/preview');

        $response->assertOk();
        $response->assertSee('Widget Preview');
        $response->assertSee('widget/'.$agent->widget_token.'/embed.js', false);
    }

    public function test_it_bootstraps_a_scoped_existing_session_with_messages(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'Acme Demo',
            'widget_token' => 'demo-widget-token',
        ]);

        $chatSession = ChatSession::query()->create([
            'agent_id' => $agent->id,
            'visitor_name' => 'Sharmin',
            'visitor_email' => 'sharmin@example.com',
        ]);

        ChatMessage::query()->create([
            'agent_id' => $agent->id,
            'chat_session_id' => $chatSession->id,
            'role' => 'user',
            'content' => 'Hello there',
        ]);

        ChatMessage::query()->create([
            'agent_id' => $agent->id,
            'chat_session_id' => $chatSession->id,
            'role' => 'assistant',
            'content' => 'How can I help?',
        ]);

        $response = $this->getJson('/widget/'.$agent->widget_token.'/bootstrap?session_id='.$chatSession->public_id);

        $response->assertOk()
            ->assertJsonPath('data.session.session_id', $chatSession->public_id)
            ->assertJsonCount(2, 'data.session.messages')
            ->assertJsonPath('data.session.messages.0.role', 'user')
            ->assertJsonPath('data.session.messages.1.role', 'assistant');
    }

    public function test_it_does_not_bootstrap_a_session_owned_by_another_agent(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'Acme Demo',
            'widget_token' => 'demo-widget-token',
        ]);

        $otherAgent = Agent::query()->create([
            'name' => 'Other Agent',
            'company_name' => 'Other Demo',
            'widget_token' => 'other-widget-token',
        ]);

        $chatSession = ChatSession::query()->create([
            'agent_id' => $otherAgent->id,
            'visitor_name' => 'Sharmin',
        ]);

        $response = $this->getJson('/widget/'.$agent->widget_token.'/bootstrap?session_id='.$chatSession->public_id);

        $response->assertOk()
            ->assertJsonPath('data.session', null);
    }

    public function test_it_lists_help_articles_for_ready_knowledge_files(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'Acme Demo',
            'widget_token' => 'demo-widget-token',
            'settings' => [
                'help_center_items' => [
                    [
                        'title' => 'Pricing FAQ',
                        'description' => 'This article explains pricing, onboarding, and support options.',
                    ],
                ],
            ],
        ]);

        $response = $this->getJson('/widget/'.$agent->widget_token.'/help');

        $response->assertOk()
            ->assertJsonPath('data.articles.0.title', 'Pricing FAQ')
            ->assertJsonPath('data.articles.0.excerpt', 'This article explains pricing, onboarding, and support options.');
    }

    public function test_it_searches_help_articles_from_agent_help_center_items(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'Acme Demo',
            'widget_token' => 'demo-widget-token',
            'settings' => [
                'help_center_items' => [
                    [
                        'title' => 'Onboarding Guide',
                        'description' => 'Acme onboarding takes two business days with setup support.',
                    ],
                    [
                        'title' => 'Support Hours',
                        'description' => 'Our support team is available Sunday to Thursday.',
                    ],
                ],
            ],
        ]);

        $response = $this->getJson('/widget/'.$agent->widget_token.'/help?q=onboarding');

        $response->assertOk()
            ->assertJsonPath('data.articles.0.title', 'Onboarding Guide')
            ->assertJsonPath('data.articles.0.excerpt', 'Acme onboarding takes two business days with setup support.');
    }

    public function test_it_returns_help_article_content_for_the_correct_agent(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'Acme Demo',
            'widget_token' => 'demo-widget-token',
            'settings' => [
                'help_center_items' => [
                    [
                        'title' => 'Help Article',
                        'description' => 'Full article body for the widget help reader.',
                    ],
                ],
            ],
        ]);

        $response = $this->getJson('/widget/'.$agent->widget_token.'/help/1');

        $response->assertOk()
            ->assertJsonPath('data.article.title', 'Help Article')
            ->assertJsonPath('data.article.content', 'Full article body for the widget help reader.');
    }
}
