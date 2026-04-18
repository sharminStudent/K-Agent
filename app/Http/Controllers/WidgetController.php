<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\KnowledgeFile;
use App\Services\AgentService;
use App\Services\RetrievalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class WidgetController extends Controller
{
    public function __construct(
        protected AgentService $agentService,
        protected RetrievalService $retrievalService,
    ) {
    }

    public function script(string $widgetToken): Response
    {
        $agent = $this->agentService->resolveActiveAgentByWidgetToken($widgetToken);

        $content = view('widget.embed-script', [
            'widgetToken' => $agent->widget_token,
            'frameUrl' => route('widget.frame', $agent->widget_token),
            'agentName' => $agent->name,
            'companyName' => $agent->company_name,
        ])->render();

        return response($content, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
        ]);
    }

    public function frame(string $widgetToken): View
    {
        $agent = $this->agentService->resolveActiveAgentByWidgetToken($widgetToken);

        return view('widget.frame', [
            'agent' => $agent,
            'bootstrapUrl' => route('widget.bootstrap', $agent->widget_token),
            'helpUrl' => route('widget.help', $agent->widget_token),
            'helpArticleBaseUrl' => url('/widget/'.$agent->widget_token.'/help'),
            'lightLogoUrl' => $agent->light_logo_url ?: $agent->logo_url ?: asset('images/fix.png'),
            'darkLogoUrl' => $agent->dark_logo_url ?: $agent->logo_url ?: asset('images/login_logo.png'),
        ]);
    }

    public function preview(string $widgetToken): View
    {
        $agent = $this->agentService->resolveActiveAgentByWidgetToken($widgetToken);

        return view('widget.preview', [
            'agent' => $agent,
            'scriptUrl' => route('widget.script', $agent->widget_token),
        ]);
    }

    public function bootstrap(Request $request, string $widgetToken): JsonResponse
    {
        $agent = $this->agentService->resolveActiveAgentByWidgetToken($widgetToken);

        $validated = $request->validate([
            'session_id' => ['nullable', 'string', 'max:26'],
        ]);

        $chatSession = null;

        if (! empty($validated['session_id'])) {
            $chatSession = ChatSession::query()
                ->with(['messages' => fn ($query) => $query->orderBy('id')])
                ->where('public_id', $validated['session_id'])
                ->where('agent_id', $agent->id)
                ->first();
        }

        return response()->json([
            'data' => [
                'agent' => [
                    'name' => $agent->name,
                    'company_name' => $agent->company_name,
                    'welcome_message' => $agent->welcome_message ?: 'Hi there. How can we help today?',
                    'fallback_message' => $agent->fallback_message ?: 'I do not have enough information to answer that yet.',
                    'support_email' => $agent->support_email,
                    'support_phone' => $agent->support_phone,
                    'light_logo_url' => $agent->light_logo_url ?: $agent->logo_url ?: asset('images/fix.png'),
                    'dark_logo_url' => $agent->dark_logo_url ?: $agent->logo_url ?: asset('images/login_logo.png'),
                ],
                'session' => $chatSession ? [
                    'session_id' => $chatSession->public_id,
                    'status' => $chatSession->status,
                    'visitor_name' => $chatSession->visitor_name,
                    'visitor_email' => $chatSession->visitor_email,
                    'visitor_phone' => $chatSession->visitor_phone,
                    'messages' => $chatSession->messages->map(fn ($message) => [
                        'message_id' => $message->public_id,
                        'role' => $message->role,
                        'content' => $message->content,
                        'created_at' => $message->created_at?->toISOString(),
                    ])->all(),
                ] : null,
            ],
        ]);
    }

    public function help(Request $request, string $widgetToken): JsonResponse
    {
        $agent = $this->agentService->resolveActiveAgentByWidgetToken($widgetToken);
        $query = trim((string) $request->query('q', ''));

        if ($query !== '') {
            $matches = $this->retrievalService->retrieveRelevantChunks($agent, $query, 8);
            $knowledgeFiles = KnowledgeFile::query()
                ->where('agent_id', $agent->id)
                ->whereIn('id', collect($matches)->pluck('knowledge_file_id')->filter()->unique()->all())
                ->get()
                ->keyBy('id');

            $articles = collect($matches)
                ->groupBy('knowledge_file_id')
                ->map(function ($group, $knowledgeFileId) use ($knowledgeFiles) {
                    $knowledgeFile = $knowledgeFiles->get((int) $knowledgeFileId);

                    if (! $knowledgeFile) {
                        return null;
                    }

                    $topMatch = $group->sortByDesc('score')->first();

                    return [
                        'id' => $knowledgeFile->id,
                        'title' => $knowledgeFile->original_name,
                        'excerpt' => $this->excerpt((string) ($topMatch['content'] ?? '')),
                        'updated_at' => $knowledgeFile->ingested_at?->toISOString(),
                    ];
                })
                ->filter()
                ->values()
                ->all();
        } else {
            $articles = KnowledgeFile::query()
                ->where('agent_id', $agent->id)
                ->where('status', 'ready')
                ->latest('ingested_at')
                ->limit(8)
                ->get()
                ->map(fn (KnowledgeFile $knowledgeFile) => [
                    'id' => $knowledgeFile->id,
                    'title' => $knowledgeFile->original_name,
                    'excerpt' => $this->excerpt($this->readProcessedText($knowledgeFile)),
                    'updated_at' => $knowledgeFile->ingested_at?->toISOString(),
                ])
                ->all();
        }

        return response()->json([
            'data' => [
                'articles' => $articles,
            ],
        ]);
    }

    public function helpArticle(string $widgetToken, KnowledgeFile $knowledgeFile): JsonResponse
    {
        $agent = $this->agentService->resolveActiveAgentByWidgetToken($widgetToken);

        abort_unless(
            $knowledgeFile->agent_id === $agent->id && $knowledgeFile->status === 'ready',
            404
        );

        return response()->json([
            'data' => [
                'article' => [
                    'id' => $knowledgeFile->id,
                    'title' => $knowledgeFile->original_name,
                    'content' => $this->readProcessedText($knowledgeFile),
                    'updated_at' => $knowledgeFile->ingested_at?->toISOString(),
                ],
            ],
        ]);
    }

    protected function readProcessedText(KnowledgeFile $knowledgeFile): string
    {
        $path = $knowledgeFile->meta['processed_text_path'] ?? null;

        if (! $path) {
            return '';
        }

        $disk = Storage::disk($knowledgeFile->disk);

        if (! $disk->exists($path)) {
            return '';
        }

        return trim((string) $disk->get($path));
    }

    protected function excerpt(string $text, int $limit = 160): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($text)) ?? '';

        if ($normalized === '') {
            return 'Open this article to read more.';
        }

        return mb_strlen($normalized) > $limit
            ? mb_substr($normalized, 0, $limit - 1).'...'
            : $normalized;
    }
}
