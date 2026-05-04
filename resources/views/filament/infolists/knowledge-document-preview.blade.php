@php
    $title = $record?->original_name ?: 'Document';
@endphp

<div class="ka-knowledge-info">
    <div class="ka-knowledge-field">
        <div class="ka-knowledge-label">Document</div>
        <div class="ka-knowledge-value">{{ $title }}</div>
    </div>

    <div class="ka-knowledge-field">
        <div class="ka-knowledge-label">Preview</div>
        <div class="ka-knowledge-value ka-knowledge-description ka-knowledge-preview">{{ $preview }}</div>
    </div>
</div>
