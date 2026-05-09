@php
    use App\Filament\Pages\CompanyNotifications;
    use Filament\Support\Icons\Heroicon;

    $user = filament()->auth()->user();
    $unreadCount = $user?->unreadNotifications()->count() ?? 0;
    $badge = $unreadCount > 99 ? '99+' : ($unreadCount > 0 ? (string) $unreadCount : null);
@endphp

@if ($user && ! $user->isSuperAdmin())
    <x-filament::icon-button
        tag="a"
        :href="CompanyNotifications::getUrl()"
        :icon="Heroicon::OutlinedBell"
        color="gray"
        icon-size="lg"
        :label="'Notifications'"
        :badge="$badge"
        :badge-color="$unreadCount > 0 ? 'danger' : 'gray'"
        class="ka-topbar-notifications-btn"
    />
@endif
