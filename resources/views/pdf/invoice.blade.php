<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $paymentRecord->reference }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            margin: 0;
            font-size: 12px;
            line-height: 1.55;
            background: #ffffff;
        }

        .page {
            padding: 34px 38px;
        }

        .header {
            width: 100%;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 18px;
            margin-bottom: 26px;
            border-collapse: collapse;
        }

        .logo {
            width: 150px;
            max-height: 52px;
        }

        .logo-fallback {
            display: inline-block;
            padding: 6px 12px;
            border: 1px solid #d1d5db;
            color: #111827;
            font-size: 17px;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        .headline {
            text-align: right;
        }

        .headline h1 {
            margin: 0;
            font-size: 26px;
            color: #111827;
            letter-spacing: 0.6px;
        }

        .headline p {
            margin: 5px 0 0;
            color: #4b5563;
        }

        .block {
            margin-bottom: 24px;
        }

        .company-meta {
            margin-top: 10px;
            color: #4b5563;
            font-size: 11px;
        }

        .company-meta div {
            margin-bottom: 2px;
        }

        .two-col {
            width: 100%;
            border-collapse: collapse;
        }

        .two-col td {
            vertical-align: top;
        }

        .box-cell {
            width: 50%;
        }

        .section-title {
            margin: 0 0 10px;
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.9px;
        }

        .card {
            border: 1px solid #d1d5db;
            padding: 14px 16px;
            background: #ffffff;
            height: 126px;
        }

        .meta-table,
        .amount-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            vertical-align: top;
            padding: 5px 0;
        }

        .meta-label {
            width: 38%;
            color: #6b7280;
        }

        .amount-table th,
        .amount-table td {
            border: 1px solid #d1d5db;
            padding: 10px 12px;
            text-align: left;
            vertical-align: top;
        }

        .amount-table th {
            background: #f3f4f6;
            color: #111827;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .amount-table tfoot td {
            font-weight: 700;
            font-size: 14px;
            background: #f9fafb;
        }

        .status {
            display: inline-block;
            padding: 3px 8px;
            border: 1px solid #d1d5db;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #ffffff;
            color: #111827;
        }

        .status.paid {
            border-color: #bbf7d0;
            background: #f0fdf4;
            color: #166534;
        }

        .status.pending {
            border-color: #fde68a;
            background: #fffbeb;
            color: #92400e;
        }

        .status.failed {
            border-color: #fecaca;
            background: #fef2f2;
            color: #991b1b;
        }

        .status.refunded {
            border-color: #d1d5db;
            background: #f9fafb;
            color: #4b5563;
        }

        .footer {
            margin-top: 28px;
            padding-top: 14px;
            border-top: 1px solid #d1d5db;
            color: #6b7280;
            font-size: 11px;
        }

        .text-muted {
            color: #6b7280;
        }

        .amount-col {
            width: 140px;
        }
    </style>
</head>
<body>
@php
    $status = strtolower((string) $paymentRecord->status);
    $amount = number_format((float) $paymentRecord->amount, 2);
    $billingPeriod = trim(
        (optional($paymentRecord->billing_period_start)->format('M j, Y') ?? '-')
        .' to '.
        (optional($paymentRecord->billing_period_end)->format('M j, Y') ?? '-')
    );
@endphp
<div class="page">
    <table class="header">
        <tr>
            <td>
                @if ($logoDataUri)
                    <img src="{{ $logoDataUri }}" alt="K-Agent" class="logo">
                @else
                    <span class="logo-fallback">K-Agent</span>
                @endif
                <div class="company-meta">
                    <div>K-Agent</div>
                    <div>Internal Billing Invoice</div>
                </div>
            </td>
            <td class="headline">
                <h1>INVOICE</h1>
                <p>Reference ID: {{ $paymentRecord->reference }}</p>
            </td>
        </tr>
    </table>

    <div class="block">
        <table class="two-col">
            <tr>
                <td class="box-cell" style="padding-right: 10px;">
                    <p class="section-title">Billed To</p>
                    <div class="card">
                        <strong>{{ $agent?->company_name ?? 'Client' }}</strong><br>
                        {{ $agent?->contact_email ?: '-' }}<br>
                        {{ $agent?->support_phone ?: '-' }}<br>
                        {{ $agent?->website_url ?: '-' }}
                    </div>
                </td>
                <td class="box-cell" style="padding-left: 10px;">
                    <p class="section-title">Invoice Summary</p>
                    <div class="card">
                        <table class="meta-table">
                            <tr>
                                <td class="meta-label">Invoice Date</td>
                                <td>{{ optional($paymentRecord->created_at)->format('M j, Y') ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">Payment Date</td>
                                <td>{{ optional($paymentRecord->paid_at)->format('M j, Y g:i A') ?? 'Unpaid' }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">Billing Period</td>
                                <td>{{ $billingPeriod }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">Status</td>
                                <td><span class="status {{ $status }}">{{ str($status)->headline()->toString() }}</span></td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="block">
        <p class="section-title">Charge Details</p>
        <table class="amount-table">
            <thead>
            <tr>
                <th>Description</th>
                <th class="amount-col">Amount</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    Workspace billing for {{ $agent?->company_name ?? 'Client' }}
                    @if ($paymentRecord->notes)
                        <br><span class="text-muted">{{ $paymentRecord->notes }}</span>
                    @endif
                </td>
                <td>{{ $amount }} {{ strtoupper((string) $paymentRecord->currency) }}</td>
            </tr>
            </tbody>
            <tfoot>
            <tr>
                <td>Total</td>
                <td>{{ $amount }} {{ strtoupper((string) $paymentRecord->currency) }}</td>
            </tr>
            </tfoot>
        </table>
    </div>

    <div class="footer">
        This invoice was generated by K-Agent for internal billing and client payment tracking.
    </div>
</div>
</body>
</html>
