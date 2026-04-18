<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\ChatSession;
use App\Models\KnowledgeFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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
            'fallback_message' => 'I do not have enough company knowledge to answer that yet.',
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
            ->assertJsonPath('data.content', 'Hello, I need help.')
            ->assertJsonPath('data.assistant_message.role', 'assistant')
            ->assertJsonPath('data.assistant_message.content', 'I do not have enough company knowledge to answer that yet.');

        $this->assertDatabaseHas('chat_messages', [
            'agent_id' => $agent->id,
            'chat_session_id' => $chatSession->id,
            'role' => 'user',
            'content' => 'Hello, I need help.',
        ]);
        $this->assertDatabaseHas('chat_messages', [
            'agent_id' => $agent->id,
            'chat_session_id' => $chatSession->id,
            'role' => 'assistant',
            'content' => 'I do not have enough company knowledge to answer that yet.',
        ]);

        $this->assertNotNull($chatSession->fresh()->last_message_at);
    }

    public function test_it_generates_an_openai_backed_reply_when_relevant_knowledge_exists(): void
    {
        Storage::fake('local');
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'id' => 'resp_123',
                'output' => [
                    [
                        'content' => [
                            [
                                'type' => 'output_text',
                                'text' => 'Acme pricing starts at 99 BHD per month.',
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        config()->set('services.openai.api_key', 'test-key');
        config()->set('services.openai.chat_model', 'test-chat-model');
        config()->set('services.openai.base_url', 'https://api.openai.com/v1');

        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'Acme',
            'widget_token' => 'demo-widget-token',
        ]);

        $chatSession = ChatSession::query()->create([
            'agent_id' => $agent->id,
            'visitor_name' => 'Visitor',
            'visitor_email' => 'visitor@example.com',
        ]);

        Storage::disk('local')->put('knowledge-processed/'.$agent->id.'/chunks.json', json_encode([
            [
                'index' => 0,
                'content' => 'Acme pricing starts at 99 BHD per month and includes onboarding support.',
                'length' => 70,
            ],
        ]));

        KnowledgeFile::query()->create([
            'agent_id' => $agent->id,
            'disk' => 'local',
            'path' => 'knowledge-files/'.$agent->id.'/pricing.txt',
            'original_name' => 'pricing.txt',
            'mime_type' => 'text/plain',
            'size' => 100,
            'status' => 'ready',
            'ingested_at' => now(),
            'meta' => [
                'processed_chunks_path' => 'knowledge-processed/'.$agent->id.'/chunks.json',
            ],
        ]);

        $response = $this->postJson('/api/chat/send-message', [
            'widget_token' => $agent->widget_token,
            'session_id' => $chatSession->public_id,
            'message' => 'What is Acme pricing?',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.assistant_message.role', 'assistant')
            ->assertJsonPath('data.assistant_message.content', 'Acme pricing starts at 99 BHD per month.');

        $this->assertDatabaseHas('chat_messages', [
            'agent_id' => $agent->id,
            'chat_session_id' => $chatSession->id,
            'role' => 'assistant',
            'content' => 'Acme pricing starts at 99 BHD per month.',
        ]);
    }

    public function test_it_uses_qdrant_for_retrieval_when_vector_store_is_configured(): void
    {
        Http::fake([
            'https://api.openai.com/v1/embeddings' => Http::response([
                'data' => [
                    ['index' => 0, 'embedding' => [0.11, 0.22, 0.33]],
                ],
            ]),
            'https://api.openai.com/v1/responses' => Http::response([
                'id' => 'resp_456',
                'output' => [
                    [
                        'content' => [
                            [
                                'type' => 'output_text',
                                'text' => 'Acme onboarding takes two business days.',
                            ],
                        ],
                    ],
                ],
            ]),
            'http://qdrant.test/collections/k_agent_test/points/query' => Http::response([
                'result' => [
                    'points' => [
                        [
                            'id' => 'point-1',
                            'score' => 0.98,
                            'payload' => [
                                'agent_id' => 1,
                                'knowledge_file_id' => 1,
                                'knowledge_file_name' => 'faq.txt',
                                'chunk_index' => 0,
                                'content' => 'Acme onboarding takes two business days and includes setup support.',
                                'length' => 72,
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        config()->set('services.openai.api_key', 'test-key');
        config()->set('services.openai.chat_model', 'test-chat-model');
        config()->set('services.openai.embedding_model', 'text-embedding-3-small');
        config()->set('services.openai.base_url', 'https://api.openai.com/v1');
        config()->set('services.qdrant.url', 'http://qdrant.test');
        config()->set('services.qdrant.collection', 'k_agent_test');

        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'Acme',
            'widget_token' => 'demo-widget-token',
        ]);

        $chatSession = ChatSession::query()->create([
            'agent_id' => $agent->id,
            'visitor_name' => 'Visitor',
            'visitor_email' => 'visitor@example.com',
        ]);

        KnowledgeFile::query()->create([
            'agent_id' => $agent->id,
            'disk' => 'local',
            'path' => 'knowledge-files/'.$agent->id.'/faq.txt',
            'original_name' => 'faq.txt',
            'mime_type' => 'text/plain',
            'size' => 100,
            'status' => 'ready',
            'ingested_at' => now(),
            'meta' => [
                'vector_backend' => 'qdrant',
                'vector_collection' => 'k_agent_test',
            ],
        ]);

        $response = $this->postJson('/api/chat/send-message', [
            'widget_token' => $agent->widget_token,
            'session_id' => $chatSession->public_id,
            'message' => 'How long does onboarding take?',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.assistant_message.content', 'Acme onboarding takes two business days.');

        Http::assertSent(fn ($request) => $request->url() === 'http://qdrant.test/collections/k_agent_test/points/query');
    }

    public function test_it_prefers_company_openai_credentials_when_configured(): void
    {
        Storage::fake('local');
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'id' => 'resp_789',
                'output' => [
                    [
                        'content' => [
                            [
                                'type' => 'output_text',
                                'text' => 'Company specific key was used.',
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        config()->set('services.openai.api_key', 'platform-key');
        config()->set('services.openai.chat_model', 'platform-chat-model');
        config()->set('services.openai.base_url', 'https://api.openai.com/v1');

        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'Acme',
            'widget_token' => 'demo-widget-token',
            'settings' => app(\App\Services\AgentProviderConfigService::class)->mergeProviderSettings([], [
                'openai' => [
                    'enabled' => true,
                    'api_key' => 'company-key',
                    'base_url' => 'https://api.openai.com/v1',
                    'chat_model' => 'company-chat-model',
                ],
            ]),
        ]);

        $chatSession = ChatSession::query()->create([
            'agent_id' => $agent->id,
            'visitor_name' => 'Visitor',
        ]);

        Storage::disk('local')->put('knowledge-processed/'.$agent->id.'/chunks.json', json_encode([
            [
                'index' => 0,
                'content' => 'Company knowledge base content.',
                'length' => 30,
            ],
        ]));

        KnowledgeFile::query()->create([
            'agent_id' => $agent->id,
            'disk' => 'local',
            'path' => 'knowledge-files/'.$agent->id.'/faq.txt',
            'original_name' => 'faq.txt',
            'mime_type' => 'text/plain',
            'size' => 100,
            'status' => 'ready',
            'ingested_at' => now(),
            'meta' => [
                'processed_chunks_path' => 'knowledge-processed/'.$agent->id.'/chunks.json',
            ],
        ]);

        $response = $this->postJson('/api/chat/send-message', [
            'widget_token' => $agent->widget_token,
            'session_id' => $chatSession->public_id,
            'message' => 'Use the company credential path.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.assistant_message.content', 'Company specific key was used.');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/responses'
                && $request->hasHeader('Authorization', 'Bearer company-key')
                && data_get($request->data(), 'model') === 'company-chat-model';
        });
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
