<x-filament-widgets::widget>
    <x-filament::section>
        @if ($hasAgent)
            <div class="ka-setup-simple">
                <div class="ka-setup-simple-header">
                    <div>
                        <span class="ka-setup-label">Workspace</span>
                        <h2>{{ $companyName }}</h2>
                        <p>{{ $agentName }}</p>
                    </div>
                </div>
                <div class="ka-setup-simple-links">
                    <a href="{{ $agentUrl }}">Agent settings</a>
                    <a href="{{ $knowledgeUrl }}">Knowledge files</a>
                    <a href="{{ $leadUrl }}">Leads</a>
                    <a href="{{ $chatUrl }}">Chat logs</a>
                    @if ($widgetPreviewUrl)
                        <a href="{{ $widgetPreviewUrl }}" target="_blank" rel="noopener noreferrer">Widget preview</a>
                    @endif
                </div>
            </div>
        @else
            <div class="ka-setup-simple">
                <div class="ka-setup-simple-header">
                    <div>
                        <span class="ka-setup-label">Setup</span>
                        <h2>Finish company setup</h2>
                        <p>Create your company agent first. After that, upload knowledge and start tracking chat activity.</p>
                    </div>
                </div>

                <div class="ka-setup-simple-links">
                    <a href="{{ $agentUrl }}">Create company agent</a>
                    <a href="{{ $knowledgeUrl }}">Knowledge area</a>
                    <a href="{{ $chatUrl }}">Chat logs</a>
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
