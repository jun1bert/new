<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $payment->receipt_number }}</title>
    @vite(['resources/css/app.css'])
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px 12px; background: #f3edf0; color: #171717; font-family: 'Courier New', monospace; font-size: 11px; line-height: 1.5; }
        .receipt { width: 80mm; max-width: 100%; margin: 0 auto; padding: 5mm 4mm; background: white; box-shadow: 0 4px 24px #35232d12; }
        .receipt-header { text-align: center; }
        .receipt-logo { display: block; width: 54mm; max-width: 100%; height: auto; margin: 0 auto 10px; }
        .brand { font-family: Georgia, serif; font-size: 24px; letter-spacing: .16em; line-height: 1.2; }
        .brand-subtitle { font-size: 9px; letter-spacing: .22em; margin: 3px 0 12px; }
        .receipt-number { margin: 8px 0 0; overflow-wrap: anywhere; }
        .muted { color: #444; }
        .badge { display: inline-block; border: 1px solid #444; padding: 1px 8px; font-size: 10px; font-weight: bold; }
        hr { border: 0; border-top: 1px dashed #888; margin: 14px 0; }
        .client-label { margin: 0; font-size: 10px; }
        h1 { font-size: 14px; line-height: 1.4; margin: 3px 0 12px; overflow-wrap: anywhere; }
        .row { display: flex; justify-content: space-between; gap: 10px; padding: 7px 0; border-bottom: 1px dashed #ddd; break-inside: avoid; }
        .row > :first-child { min-width: 0; overflow-wrap: anywhere; }
        .row > :last-child { flex-shrink: 0; max-width: 55%; text-align: right; overflow-wrap: anywhere; }
        .total { font-size: 14px; font-weight: bold; border-top: 1px dashed #444; border-bottom: 1px dashed #444; margin-top: 9px; padding: 10px 0; }
        .supplies { margin-top: 16px; padding-top: 12px; border-top: 1px dashed #888; }
        .supplies h2 { font-size: 11px; margin: 0 0 4px; }
        .supplies p, .review-note { font-size: 10px; line-height: 1.5; margin: 0 0 8px; }
        .receipt-footer { text-align: center; font-size: 10px; margin: 16px 0 0; }
        .receipt-footer p { margin: 5px 0; overflow-wrap: anywhere; }
        .print-controls { width: 80mm; max-width: 100%; margin: 18px auto 0; text-align: center; font-family: Arial, sans-serif; }
        .print-controls button { width: 100%; border: 0; border-radius: 6px; background: var(--brand-moss); color: white; padding: 12px; font-weight: bold; cursor: pointer; }
        .print-controls p { font-size: 11px; color: var(--brand-text-muted); line-height: 1.6; }
        @page { margin: 0; }
        @media print {
            html, body { width: 80mm; margin: 0; padding: 0; background: white; color: black; }
            .receipt { width: 80mm; max-width: none; margin: 0; padding: 4mm; box-shadow: none; }
            .muted { color: black; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <main class="receipt">
        <header class="receipt-header">
            <img class="receipt-logo" src="{{ asset('images/branding/shafers-logo.png') }}" alt="Shafer's Skin Lovers Lounge">
            <span class="badge">PAYMENT RECEIPT</span>
            <p class="receipt-number">Receipt {{ $payment->receipt_number ?? 'Pending number' }}<br>{{ $payment->paid_at->copy()->timezone('Asia/Manila')->format('M j, Y') }} &middot; {{ $payment->paid_at->copy()->timezone('Asia/Manila')->format('g:i A') }}</p>
        </header>
        <hr>
        <p class="muted client-label">Received from</p><h1>{{ $payment->appointment->client->name }}</h1>
        @foreach($payment->items as $item)<div class="row"><span>{{ $item->description }} @if($item->quantity > 1)× {{ $item->quantity }}@endif</span><strong>PHP {{ number_format((float) ($item->quantity * $item->unit_price), 2) }}</strong></div>@endforeach
        @foreach($payment->appointment->booked_services as $service)
            <div class="row"><span>{{ $service->name }} session</span><strong>{{ $payment->appointment->session_number }} of {{ $service->pivot?->session_count ?? $service->session_count }}</strong></div>
        @endforeach
        <div class="row"><span>Payment method</span><strong>{{ ucwords(str_replace('_', ' ', $payment->method)) }}</strong></div>
        @if($payment->reference)<div class="row"><span>Reference</span><strong>{{ $payment->reference }}</strong></div>@endif
        @php($itemTotalCents = $payment->items->sum(fn ($item) => (int) round((float) $item->unit_price * 100) * (float) $item->quantity))
        @if((float) $payment->discount_amount > 0)
            @if(!$payment->treatment_invoice_id || $itemTotalCents > 0)<div class="row"><span>{{ $payment->treatment_invoice_id ? 'New items subtotal' : 'Subtotal' }}</span><strong>PHP {{ number_format($itemTotalCents / 100, 2) }}</strong></div>@endif
            <div class="row"><span>Discount @if($payment->discount_reason)({{ $payment->discount_reason }})@endif</span><strong>-PHP {{ number_format((float) $payment->discount_amount, 2) }}</strong></div>
        @endif
        @if(!$payment->treatment_invoice_id && abs($itemTotalCents - round((float) $payment->discount_amount * 100) - round((float) $payment->amount * 100)) >= 1)
            <div class="row"><span>Item total</span><strong>PHP {{ number_format($itemTotalCents / 100, 2) }}</strong></div>
            <p class="muted review-note">The recorded payment differs from the item total. Please ask the studio to review this payment record.</p>
        @endif
        <div class="row total"><span>Payment received</span><span>PHP {{ number_format((float) $payment->amount, 2) }}</span></div>
        @if($payment->invoice)<div class="row"><span>Current package total</span><strong>PHP {{ number_format((float) $payment->invoice->total, 2) }}</strong></div><div class="row"><span>Current total paid</span><strong>PHP {{ number_format($payment->invoice->paid_cents / 100, 2) }}</strong></div><div class="row"><span>Current balance</span><strong>PHP {{ number_format($payment->invoice->balance_cents / 100, 2) }}</strong></div>@endif
        @if($payment->appointment->supplyUsages->isNotEmpty())
            <section class="supplies" aria-labelledby="supplies-heading">
                <h2 id="supplies-heading">Treatment supplies used</h2>
                <p class="muted">Included in service &mdash; no additional charge.</p>
                @foreach($payment->appointment->supplyUsages as $usage)
                    <div class="row"><span>{{ $usage->product?->name ?? 'Treatment supply' }}</span><span>Qty: {{ number_format(abs((float) $usage->quantity), 2) }}</span></div>
                @endforeach
            </section>
        @endif
        <footer class="receipt-footer"><p>Recorded by {{ $payment->recorder?->name ?? 'studio staff' }}</p><p>Thank you for choosing<br>Shafer's Skin Lovers Lounge.</p></footer>
    </main>
    <div class="print-controls no-print"><button onclick="window.print()">Print or save as PDF</button></div>
</body>
</html>

