<?php

namespace App\Filament\Auth;

use App\Support\WorkspaceBranding;
use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class Login extends BaseLogin
{
    public function getExtraBodyAttributes(): array
    {
        return [
            'style' => "background:
                linear-gradient(rgba(4, 13, 31, 0.45), rgba(4, 13, 31, 0.68)),
                url('".asset('images/new.jpg')."') center center / cover no-repeat fixed;",
        ];
    }

    public function hasLogo(): bool
    {
        return true;
    }

    public function getLogo(): string | Htmlable | null
    {
        return WorkspaceBranding::loginLogoUrl();
    }

    public function getHeading(): string | Htmlable | null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getHeading();
        }

        return '';
    }

    public function getSubheading(): string | Htmlable | null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getSubheading();
        }

        return new HtmlString('Sign in to manage your chat agent workspace.');
    }
}
