<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\ChatSession;

class PromptBuilderService
{
    /**
     * @param  array<int, array<string, mixed>>  $contextChunks
     * @return array{instructions: string, input: array<int, array<string, mixed>>}
     */
    public function buildChatPayload(Agent $agent, ChatSession $chatSession, array $contextChunks): array
    {
        $historyLimit = (int) config('services.rag.max_history_messages', 8);

        $history = $chatSession->messages()
            ->latest('id')
            ->take($historyLimit)
            ->get()
            ->reverse()
            ->map(function ($message): array {
                return [
                    'role' => $message->role === 'assistant' ? 'assistant' : 'user',
                    'content' => $message->content,
                ];
            })
            ->values()
            ->all();

        $context = collect($contextChunks)->map(function (array $chunk): string {
            return sprintf(
                '[%s | chunk %s | score %.3f] %s',
                $chunk['knowledge_file_name'] ?? 'unknown',
                $chunk['chunk_index'] ?? '?',
                $chunk['score'] ?? 0,
                $chunk['content'] ?? ''
            );
        })->implode("\n\n");

        return [
            'instructions' => trim(implode("\n\n", array_filter([
                'You are the company AI assistant for '.$agent->company_name.'.',
                $agent->system_prompt,
                'Answer using only the company context provided below when it is relevant.',
                'If the answer is not supported by the company context, say so instead of guessing.',
                $context !== '' ? "Company context:\n".$context : null,
            ]))),
            'input' => $history,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $contextChunks
     * @return array{instructions: string, input: array<int, array<string, mixed>>}
     */
    public function buildSingleTurnPayload(Agent $agent, string $message, array $contextChunks): array
    {
        $context = collect($contextChunks)->map(function (array $chunk): string {
            return sprintf(
                '[%s | chunk %s | score %.3f] %s',
                $chunk['knowledge_file_name'] ?? 'unknown',
                $chunk['chunk_index'] ?? '?',
                $chunk['score'] ?? 0,
                $chunk['content'] ?? ''
            );
        })->implode("\n\n");

        return [
            'instructions' => trim(implode("\n\n", array_filter([
                'You are the company AI assistant for '.$agent->company_name.'.',
                $agent->system_prompt,
                'Answer using only the company context provided below when it is relevant.',
                'If the answer is not supported by the company context, say so instead of guessing.',
                $context !== '' ? "Company context:\n".$context : null,
            ]))),
            'input' => [[
                'role' => 'user',
                'content' => $message,
            ]],
        ];
    }
}
