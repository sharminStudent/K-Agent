<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\User;
use Database\Seeders\ClientWorkspaceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientWorkspaceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_two_clean_client_workspaces_and_removes_the_old_dummy_client(): void
    {
        Agent::query()->create([
            'name' => 'Old Demo Assistant',
            'company_name' => 'BrightPath Academy',
            'slug' => 'brightpath-academy',
            'widget_token' => 'brightpath-academy-widget-demo-token-2026',
        ]);

        $this->seed(ClientWorkspaceSeeder::class);
        $this->seed(ClientWorkspaceSeeder::class);

        $this->assertDatabaseMissing('agents', [
            'slug' => 'brightpath-academy',
        ]);

        $this->assertDatabaseHas('agents', [
            'company_name' => 'Klabs Tech',
            'slug' => 'klabs-tech',
            'widget_token' => 'BDZdb5u9Rsv2tp2fGxV83ykFPgeqrLqteKCqCQ54',
        ]);

        $this->assertDatabaseHas('agents', [
            'company_name' => 'Northstar Learning',
            'slug' => 'northstar-learning',
            'widget_token' => 'zqMqmzWo2V9c9rIwdthD8xTgEDjPfsGYKiNG4Aze',
        ]);

        $this->assertSame(2, Agent::query()->count());
        $this->assertNotNull(User::query()->where('email', 'admin@klabstech.test')->first());
        $this->assertNotNull(User::query()->where('email', 'admin@northstarlearning.test')->first());
    }
}
