@php
    $title = data_get($record?->meta, 'title') ?: $record?->original_name ?: 'Untitled';
    $description = data_get($record?->meta, 'description') ?: 'No description added.';
@endphp

<div class="ka-knowledge-info">
    <div class="ka-knowledge-field">
        <div class="ka-knowledge-label">Title</div>
        <div class="ka-knowledge-value">{{ $title }}</div>
    </div>

    <div class="ka-knowledge-field">
        <div class="ka-knowledge-label">Description</div>
        <div class="ka-knowledge-value ka-knowledge-description">{{ $description }}</div>
    </div>
</div>
