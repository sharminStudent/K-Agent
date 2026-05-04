<x-filament-panels::page>
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Review recent activity and lead alerts for this workspace.
            </p>

            @if ($this->getNotifications()->contains(fn ($notification) => $notification->read_at === null))
                <x-filament::button wire:click="markAllAsRead" color="gray">
                    Mark All Read
                </x-filament::button>
            @endif
        </div>

        @forelse ($this->getNotifications() as $notification)
            @php
                $data = $notification->data ?? [];
                $isUnread = $notification->read_at === null;
                $link = $data['url'] ?? null;
            @endphp

            <div @class([
                'rounded-2xl border p-4 shadow-sm transition',
                'border-primary-200 bg-primary-50/50 dark:border-primary-800 dark:bg-primary-950/20' => $isUnread,
                'border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900' => ! $isUnread,
            ])>
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                                {{ $data['title'] ?? 'Notification' }}
                            </h3>

                            @if ($isUnread)
                                <span class="rounded-full bg-primary-600 px-2 py-0.5 text-xs font-medium text-white">
                                    New
                                </span>
                            @endif
                        </div>

                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ $data['body'] ?? 'No details available.' }}
                        </p>

                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                            @if (filled($data['lead_name'] ?? null))
                                <span><strong>Lead:</strong> {{ $data['lead_name'] }}</span>
                            @endif
                            @if (filled($data['lead_email'] ?? null))
                                <span><strong>Email:</strong> {{ $data['lead_email'] }}</span>
                            @endif
                            @if (filled($data['chat_session_id'] ?? null))
                                <span><strong>Session:</strong> {{ $data['chat_session_id'] }}</span>
                            @endif
                            <span><strong>Time:</strong> {{ $notification->created_at?->format('M j, Y g:i A') }}</span>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        @if ($isUnread)
                            <x-filament::button
                                wire:click="markAsRead('{{ $notification->getKey() }}')"
                                color="gray"
                                size="sm"
                            >
                                Mark Read
                            </x-filament::button>
                        @endif

                        @if (filled($link))
                            <x-filament::button
                                tag="a"
                                href="{{ $link }}"
                                size="sm"
                            >
                                Open
                            </x-filament::button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                No notifications yet.
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
