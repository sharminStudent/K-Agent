<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientWorkspaceSeeder extends Seeder
{
    public function run(): void
    {
        $this->deleteTemporaryDemoClients();

        $klabs = $this->upsertAgent(
            matchCompanyNames: ['Klabs Tech'],
            matchSlugs: ['klabs-tech', 'k-agent-admin-omkgfd'],
            attributes: [
                'name' => 'Klabs Assistant',
                'company_name' => 'Klabs Tech',
                'slug' => 'klabs-tech',
                'widget_token' => 'BDZdb5u9Rsv2tp2fGxV83ykFPgeqrLqteKCqCQ54',
                'contact_email' => 'admin@klabstech.test',
                'support_email' => 'hello@klabstech.test',
                'support_phone' => '+97317001234',
                'website_url' => 'https://klabstech.test',
                'industry' => 'Software Development',
                'company_description' => 'Bahrain-based software studio delivering custom web platforms, mobile apps, internal tools, and workflow automation for growing businesses.',
                'welcome_message' => 'Hi, I can help with software project scope, delivery timelines, web and mobile builds, and support questions.',
                'fallback_message' => 'I do not want to guess on that. Leave your project details and the Klabs team will follow up with the right person.',
                'is_active' => true,
            ],
        );

        $this->upsertWorkspaceUser(
            agent: $klabs,
            email: 'admin@klabstech.test',
            name: 'K-Agent Admin',
            password: 'client@agent',
        );

        $northstar = $this->upsertAgent(
            matchCompanyNames: ['Northstar Learning', 'test'],
            matchSlugs: ['northstar-learning', 'test-uurd8c'],
            attributes: [
                'name' => 'Northstar Assistant',
                'company_name' => 'Northstar Learning',
                'slug' => 'northstar-learning',
                'widget_token' => 'zqMqmzWo2V9c9rIwdthD8xTgEDjPfsGYKiNG4Aze',
                'contact_email' => 'admin@northstarlearning.test',
                'support_email' => 'admissions@northstarlearning.test',
                'support_phone' => '+97317004567',
                'website_url' => 'https://northstarlearning.test',
                'industry' => 'Professional Education',
                'company_description' => 'Professional training provider offering cohort-based certificate programs in digital marketing, business analytics, and applied AI skills for working professionals.',
                'welcome_message' => 'Hi, I can help with course outlines, upcoming intakes, tuition questions, and enrollment steps.',
                'fallback_message' => 'I do not have a confirmed answer for that yet. Share your contact details and the Northstar admissions team will follow up.',
                'is_active' => true,
            ],
        );

        $this->upsertWorkspaceUser(
            agent: $northstar,
            email: 'admin@northstarlearning.test',
            name: 'Northstar Admin',
            password: 'client@agent',
        );
    }

    /**
     * @param  list<string>  $matchCompanyNames
     * @param  list<string>  $matchSlugs
     * @param  array<string, mixed>  $attributes
     */
    protected function upsertAgent(array $matchCompanyNames, array $matchSlugs, array $attributes): Agent
    {
        $agent = Agent::query()
            ->whereIn('company_name', $matchCompanyNames)
            ->orWhereIn('slug', $matchSlugs)
            ->first();

        if ($agent) {
            $agent->fill($attributes);
            $agent->save();

            return $agent->fresh();
        }

        return Agent::query()->create($attributes);
    }

    protected function upsertWorkspaceUser(Agent $agent, string $email, string $name, string $password): void
    {
        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'agent_id' => $agent->id,
                'password' => $password,
                'is_super_admin' => false,
                'is_active' => true,
            ],
        );
    }

    protected function deleteTemporaryDemoClients(): void
    {
        Agent::query()
            ->whereIn('slug', ['brightpath-academy'])
            ->orWhereIn('company_name', ['BrightPath Academy'])
            ->get()
            ->each(function (Agent $agent): void {
                User::query()->where('agent_id', $agent->id)->delete();
                $agent->delete();
            });
    }
}
