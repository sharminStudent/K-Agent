<?php

namespace App\Filament\SuperAdmin\Resources\Users;

use App\Filament\SuperAdmin\Resources\Users\Pages\ManageUsers;
use App\Filament\SuperAdmin\Resources\Users\Pages\ViewUser;
use App\Models\Agent;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Workspace Users';

    protected static string|\UnitEnum|null $navigationGroup = 'Management';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'workspace-users';

    public static function canViewAny(): bool
    {
        return (bool) Filament::auth()->user()?->isSuperAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Client Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('basic_info')
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->inline(false)
                            ->default(true),
                        Select::make('agent_id')
                            ->label('Company')
                            ->options(fn (): array => Agent::query()->orderBy('company_name')->pluck('company_name', 'id')->all())
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Client Summary')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email'),
                        TextEntry::make('phone'),
                        TextEntry::make('basic_info'),
                        TextEntry::make('agent.company_name')
                            ->label('Company'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('agent.company_name')
                    ->label('Company')
                    ->placeholder('Unassigned')
                    ->searchable(),
                TextColumn::make('phone')
                    ->toggleable(),
                SelectColumn::make('is_active')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),
                TextColumn::make('created_at')
                    ->since()
                    ->label('Created'),
            ])
            ->filters([
                SelectFilter::make('agent_id')
                    ->label('Company')
                    ->options(fn (): array => Agent::query()->orderBy('company_name')->pluck('company_name', 'id')->all()),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('toggleActive')
                    ->label(fn (User $record): string => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (User $record) => $record->is_active ? Heroicon::OutlinedNoSymbol : Heroicon::OutlinedCheckCircle)
                    ->color(fn (User $record): string => $record->is_active ? 'gray' : 'success')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $record->update([
                            'is_active' => ! $record->is_active,
                        ]);
                    }),
                EditAction::make()
                    ->label('View')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (User $record): string => static::getUrl('view', ['record' => $record])),
                EditAction::make()
                    ->label('Edit'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('agent')
            ->where('is_super_admin', false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
            'view' => ViewUser::route('/{record}'),
        ];
    }
}
