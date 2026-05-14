<?php

namespace App\Providers;

use App\Http\Middleware\LogLivewireTemporaryUpload;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        config([
            'livewire.temporary_file_upload.disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK', 'public'),
            'livewire.temporary_file_upload.middleware' => [
                LogLivewireTemporaryUpload::class,
                'throttle:60,1',
            ],
        ]);

        Event::listen(Login::class, function (Login $event): void {
            if (! $event->user instanceof User) {
                return;
            }

            app(ActivityLogService::class)->log(
                event: 'auth.login',
                description: 'User signed in to the workspace.',
                category: 'security',
                severity: 'normal',
                status: 'success',
                user: $event->user,
                meta: [
                    'remember' => $event->remember,
                ],
            );
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if (! $event->user instanceof User) {
                return;
            }

            app(ActivityLogService::class)->log(
                event: 'auth.logout',
                description: 'User signed out of the workspace.',
                category: 'security',
                severity: 'normal',
                status: 'success',
                user: $event->user,
            );
        });
    }
}
