<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\KnowledgeFile;
use App\Models\User;
use App\Services\KnowledgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KnowledgeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uploads_a_knowledge_file_for_the_correct_agent(): void
    {
        Storage::fake('local');

        $agent = Agent::query()->create([
            'name' => 'Knowledge Agent',
            'company_name' => 'Acme',
            'widget_token' => 'acme-widget-token',
        ]);
        $this->authenticateForAgent($agent);

        $file = UploadedFile::fake()->createWithContent('pricing.txt', 'Pricing details for Acme');

        $response = $this->postJson('/api/knowledge/upload', [
            'widget_token' => $agent->widget_token,
            'file' => $file,
            'meta' => [
                'source' => 'dashboard',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.agent_id', $agent->id)
            ->assertJsonPath('data.original_name', 'pricing.txt')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('knowledge_files', [
            'agent_id' => $agent->id,
            'original_name' => 'pricing.txt',
            'status' => 'pending',
        ]);

        $knowledgeFile = KnowledgeFile::query()->firstOrFail();

        Storage::disk('local')->assertExists($knowledgeFile->path);
    }

    public function test_it_rejects_unauthenticated_uploads(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->createWithContent('pricing.txt', 'Pricing details');

        $response = $this->postJson('/api/knowledge/upload', [
            'widget_token' => 'missing-agent-token',
            'file' => $file,
        ]);

        $response->assertUnauthorized();

        $this->assertDatabaseCount('knowledge_files', 0);
    }

    public function test_it_rejects_upload_for_an_agent_the_user_does_not_own(): void
    {
        Storage::fake('local');

        $agent = Agent::query()->create([
            'name' => 'Knowledge Agent',
            'company_name' => 'Acme',
            'widget_token' => 'acme-widget-token',
        ]);
        $otherAgent = Agent::query()->create([
            'name' => 'Other Agent',
            'company_name' => 'Globex',
            'widget_token' => 'globex-widget-token',
        ]);
        $this->authenticateForAgent($otherAgent);

        $file = UploadedFile::fake()->createWithContent('pricing.txt', 'Pricing details');

        $response = $this->postJson('/api/knowledge/upload', [
            'widget_token' => $agent->widget_token,
            'file' => $file,
        ]);

        $response->assertForbidden();

        $this->assertDatabaseCount('knowledge_files', 0);
    }

    public function test_it_rejects_an_invalid_file_type(): void
    {
        Storage::fake('local');

        $agent = Agent::query()->create([
            'name' => 'Knowledge Agent',
            'company_name' => 'Acme',
            'widget_token' => 'acme-widget-token',
        ]);
        $this->authenticateForAgent($agent);

        $file = UploadedFile::fake()->create('logo.png', 10, 'image/png');

        $response = $this->postJson('/api/knowledge/upload', [
            'widget_token' => $agent->widget_token,
            'file' => $file,
        ]);

        $response->assertUnprocessable();

        $this->assertDatabaseCount('knowledge_files', 0);
    }

    public function test_it_processes_a_text_knowledge_file_into_chunks(): void
    {
        Storage::fake('local');

        $agent = Agent::query()->create([
            'name' => 'Knowledge Agent',
            'company_name' => 'Acme',
            'widget_token' => 'acme-widget-token',
        ]);
        $this->authenticateForAgent($agent);

        $file = UploadedFile::fake()->createWithContent(
            'faq.txt',
            str_repeat('Acme pricing and onboarding details. ', 80)
        );

        $uploadResponse = $this->postJson('/api/knowledge/upload', [
            'widget_token' => $agent->widget_token,
            'file' => $file,
        ]);

        $knowledgeFileId = $uploadResponse->json('data.id');

        $response = $this->postJson("/api/knowledge/{$knowledgeFileId}/process", [
            'widget_token' => $agent->widget_token,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.agent_id', $agent->id)
            ->assertJsonPath('data.status', 'ready');

        /** @var KnowledgeFile $knowledgeFile */
        $knowledgeFile = KnowledgeFile::query()->findOrFail($knowledgeFileId);

        $this->assertSame('ready', $knowledgeFile->status);
        $this->assertNotNull($knowledgeFile->ingested_at);
        $this->assertGreaterThan(0, $knowledgeFile->meta['chunk_count']);

        Storage::disk('local')->assertExists($knowledgeFile->meta['processed_text_path']);
        Storage::disk('local')->assertExists($knowledgeFile->meta['processed_chunks_path']);
    }

    public function test_it_stores_additional_info_as_text_knowledge(): void
    {
        Storage::fake('local');

        $agent = Agent::query()->create([
            'name' => 'Knowledge Agent',
            'company_name' => 'Acme',
            'widget_token' => 'acme-widget-token',
        ]);

        $knowledgeFile = app(KnowledgeService::class)->storeTextKnowledge([
            'widget_token' => $agent->widget_token,
            'meta' => [
                'source' => 'filament',
            ],
        ], 'Pricing Notes', 'Acme offers custom onboarding and monthly support.');

        $this->assertSame($agent->id, $knowledgeFile->agent_id);
        $this->assertSame('Pricing Notes.txt', $knowledgeFile->original_name);
        $this->assertSame('text/plain', $knowledgeFile->mime_type);
        $this->assertSame('pending', $knowledgeFile->status);
        $this->assertSame('additional_info', $knowledgeFile->meta['source']);
        $this->assertSame('Pricing Notes', $knowledgeFile->meta['title']);

        Storage::disk('local')->assertExists($knowledgeFile->path);
        $this->assertStringContainsString(
            'Acme offers custom onboarding and monthly support.',
            Storage::disk('local')->get($knowledgeFile->path)
        );
    }

    public function test_it_updates_additional_info_and_refreshes_processed_content(): void
    {
        Storage::fake('local');

        $agent = Agent::query()->create([
            'name' => 'Knowledge Agent',
            'company_name' => 'Acme',
            'widget_token' => 'acme-widget-token',
        ]);

        $knowledgeService = app(KnowledgeService::class);

        $knowledgeFile = $knowledgeService->storeTextKnowledge([
            'widget_token' => $agent->widget_token,
            'meta' => [
                'source' => 'filament',
            ],
        ], 'Old Title', 'Old description');

        $processed = $knowledgeService->processKnowledgeFile($knowledgeFile, [
            'widget_token' => $agent->widget_token,
        ]);

        $updated = $knowledgeService->updateTextKnowledge($processed, $agent, 'New Title', 'New description');

        $this->assertSame('New Title.txt', $updated->original_name);
        $this->assertSame('New Title', $updated->meta['title']);
        $this->assertSame('New description', $updated->meta['description']);
        $this->assertSame('ready', $updated->status);
        $this->assertNotNull($updated->ingested_at);

        $this->assertStringContainsString('New description', Storage::disk('local')->get($updated->path));
        $this->assertStringContainsString(
            'New description',
            Storage::disk('local')->get($updated->meta['processed_text_path'])
        );
    }

    public function test_it_stores_embeddings_in_qdrant_when_configured(): void
    {
        Storage::fake('local');

        Http::fake([
            'https://api.openai.com/v1/embeddings' => Http::response([
                'data' => [
                    ['index' => 0, 'embedding' => [0.1, 0.2, 0.3]],
                    ['index' => 1, 'embedding' => [0.2, 0.3, 0.4]],
                    ['index' => 2, 'embedding' => [0.3, 0.4, 0.5]],
                    ['index' => 3, 'embedding' => [0.4, 0.5, 0.6]],
                ],
            ]),
            'http://qdrant.test/collections/k_agent_test' => Http::sequence()
                ->push([], 404)
                ->push(['result' => ['status' => 'green']], 200),
            'http://qdrant.test/collections/k_agent_test/points' => Http::response([
                'result' => ['status' => 'acknowledged'],
            ]),
        ]);

        config()->set('services.openai.api_key', 'test-key');
        config()->set('services.openai.embedding_model', 'text-embedding-3-small');
        config()->set('services.openai.base_url', 'https://api.openai.com/v1');
        config()->set('services.qdrant.url', 'http://qdrant.test');
        config()->set('services.qdrant.collection', 'k_agent_test');

        $agent = Agent::query()->create([
            'name' => 'Knowledge Agent',
            'company_name' => 'Acme',
            'widget_token' => 'acme-widget-token',
        ]);
        $this->authenticateForAgent($agent);

        $file = UploadedFile::fake()->createWithContent(
            'faq.txt',
            str_repeat('Acme pricing and onboarding details. ', 80)
        );

        $uploadResponse = $this->postJson('/api/knowledge/upload', [
            'widget_token' => $agent->widget_token,
            'file' => $file,
        ]);

        $knowledgeFileId = $uploadResponse->json('data.id');

        $response = $this->postJson("/api/knowledge/{$knowledgeFileId}/process", [
            'widget_token' => $agent->widget_token,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'ready');

        $knowledgeFile = KnowledgeFile::query()->findOrFail($knowledgeFileId);

        $this->assertSame('qdrant', $knowledgeFile->meta['vector_backend']);
        $this->assertSame('k_agent_test', $knowledgeFile->meta['vector_collection']);
        $this->assertNotEmpty($knowledgeFile->meta['vector_point_ids']);
        Storage::disk('local')->assertExists($knowledgeFile->meta['embeddings_path']);

        Http::assertSent(fn ($request) => $request->url() === 'http://qdrant.test/collections/k_agent_test/points');
    }

    public function test_it_marks_knowledge_ready_even_when_embeddings_fail(): void
    {
        Storage::fake('local');

        Http::fake([
            'https://api.openai.com/v1/embeddings' => Http::response([
                'error' => [
                    'message' => 'Quota exceeded.',
                ],
            ], 429),
        ]);

        config()->set('services.openai.api_key', 'test-key');
        config()->set('services.openai.embedding_model', 'text-embedding-3-large');
        config()->set('services.openai.base_url', 'https://api.openai.com/v1');
        config()->set('services.qdrant.url', null);
        config()->set('services.qdrant.collection', null);

        $agent = Agent::query()->create([
            'name' => 'Knowledge Agent',
            'company_name' => 'Acme',
            'widget_token' => 'acme-widget-token',
        ]);
        $this->authenticateForAgent($agent);

        $file = UploadedFile::fake()->createWithContent(
            'faq.txt',
            'Service Charges: based on scope. Our Team - Project Managers, Designers and Developers.'
        );

        $uploadResponse = $this->postJson('/api/knowledge/upload', [
            'widget_token' => $agent->widget_token,
            'file' => $file,
        ]);

        $knowledgeFileId = $uploadResponse->json('data.id');

        $response = $this->postJson("/api/knowledge/{$knowledgeFileId}/process", [
            'widget_token' => $agent->widget_token,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'ready');

        /** @var KnowledgeFile $knowledgeFile */
        $knowledgeFile = KnowledgeFile::query()->findOrFail($knowledgeFileId);

        $this->assertSame('ready', $knowledgeFile->status);
        $this->assertSame('failed', $knowledgeFile->meta['embeddings_status']);
        $this->assertNotEmpty($knowledgeFile->meta['embeddings_error']);
        Storage::disk('local')->assertExists($knowledgeFile->meta['processed_text_path']);
        Storage::disk('local')->assertExists($knowledgeFile->meta['processed_chunks_path']);
    }

    public function test_it_rejects_processing_a_knowledge_file_for_another_agent(): void
    {
        Storage::fake('local');

        $agent = Agent::query()->create([
            'name' => 'Knowledge Agent',
            'company_name' => 'Acme',
            'widget_token' => 'acme-widget-token',
        ]);

        $otherAgent = Agent::query()->create([
            'name' => 'Other Agent',
            'company_name' => 'Globex',
            'widget_token' => 'globex-widget-token',
        ]);
        $this->authenticateForAgent($agent);

        $file = UploadedFile::fake()->createWithContent('faq.txt', 'Acme details');

        $uploadResponse = $this->postJson('/api/knowledge/upload', [
            'widget_token' => $agent->widget_token,
            'file' => $file,
        ]);

        $knowledgeFileId = $uploadResponse->json('data.id');

        $this->authenticateForAgent($otherAgent);

        $response = $this->postJson("/api/knowledge/{$knowledgeFileId}/process", [
            'widget_token' => $otherAgent->widget_token,
        ]);

        $response->assertForbidden();

        $this->assertSame('pending', KnowledgeFile::query()->findOrFail($knowledgeFileId)->status);
    }

    public function test_it_deletes_a_knowledge_file_and_its_processing_artifacts(): void
    {
        Storage::fake('local');

        $agent = Agent::query()->create([
            'name' => 'Knowledge Agent',
            'company_name' => 'Acme',
            'widget_token' => 'acme-widget-token',
        ]);

        $knowledgeFile = KnowledgeFile::query()->create([
            'agent_id' => $agent->id,
            'disk' => 'local',
            'path' => 'knowledge-files/'.$agent->id.'/pricing.txt',
            'original_name' => 'pricing.txt',
            'mime_type' => 'text/plain',
            'size' => 100,
            'status' => 'ready',
            'meta' => [
                'processed_text_path' => 'knowledge-processed/'.$agent->id.'/pricing-text.txt',
                'processed_chunks_path' => 'knowledge-processed/'.$agent->id.'/pricing-chunks.json',
                'embeddings_path' => 'knowledge-vectors/'.$agent->id.'/pricing-embeddings.json',
            ],
        ]);

        Storage::disk('local')->put($knowledgeFile->path, 'pricing');
        Storage::disk('local')->put($knowledgeFile->meta['processed_text_path'], 'pricing text');
        Storage::disk('local')->put($knowledgeFile->meta['processed_chunks_path'], '{"chunks":[]}');
        Storage::disk('local')->put($knowledgeFile->meta['embeddings_path'], '{"vectors":[]}');

        app(KnowledgeService::class)->deleteKnowledgeFile($knowledgeFile, $agent);

        $this->assertDatabaseMissing('knowledge_files', [
            'id' => $knowledgeFile->id,
        ]);
        Storage::disk('local')->assertMissing('knowledge-files/'.$agent->id.'/pricing.txt');
        Storage::disk('local')->assertMissing('knowledge-processed/'.$agent->id.'/pricing-text.txt');
        Storage::disk('local')->assertMissing('knowledge-processed/'.$agent->id.'/pricing-chunks.json');
        Storage::disk('local')->assertMissing('knowledge-vectors/'.$agent->id.'/pricing-embeddings.json');
    }

    private function authenticateForAgent(Agent $agent): void
    {
        $this->actingAs(User::factory()->create([
            'agent_id' => $agent->id,
        ]));
    }
}
