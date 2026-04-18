<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\KnowledgeFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WidgetWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_serves_the_widget_embed_script_for_an_active_agent(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'K-Agent Demo',
            'widget_token' => 'demo-widget-token',
        ]);

        $response = $this->get('/widget/'.$agent->widget_token.'/embed.js');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/javascript; charset=UTF-8');
        $response->assertSee('widget\\/'.$agent->widget_token.'\\/frame', false);
        $response->assertSee("frame.style.width = '360px';", false);
        $response->assertSee("frame.style.height = '500px';", false);
    }

    public function test_it_renders_the_widget_frame_for_an_active_agent(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'K-Agent Demo',
            'widget_token' => 'demo-widget-token',
            'welcome_message' => 'Ask us anything about your business.',
        ]);

        $response = $this->get('/widget/'.$agent->widget_token.'/frame');

        $response->assertOk();
        $response->assertSee('K-Agent Demo');
        $response->assertSee('Ask us anything about your business.');
        $response->assertSee('Support Agent');
        $response->assertSee('Ask a question....');
        $response->assertSee('privacy policy');
    }

    public function test_it_renders_a_preview_page_that_loads_the_embed_script(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'K-Agent Demo',
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
            'company_name' => 'K-Agent Demo',
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
            'company_name' => 'K-Agent Demo',
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
        Storage::fake('local');

        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'K-Agent Demo',
            'widget_token' => 'demo-widget-token',
        ]);

        Storage::disk('local')->put('knowledge-processed/1/article-text.txt', 'This article explains pricing, onboarding, and support options.');

        KnowledgeFile::query()->create([
            'agent_id' => $agent->id,
            'disk' => 'local',
            'path' => 'knowledge-files/1/pricing.txt',
            'original_name' => 'Pricing FAQ',
            'mime_type' => 'text/plain',
            'size' => 100,
            'status' => 'ready',
            'ingested_at' => now(),
            'meta' => [
                'processed_text_path' => 'knowledge-processed/1/article-text.txt',
            ],
        ]);

        $response = $this->getJson('/widget/'.$agent->widget_token.'/help');

        $response->assertOk()
            ->assertJsonPath('data.articles.0.title', 'Pricing FAQ');
    }

    public function test_it_returns_help_article_content_for_the_correct_agent(): void
    {
        Storage::fake('local');

        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'K-Agent Demo',
            'widget_token' => 'demo-widget-token',
        ]);

        Storage::disk('local')->put('knowledge-processed/1/article-text.txt', 'Full article body for the widget help reader.');

        $knowledgeFile = KnowledgeFile::query()->create([
            'agent_id' => $agent->id,
            'disk' => 'local',
            'path' => 'knowledge-files/1/article.txt',
            'original_name' => 'Help Article',
            'mime_type' => 'text/plain',
            'size' => 100,
            'status' => 'ready',
            'ingested_at' => now(),
            'meta' => [
                'processed_text_path' => 'knowledge-processed/1/article-text.txt',
            ],
        ]);

        $response = $this->getJson('/widget/'.$agent->widget_token.'/help/'.$knowledgeFile->id);

        $response->assertOk()
            ->assertJsonPath('data.article.title', 'Help Article')
            ->assertJsonPath('data.article.content', 'Full article body for the widget help reader.');
    }
}
