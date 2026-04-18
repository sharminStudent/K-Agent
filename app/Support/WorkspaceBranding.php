<?php

namespace App\Support;

use App\Models\Agent;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class WorkspaceBranding
{
    public static function lightLogoUrl(): string
    {
        return static::publicUrl(static::resolveAgent()?->light_logo_path) ?? asset('images/fix.png');
    }

    public static function darkLogoUrl(): string
    {
        return static::publicUrl(static::resolveAgent()?->dark_logo_path) ?? asset('images/login_logo.png');
    }

    public static function loginLogoUrl(): string
    {
        return static::darkLogoUrl();
    }

    protected static function resolveAgent(): ?Agent
    {
        $user = Filament::auth()->user();

        if ($user?->agent) {
            return $user->agent;
        }

        if (! Schema::hasTable('agents') || ! Schema::hasColumns('agents', [
            'login_logo_path',
            'light_logo_path',
            'dark_logo_path',
        ])) {
            return null;
        }

        try {
            $agentWithDarkLogo = Agent::query()
                ->whereNotNull('dark_logo_path')
                ->latest('updated_at')
                ->first();

            if ($agentWithDarkLogo) {
                return $agentWithDarkLogo;
            }

            return Agent::query()
                ->where(function ($query): void {
                    $query
                        ->whereNotNull('login_logo_path')
                        ->orWhereNotNull('light_logo_path')
                        ->orWhereNotNull('dark_logo_path');
                })
                ->latest('updated_at')
                ->first();
        } catch (QueryException) {
            return null;
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
