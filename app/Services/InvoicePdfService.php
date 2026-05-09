<?php

namespace App\Services;

use App\Models\PaymentRecord;
use App\Support\WorkspaceBranding;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    public function render(PaymentRecord $paymentRecord): string
    {
        $paymentRecord->loadMissing('agent');

        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('pdf.invoice', [
            'paymentRecord' => $paymentRecord,
            'agent' => $paymentRecord->agent,
            'logoDataUri' => $this->logoDataUri(),
        ])->render());
        $dompdf->setPaper('a4');
        $dompdf->render();

        return $dompdf->output();
    }

    public function filename(PaymentRecord $paymentRecord): string
    {
        return 'invoice-'.strtolower((string) ($paymentRecord->reference ?: $paymentRecord->getKey())).'.pdf';
    }

    protected function logoDataUri(): ?string
    {
        $path = WorkspaceBranding::setting(WorkspaceBranding::LIGHT_LOGO_KEY);

        if (filled($path) && Storage::disk('public')->exists((string) $path)) {
            $absolutePath = Storage::disk('public')->path((string) $path);

            return $this->fileToDataUri($absolutePath);
        }

        return $this->fileToDataUri(public_path('images/fix.png'));
    }

    protected function fileToDataUri(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';

        if (! $this->canEmbedMimeType($mime)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    protected function canEmbedMimeType(string $mime): bool
    {
        if ($mime === 'image/svg+xml') {
            return true;
        }

        return extension_loaded('gd');
    }
}
