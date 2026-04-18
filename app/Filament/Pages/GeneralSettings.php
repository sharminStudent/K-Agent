<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class GeneralSettings extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedCog8Tooth;

    protected static ?string $navigationLabel = 'General Settings';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return Filament::auth()->check();
    }

    public function getTitle(): string
    {
        return 'General Settings';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Settings Sections')
                    ->description('Manage admin-facing settings using the subsections below.')
                    ->schema([
                        Actions::make([
                            Action::make('profile')
                                ->label('Open Profile')
                                ->icon(Heroicon::OutlinedUserCircle)
                                ->url(CompanyProfile::getUrl()),
                        ]),
                    ]),
            ]);
    }
}
