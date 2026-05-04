@php
    $messages = $record->messages->sortBy('id')->values();
    $summary = array_filter([
        'Session ID' => $record->public_id,
        'Status' => filled($record->status) ? str($record->status)->headline()->toString() : 'Unknown',
        'Visitor' => $record->visitor_name ?: 'Unknown visitor',
        'Email' => $record->visitor_email,
        'Phone' => $record->visitor_phone,
    ], fn ($value) => filled($value));
@endphp

<div class="ka-chat-modal">
    <div class="ka-chat-summary">
        @foreach ($summary as $label => $value)
            <div class="ka-chat-summary-item">
                <div class="ka-chat-summary-label">{{ $label }}</div>
                <div class="ka-chat-summary-value">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    @include('filament.infolists.chat-transcript', ['record' => $record, 'messages' => $messages])
</div>
