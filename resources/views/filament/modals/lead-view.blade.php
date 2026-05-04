@php
    $status = $record->status;
    $details = array_filter([
        'Name' => $record->name,
        'Email' => $record->email,
        'Phone' => $record->phone,
        'Status' => filled($status) ? str($status)->headline()->toString() : 'Unknown',
        'Company' => $record->agent?->company_name,
        'Chat Session' => $record->chatSession?->public_id,
        'Created' => $record->created_at?->format('M j, Y g:i A'),
    ], fn ($value) => filled($value));
@endphp

<div class="ka-lead-modal">
    <div class="ka-lead-header">
        <div class="ka-lead-eyebrow">Lead Overview</div>
        <div class="ka-lead-headline">{{ $record->name ?: 'Lead Details' }}</div>
    </div>

    <div class="ka-lead-grid">
        @foreach ($details as $label => $value)
            <div class="ka-lead-card">
                <div class="ka-lead-label">{{ $label }}</div>
                <div class="ka-lead-value {{ $label === 'Status' ? 'ka-lead-status ka-lead-status-' . ($status ?: 'unknown') : '' }}">{{ $value }}</div>
            </div>
        @endforeach
    </div>
</div>
