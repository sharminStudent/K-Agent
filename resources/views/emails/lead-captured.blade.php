@php
    $agent = $lead->agent;
    $chatSession = $lead->chatSession;
@endphp

<p>A new lead has been captured for {{ $agent?->company_name ?? 'your company' }}.</p>

<p><strong>Name:</strong> {{ $lead->name }}</p>
<p><strong>Email:</strong> {{ $lead->email ?: '-' }}</p>
<p><strong>Phone:</strong> {{ $lead->phone ?: '-' }}</p>
<p><strong>Status:</strong> {{ ucfirst((string) $lead->status) }}</p>
<p><strong>Chat Session:</strong> {{ $chatSession?->public_id ?: '-' }}</p>
<p><strong>Captured At:</strong> {{ $lead->created_at?->format('m/d/Y h:i:s A') ?: '-' }}</p>

@if (filled($lead->notes))
    <p><strong>Notes:</strong> {{ $lead->notes }}</p>
@endif

<p>Please review this lead in your dashboard.</p>
