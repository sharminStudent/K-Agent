<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageLeads extends ManageRecords
{
    protected static string $resource = LeadResource::class;

    protected ?string $heading = 'Leads';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label('Export CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(function () {
                    $agentId = auth()->user()?->agent_id;

                    abort_unless($agentId !== null, 403);

                    $filename = 'leads-'.now()->format('Y-m-d-His').'.csv';
                    $leads = Lead::query()
                        ->where('agent_id', $agentId)
                        ->with('chatSession')
                        ->orderByDesc('created_at')
                        ->get();

                    return response()->streamDownload(function () use ($leads): void {
                        $handle = fopen('php://output', 'w');
                        fwrite($handle, "\xEF\xBB\xBF");

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
                                $lead->created_at?->format('m/d/Y h:i:s A'),
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
