<?php

namespace App\Http\Controllers\Filament;

use App\Http\Controllers\Controller;
use App\Models\PaymentRecord;
use App\Services\ActivityLogService;
use App\Services\InvoicePdfService;
use Illuminate\Http\Response;

class PaymentRecordInvoiceController extends Controller
{
    public function __invoke(PaymentRecord $paymentRecord, InvoicePdfService $invoicePdfService): Response
    {
        abort_unless(auth()->check(), 403);
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $content = $invoicePdfService->render($paymentRecord);
        $filename = $invoicePdfService->filename($paymentRecord);

        app(ActivityLogService::class)->log(
            event: 'billing.invoice.generated',
            description: 'A billing invoice PDF was generated.',
            category: 'billing',
            severity: 'normal',
            status: 'success',
            agent: $paymentRecord->agent,
            user: auth()->user(),
            subject: $paymentRecord,
            meta: [
                'reference' => $paymentRecord->reference,
                'filename' => $filename,
            ],
        );

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
