<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Concerns\HasMaxWidth;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
class CompanyProfile extends Page
{
    use HasMaxWidth;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $navigationLabel = 'Profile';

    protected static ?string $navigationParentItem = 'General Settings';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return Filament::auth()->check();
    }

    public function getTitle(): string | Htmlable
    {
        return 'Profile';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Current Admin Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Text::make('Name')
                                    ->content(fn (): string => Filament::auth()->user()?->name ?? '-'),
                                Text::make('Email')
                                    ->content(fn (): string => Filament::auth()->user()?->email ?? '-'),
                                Text::make('Phone Number')
                                    ->content(fn (): string => Filament::auth()->user()?->phone ?? '-'),
                                Text::make('Basic Info')
                                    ->content(fn (): string => Filament::auth()->user()?->basic_info ?? '-')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make()
                    ->schema([
                        Actions::make([
                            Action::make('edit')
                                ->label('Edit Profile')
                                ->icon(Heroicon::OutlinedPencilSquare)
                                ->url(EditCompanyProfile::getUrl()),
                        ]),
                    ]),
            ]);
    }
}
