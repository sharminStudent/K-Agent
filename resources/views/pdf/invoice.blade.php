<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $paymentRecord->reference }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            margin: 0;
            font-size: 13px;
            line-height: 1.5;
            background: #ffffff;
        }

        .page {
            padding: 38px 42px;
        }

        .header {
            width: 100%;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 24px;
            margin-bottom: 28px;
        }

        .logo {
            max-width: 160px;
            max-height: 58px;
        }

        .logo-fallback {
            display: inline-block;
            padding: 10px 18px;
            border: 1px solid #fecdd3;
            border-radius: 999px;
            background: #fff1f2;
            color: #d3033d;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .headline {
            text-align: right;
        }

        .headline h1 {
            margin: 0;
            font-size: 30px;
            color: #d3033d;
            letter-spacing: 1px;
        }

        .headline p {
            margin: 6px 0 0;
            color: #475569;
        }

        .block {
            margin-bottom: 24px;
        }

        .section-title {
            margin: 0 0 10px;
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1.2px;
        }

        .card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 18px;
            background: #f8fafc;
        }

        .meta-table,
        .amount-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            vertical-align: top;
            padding: 6px 0;
        }

        .meta-label {
            width: 34%;
            color: #64748b;
        }

        .amount-table th,
        .amount-table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 10px;
            text-align: left;
        }

        .amount-table th {
            background: #f1f5f9;
            color: #334155;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .amount-table tfoot td {
            font-weight: 700;
            font-size: 14px;
            background: #fff7fa;
        }

        .status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            background: #fee2e2;
            color: #b91c1c;
        }

        .status.paid {
            background: #dcfce7;
            color: #166534;
        }

        .status.pending {
            background: #fef3c7;
            color: #b45309;
        }

        .status.refunded {
            background: #e2e8f0;
            color: #475569;
        }

        .footer {
            margin-top: 32px;
            color: #64748b;
            font-size: 11px;
        }
    </style>
</head>
<body>
@php
    $status = strtolower((string) $paymentRecord->status);
    $amount = number_format((float) $paymentRecord->amount, 2);
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
            </td>
            <td class="headline">
                <h1>INVOICE</h1>
                <p>Reference ID: {{ $paymentRecord->reference }}</p>
            </td>
        </tr>
    </table>

    <div class="block">
        <table width="100%">
            <tr>
                <td width="50%" style="padding-right: 10px;">
                    <p class="section-title">Billed To</p>
                    <div class="card">
                        <strong>{{ $agent?->company_name ?? 'Client' }}</strong><br>
                        {{ $agent?->contact_email ?: '-' }}<br>
                        {{ $agent?->support_phone ?: '-' }}<br>
                        {{ $agent?->website_url ?: '-' }}
                    </div>
                </td>
                <td width="50%" style="padding-left: 10px;">
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
                                <td>
                                    {{ optional($paymentRecord->billing_period_start)->format('M j, Y') ?? '-' }}
                                    -
                                    {{ optional($paymentRecord->billing_period_end)->format('M j, Y') ?? '-' }}
                                </td>
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
                <th width="120">Amount</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    Workspace billing for {{ $agent?->company_name ?? 'Client' }}
                    @if ($paymentRecord->notes)
                        <br><span style="color:#64748b;">{{ $paymentRecord->notes }}</span>
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
