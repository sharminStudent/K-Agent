<?php

namespace App\Filament\SuperAdmin\Resources\Agents\Pages;

use App\Filament\SuperAdmin\Resources\Agents\AgentResource;
use App\Models\Agent;
use App\Services\ClientAccountService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class ManageAgents extends ManageRecords
{
    protected static string $resource = AgentResource::class;

    protected ?string $heading = 'Clients';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn () => AgentResource::streamCsvExport()),
            CreateAction::make()
                ->using(function (array $data): Agent {
                    $data = AgentResource::mutateProviderDataBeforeSave($data);

                    return DB::transaction(function () use ($data): Agent {
                        /** @var Agent $agent */
                        $agent = Agent::query()->create($data);

                        app(ClientAccountService::class)->syncPrimaryUser($agent, $data);

                        return $agent->fresh();
                    });
                }),
        ];
    }
}
