<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\View\View;

class ShowcaseController extends Controller
{
    public function showDummyClientSite(string $slug): View
    {
        $agent = Agent::query()
            ->where('slug', $slug)
            ->first();

        abort_unless($agent && $agent->allowsWorkspaceAccess(), 404);

        $companyName = trim((string) ($agent->company_name ?: $agent->name ?: 'Client Company'));
        $industry = trim((string) ($agent->industry ?: 'Service business'));
        $description = trim((string) ($agent->company_description ?: 'This showcase page demonstrates how the chat widget can be embedded into any customer website as a white-label SaaS experience.'));
        $websiteHost = parse_url((string) $agent->website_url, PHP_URL_HOST);

        return view('showcase.company-site', [
            'agent' => $agent,
            'companyName' => $companyName,
            'industry' => $industry,
            'description' => $description,
            'websiteHost' => $websiteHost ?: null,
            'scriptUrl' => route('widget.script', $agent->widget_token),
        ]);
    }
}
