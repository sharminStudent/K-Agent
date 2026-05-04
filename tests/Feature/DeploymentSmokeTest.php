<?php

namespace Tests\Feature;

use App\Models\Agent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeploymentSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_entry_points_boot_for_deployment(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Support Agent',
            'company_name' => 'Acme Demo',
            'widget_token' => 'deploy-smoke-widget',
        ]);

        $this->get('/up')->assertOk();
        $this->get('/')->assertOk();
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/widget/'.$agent->widget_token.'/embed.js')->assertOk();
        $this->get('/widget/'.$agent->widget_token.'/frame')->assertOk();
    }
}
