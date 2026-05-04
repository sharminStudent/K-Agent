<?php

namespace Tests\Feature;

use App\Models\Agent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowcasePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_a_tenant_specific_dummy_client_site_with_the_widget_embed(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Northstar Assistant',
            'company_name' => 'Northstar Advisory',
            'slug' => 'northstar-advisory',
            'widget_token' => 'northstar-widget-token',
            'industry' => 'Financial Consulting',
            'company_description' => 'Independent advisory firm for founders, operators, and family businesses.',
            'website_url' => 'https://northstar.example',
        ]);

        $this->get('/dummy-client/'.$agent->slug)
            ->assertOk()
            ->assertSee('Northstar Advisory')
            ->assertSee('Northstar Assistant')
            ->assertSee('Financial Consulting')
            ->assertSee('/widget/'.$agent->widget_token.'/embed.js', false)
            ->assertSee('Professional Courses')
            ->assertSee('Featured Courses')
            ->assertSee('Digital Marketing for Small Business Growth')
            ->assertSee('Data Analytics Foundations');
    }
}
