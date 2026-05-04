<?php

namespace App\Support;

use App\Models\PlatformSetting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class WorkspaceBranding
{
    public const LIGHT_LOGO_KEY = 'branding.light_logo_path';

    public const DARK_LOGO_KEY = 'branding.dark_logo_path';

    public const LOGIN_LOGO_KEY = 'branding.login_logo_path';

    public static function lightLogoUrl(): string
    {
        return static::publicUrl(static::setting(static::LIGHT_LOGO_KEY)) ?? asset('images/fix.png');
    }

    public static function darkLogoUrl(): string
    {
        return static::publicUrl(static::setting(static::DARK_LOGO_KEY)) ?? asset('images/login_logo.png');
    }

    public static function loginLogoUrl(): string
    {
        return static::publicUrl(static::setting(static::LOGIN_LOGO_KEY))
            ?? static::publicUrl(static::setting(static::DARK_LOGO_KEY))
            ?? asset('images/login_logo.png');
    }

    public static function setting(string $key): ?string
    {
        if (! Schema::hasTable('platform_settings')) {
            return null;
        }

        try {
            $value = PlatformSetting::query()
                ->where('key', $key)
                ->value('value');
        } catch (QueryException) {
            return null;
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    public static function updateSetting(string $key, ?string $value): void
    {
        if (! Schema::hasTable('platform_settings')) {
            return;
        }

        try {
            PlatformSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => filled($value) ? $value : null],
            );
        } catch (QueryException) {
        }
    }

    protected static function publicUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
