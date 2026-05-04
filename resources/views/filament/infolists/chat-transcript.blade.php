@php
    $messages = $messages ?? $record?->messages?->sortBy('id')->values() ?? collect();
@endphp

<div class="ka-transcript">
    <div class="ka-transcript-header">
        <h3 class="ka-transcript-title">Conversation</h3>
    </div>

    @if ($messages->isEmpty())
        <div class="ka-transcript-empty">
            No messages are available for this session yet.
        </div>
    @else
        <div class="ka-transcript-thread">
            @foreach ($messages as $message)
                @php
                    $isVisitor = $message->role === 'user';
                    $roleLabel = match ($message->role) {
                        'user' => 'Visitor',
                        'assistant' => 'Assistant',
                        default => str($message->role)->headline()->toString(),
                    };
                @endphp

                <article class="ka-transcript-message {{ $isVisitor ? 'is-visitor' : 'is-assistant' }}">
                    <div class="ka-transcript-message-meta">
                        <span class="ka-transcript-role">{{ $roleLabel }}</span>
                        <span class="ka-transcript-time">{{ $message->created_at?->format('M j, Y g:i A') }}</span>
                    </div>

                    <div class="ka-transcript-bubble">
                        {!! nl2br(e($message->content)) !!}
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
