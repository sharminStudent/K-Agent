<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\KnowledgeFile;
use App\Services\AiTesterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiTesterServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_runs_a_normal_rag_test_and_returns_an_openai_answer(): void
    {
        Storage::fake('local');
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'id' => 'resp_tester_123',
                'output' => [[
                    'content' => [[
                        'type' => 'output_text',
                        'text' => 'K-Labs offers design, development, and AI workflow services.',
                    ]],
                ]],
            ]),
        ]);

        config()->set('services.openai.api_key', 'test-key');
        config()->set('services.openai.chat_model', 'test-model');
        config()->set('services.openai.base_url', 'https://api.openai.com/v1');

        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'K-Labs',
            'widget_token' => 'demo-widget-token',
        ]);

        Storage::disk('local')->put('knowledge-processed/'.$agent->id.'/chunks.json', json_encode([
            [
                'index' => 0,
                'content' => 'K-Labs offers design, development, and AI workflow services for clients.',
                'length' => 73,
            ],
        ]));

        KnowledgeFile::query()->create([
            'agent_id' => $agent->id,
            'disk' => 'local',
            'path' => 'knowledge-files/'.$agent->id.'/services.txt',
            'original_name' => 'services.txt',
            'mime_type' => 'text/plain',
            'size' => 100,
            'status' => 'ready',
            'ingested_at' => now(),
            'meta' => [
                'processed_chunks_path' => 'knowledge-processed/'.$agent->id.'/chunks.json',
            ],
        ]);

        $result = app(AiTesterService::class)->run($agent, 'What services does K-Labs offer?', 'normal');

        $this->assertSame('openai_rag', $result['source']);
        $this->assertFalse($result['used_fallback']);
        $this->assertSame('K-Labs offers design, development, and AI workflow services.', $result['content']);
        $this->assertCount(1, $result['context_chunks']);
    }

    public function test_it_can_force_a_guardrail_no_context_fallback(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'K-Labs',
            'widget_token' => 'demo-widget-token',
            'fallback_message' => 'Fallback triggered.',
        ]);

        $result = app(AiTesterService::class)->run($agent, 'What services does K-Labs offer?', 'no_context');

        $this->assertSame('forced_guardrail_no_context', $result['source']);
        $this->assertTrue($result['used_fallback']);
        $this->assertSame('Fallback triggered.', $result['content']);
        $this->assertSame([], $result['context_chunks']);
    }

    public function test_it_can_force_an_openai_error_and_return_fallback(): void
    {
        Storage::fake('local');
        config()->set('services.openai.api_key', 'test-key');
        config()->set('services.openai.chat_model', 'test-model');
        config()->set('services.openai.base_url', 'https://api.openai.com/v1');

        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'K-Labs',
            'widget_token' => 'demo-widget-token',
            'fallback_message' => 'Fallback triggered.',
        ]);

        Storage::disk('local')->put('knowledge-processed/'.$agent->id.'/chunks.json', json_encode([
            [
                'index' => 0,
                'content' => 'K-Labs offers design and development.',
                'length' => 40,
            ],
        ]));

        KnowledgeFile::query()->create([
            'agent_id' => $agent->id,
            'disk' => 'local',
            'path' => 'knowledge-files/'.$agent->id.'/services.txt',
            'original_name' => 'services.txt',
            'mime_type' => 'text/plain',
            'size' => 100,
            'status' => 'ready',
            'ingested_at' => now(),
            'meta' => [
                'processed_chunks_path' => 'knowledge-processed/'.$agent->id.'/chunks.json',
            ],
        ]);

        $result = app(AiTesterService::class)->run($agent, 'What services does K-Labs offer?', 'openai_error');

        $this->assertSame('fallback_error', $result['source']);
        $this->assertTrue($result['used_fallback']);
        $this->assertSame('Fallback triggered.', $result['content']);
        $this->assertSame('Forced OpenAI failure for tester.', $result['error']);
    }
}
