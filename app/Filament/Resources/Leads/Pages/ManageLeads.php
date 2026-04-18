<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;

class ManageLeads extends ManageRecords
{
    protected static string $resource = LeadResource::class;

    protected ?string $heading = 'Leads';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label('Export CSV')
                ->icon(\Filament\Support\Icons\Heroicon::OutlinedArrowDownTray)
                ->action(function () {
                    $agentId = auth()->user()?->agent_id;

                    abort_unless($agentId !== null, 403);

                    $filename = 'leads-'.now()->format('Y-m-d-His').'.csv';
                    $leads = \App\Models\Lead::query()
                        ->where('agent_id', $agentId)
                        ->with('chatSession')
                        ->orderByDesc('created_at')
                        ->get();

                    return response()->streamDownload(function () use ($leads): void {
                        $handle = fopen('php://output', 'w');

                        fputcsv($handle, [
                            'Name',
                            'Email',
                            'Phone',
                            'Status',
                            'Session ID',
                            'Notes',
                            'Created At',
                        ]);

                        foreach ($leads as $lead) {
                            fputcsv($handle, [
                                $lead->name,
                                $lead->email,
                                $lead->phone,
                                $lead->status,
                                $lead->chatSession?->public_id,
                                $lead->notes,
                                optional($lead->created_at)?->toDateTimeString(),
                            ]);
                        }

                        fclose($handle);
                    }, $filename, [
                        'Content-Type' => 'text/csv; charset=UTF-8',
                    ]);
                }),
        ];
    }
}
