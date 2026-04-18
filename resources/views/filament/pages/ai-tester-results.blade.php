@php
    $contextChunks = $result['context_chunks'] ?? [];
    $prompt = $result['prompt'] ?? null;
@endphp

<div class="space-y-6 text-sm text-gray-800 dark:text-gray-100">
    <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Scenario</div>
            <div class="mt-2 font-medium">{{ $result['scenario'] ?? '-' }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Pipeline Source</div>
            <div class="mt-2 font-medium">{{ $result['source'] ?? '-' }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Fallback Used</div>
            <div class="mt-2 font-medium">{{ !empty($result['used_fallback']) ? 'Yes' : 'No' }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Context Chunks</div>
            <div class="mt-2 font-medium">{{ count($contextChunks) }}</div>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Assistant Output</div>
        <pre class="mt-3 whitespace-pre-wrap font-sans">{{ $result['content'] ?? '-' }}</pre>
    </div>

    @if (filled($result['error'] ?? null))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200">
            <div class="text-xs uppercase tracking-wide">Error</div>
            <div class="mt-2">{{ $result['error'] }}</div>
        </div>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Retrieved Context</div>
        @if (count($contextChunks))
            <div class="mt-3 space-y-3">
                @foreach ($contextChunks as $chunk)
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-white/10">
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $chunk['knowledge_file_name'] ?? 'unknown' }}
                            @if (isset($chunk['score']))
                                · score {{ number_format((float) $chunk['score'], 3) }}
                            @endif
                        </div>
                        <div class="mt-2 whitespace-pre-wrap">{{ $chunk['content'] ?? '' }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="mt-3 text-gray-500 dark:text-gray-400">No context chunks were used.</div>
        @endif
    </div>

    @if (is_array($prompt))
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Prompt Instructions</div>
            <pre class="mt-3 whitespace-pre-wrap font-sans">{{ $prompt['instructions'] ?? '' }}</pre>
        </div>
    @endif
</div>
