<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogLivewireTemporaryUpload
{
    public function handle(Request $request, Closure $next): Response
    {
        $files = $request->file('files', []);

        Log::channel('stderr')->info('Livewire temporary upload started.', [
            'content_length' => $request->header('content-length'),
            'file_count' => is_array($files) ? count($files) : 0,
            'files' => collect(is_array($files) ? $files : [])
                ->map(fn ($file) => [
                    'valid' => $file?->isValid(),
                    'original_name' => $file?->getClientOriginalName(),
                    'client_mime' => $file?->getClientMimeType(),
                    'mime' => $file?->getMimeType(),
                    'extension' => $file?->getClientOriginalExtension(),
                    'size' => $file?->getSize(),
                    'error' => $file?->getError(),
                    'error_message' => $file?->getErrorMessage(),
                ])
                ->values()
                ->all(),
        ]);

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            Log::channel('stderr')->error('Livewire temporary upload crashed.', [
                'message' => $exception->getMessage(),
                'class' => $exception::class,
            ]);

            throw $exception;
        }

        Log::channel('stderr')->info('Livewire temporary upload finished.', [
            'status' => $response->getStatusCode(),
            'content' => method_exists($response, 'getContent') ? $response->getContent() : null,
        ]);

        return $response;
    }
}
