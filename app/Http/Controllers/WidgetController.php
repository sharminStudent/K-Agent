<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\Agent;
use App\Services\AgentService;
use App\Support\WorkspaceBranding;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class WidgetController extends Controller
{
    public function __construct(
        protected AgentService $agentService,
    ) {}

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
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
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
            'lightLogoUrl' => WorkspaceBranding::lightLogoUrl(),
            'darkLogoUrl' => WorkspaceBranding::darkLogoUrl(),
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
                    'welcome_message' => $agent->welcome_message,
                    'fallback_message' => $agent->fallback_message ?: 'I do not have enough information to answer that yet.',
                    'support_email' => $agent->support_email,
                    'support_phone' => $agent->support_phone,
                    'light_logo_url' => WorkspaceBranding::lightLogoUrl(),
                    'dark_logo_url' => WorkspaceBranding::darkLogoUrl(),
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
                'history' => $chatSession ? $this->conversationHistory($agent, $chatSession) : [],
            ],
        ]);
    }

    public function help(Request $request, string $widgetToken): JsonResponse
    {
        $agent = $this->agentService->resolveActiveAgentByWidgetToken($widgetToken);
        $query = trim((string) $request->query('q', ''));
        $articles = collect($this->helpCenterArticles($agent))
            ->when(
                $query !== '',
                fn ($collection) => $collection->filter(function (array $article) use ($query): bool {
                    $haystack = mb_strtolower($article['title'].' '.$article['content']);

                    return str_contains($haystack, mb_strtolower($query));
                })
            )
            ->values()
            ->map(fn (array $article) => [
                'id' => $article['id'],
                'title' => $article['title'],
                'excerpt' => $this->excerpt($article['content']),
                'updated_at' => null,
            ])
            ->all();

        return response()->json([
            'data' => [
                'articles' => $articles,
            ],
        ]);
    }

    public function helpArticle(string $widgetToken, string $knowledgeFile): JsonResponse
    {
        $agent = $this->agentService->resolveActiveAgentByWidgetToken($widgetToken);
        $article = collect($this->helpCenterArticles($agent))
            ->firstWhere('id', $knowledgeFile);

        abort_unless($article !== null, 404);

        return response()->json([
            'data' => [
                'article' => [
                    'id' => $article['id'],
                    'title' => $article['title'],
                    'content' => $article['content'],
                    'updated_at' => null,
                ],
            ],
        ]);
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

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function conversationHistory(Agent $agent, ChatSession $chatSession): array
    {
        $email = mb_strtolower(trim((string) $chatSession->visitor_email));

        if ($email === '') {
            return [];
        }

        return ChatSession::query()
            ->with(['messages' => fn ($query) => $query->orderBy('id')])
            ->where('agent_id', $agent->id)
            ->whereKeyNot($chatSession->getKey())
            ->whereRaw('LOWER(visitor_email) = ?', [$email])
            ->where(function (Builder $query): void {
                $query
                    ->whereHas('messages')
                    ->orWhereNotNull('last_message_at');
            })
            ->orderByRaw('COALESCE(last_message_at, created_at) desc')
            ->limit(12)
            ->get()
            ->map(function (ChatSession $historySession): array {
                $messages = $historySession->messages->map(fn ($message) => [
                    'message_id' => $message->public_id,
                    'role' => $message->role,
                    'content' => $message->content,
                    'created_at' => $message->created_at?->toISOString(),
                ])->all();

                $firstUserMessage = collect($messages)
                    ->firstWhere('role', 'user');
                $lastMessage = $messages !== [] ? $messages[array_key_last($messages)] : null;

                return [
                    'session_id' => $historySession->public_id,
                    'title' => mb_substr((string) ($firstUserMessage['content'] ?? 'Previous chat'), 0, 48),
                    'preview' => (string) ($lastMessage['content'] ?? 'Open conversation'),
                    'updated_at' => $historySession->last_message_at?->toISOString()
                        ?? $historySession->created_at?->toISOString(),
                    'transcript' => $messages,
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{id: string, title: string, content: string}>
     */
    protected function helpCenterArticles($agent): array
    {
        return collect($agent->settings['help_center_items'] ?? [])
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(function (array $item, int $index): array {
                return [
                    'id' => (string) ($index + 1),
                    'title' => trim((string) ($item['title'] ?? '')),
                    'content' => trim((string) ($item['description'] ?? '')),
                ];
            })
            ->filter(fn (array $item): bool => $item['title'] !== '' && $item['content'] !== '')
            ->values()
            ->all();
    }
}
