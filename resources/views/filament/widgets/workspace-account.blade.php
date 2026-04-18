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
                <span>{{ $user->email }}</span>

                <form action="{{ filament()->getLogoutUrl() }}" method="post">
                    @csrf

                    <x-filament::button
                        color="gray"
                        size="sm"
                        tag="button"
                        type="submit"
                    >
                        Sign out
                    </x-filament::button>
                </form>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
