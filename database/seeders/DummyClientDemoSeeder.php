<?php

namespace Database\Seeders;

use App\Models\Agent;
use Illuminate\Database\Seeder;

class DummyClientDemoSeeder extends Seeder
{
    public function run(): void
    {
        Agent::query()->updateOrCreate(
            ['slug' => 'brightpath-academy'],
            [
                'name' => 'BrightPath Admissions Assistant',
                'company_name' => 'BrightPath Academy',
                'widget_token' => 'brightpath-academy-widget-demo-token-2026',
                'website_url' => 'https://brightpath.example',
                'industry' => 'Professional Education',
                'company_description' => 'Career-focused certificate courses in marketing, analytics, and product communication for busy professionals.',
                'support_email' => 'admissions@brightpath.example',
                'support_phone' => '+973 1700 2200',
                'contact_email' => 'admissions@brightpath.example',
                'welcome_message' => 'Hi, I can help you choose a course, explain schedules, and answer admissions questions.',
                'fallback_message' => 'I do not have that answer yet. Please leave your contact details and our admissions team will follow up.',
                'is_active' => true,
            ]
        );
    }
}
