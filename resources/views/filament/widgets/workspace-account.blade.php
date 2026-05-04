<x-filament-widgets::widget class="ka-workspace-account-widget">
    <x-filament::section>
        <div class="ka-workspace-account">
            <div class="ka-workspace-account-header">
                <div>
                    <p class="ka-workspace-account-label">{{ $statusLabel }}</p>
                    <h2>{{ filament()->getUserName($user) }}</h2>
                    <p>{{ $companyName ?: 'No company connected yet' }}</p>
                </div>
            </div>

            <div class="ka-account-summary">
                <div>
                    <span>Chats</span>
                    <strong>{{ $chatCount }}</strong>
                </div>
                <div>
                    <span>Leads</span>
                    <strong>{{ $leadCount }}</strong>
                </div>
                <div>
                    <span>Knowledge</span>
                    <strong>{{ $knowledgeCount }}</strong>
                </div>
            </div>

            <div class="ka-account-meta">
                <div class="ka-account-meta-stack">
                    <span>{{ $user->email }}</span>
                    <span>Platform support: <a href="mailto:{{ $supportContact }}">{{ $supportContact }}</a></span>
                    @if ($widgetPreviewUrl)
                        <a href="{{ $widgetPreviewUrl }}" target="_blank" rel="noopener noreferrer">Open widget preview</a>
                    @endif
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
