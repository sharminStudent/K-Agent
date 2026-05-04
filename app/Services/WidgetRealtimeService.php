<?php

namespace App\Services;

use App\Events\WidgetAssistantMessageCreated;
use App\Models\ChatMessage;
use App\Models\ChatSession;

class WidgetRealtimeService
{
    public function broadcastAssistantMessage(ChatSession $chatSession, ChatMessage $assistantMessage): void
    {
        $chunks = $this->chunkAssistantMessage((string) $assistantMessage->content);
        $totalChunks = count($chunks);

        foreach ($chunks as $index => $chunkContent) {
            broadcast(new WidgetAssistantMessageCreated(
                chatSession: $chatSession,
                assistantMessage: $assistantMessage,
                streamedContent: $chunkContent,
                chunkIndex: $index,
                chunkCount: $totalChunks,
                isFinalChunk: $index === $totalChunks - 1,
            ));
        }
    }

    /**
     * @return list<string>
     */
    protected function chunkAssistantMessage(string $content, int $targetSize = 48): array
    {
        $normalized = trim($content);

        if ($normalized === '') {
            return [''];
        }

        $words = preg_split('/\s+/u', $normalized) ?: [];
        $chunks = [];
        $buffer = '';

        foreach ($words as $word) {
            $candidate = $buffer === '' ? $word : $buffer.' '.$word;

            if (mb_strlen($candidate) <= $targetSize || $buffer === '') {
                $buffer = $candidate;

                continue;
            }

            $chunks[] = $buffer;
            $buffer = $word;
        }

        if ($buffer !== '') {
            $chunks[] = $buffer;
        }

        return $chunks === [] ? [''] : $chunks;
    }
}
