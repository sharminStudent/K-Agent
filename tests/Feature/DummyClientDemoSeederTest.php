<?php

namespace Tests\Feature;

use App\Models\Agent;
use Database\Seeders\DummyClientDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DummyClientDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_demo_tenant_idempotently(): void
    {
        $this->seed(DummyClientDemoSeeder::class);
        $this->seed(DummyClientDemoSeeder::class);

        $this->assertDatabaseCount('agents', 1);

        $agent = Agent::query()->where('slug', 'brightpath-academy')->firstOrFail();

        $this->assertSame('BrightPath Academy', $agent->company_name);
        $this->assertSame('brightpath-academy-widget-demo-token-2026', $agent->widget_token);
        $this->assertSame('Professional Education', $agent->industry);
        $this->assertTrue($agent->is_active);
    }
}
