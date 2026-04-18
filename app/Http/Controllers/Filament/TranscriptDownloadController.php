<?php

namespace App\Http\Controllers\Filament;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Services\TranscriptService;
use Illuminate\Http\Response;

class TranscriptDownloadController extends Controller
{
    public function __invoke(ChatSession $chatSession, TranscriptService $transcriptService): Response
    {
        abort_unless(auth()->check(), 403);
        abort_unless(auth()->user()->agent_id === $chatSession->agent_id, 403);

        $content = $transcriptService->buildTranscript($chatSession);
        $filename = 'transcript-'.$chatSession->public_id.'.txt';

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
