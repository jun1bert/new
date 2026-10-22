<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $payment->receipt_number }}</title>
    <style>body{margin:0;background:#f5f1eb;color:#4d4037;font-family:Arial,sans-serif}.receipt{max-width:640px;margin:40px auto;background:#fff;padding:42px;border-radius:16px;box-shadow:0 8px 30px #4d40371a}.brand{font-family:Georgia,serif;font-size:28px;letter-spacing:.14em}.muted{color:#66584e}.row{display:flex;justify-content:space-between;gap:20px;padding:12px 0;border-bottom:1px solid #eee8de}.total{font-size:22px;font-weight:bold;border:0}.badge{display:inline-block;background:#eaf8f0;color:#187146;border-radius:999px;padding:6px 10px;font-weight:bold;font-size:12px}@media print{body{background:#fff}.receipt{box-shadow:none;margin:0;max-width:none}.no-print{display:none}}</style>
</head>
<body>
    <main class="receipt">
        <div style="display:flex;justify-content:space-between;gap:20px"><div><div class="brand">LUNELLE</div><p class="muted" style="margin:6px 0 0">AESTHETICS</p></div><div style="text-align:right"><span class="badge">PAID</span><p class="muted">Receipt {{ $payment->receipt_number ?? 'Pending number' }}<br>{{ $payment->paid_at->format('M j, Y · g:i A') }}</p></div></div>
        <hr style="border:0;border-top:1px solid #eee8de;margin:28px 0">
        <p class="muted">Received from</p><h1 style="font-family:Georgia,serif;margin-top:4px">{{ $payment->appointment->client->name }}</h1>
        @foreach($payment->items as $item)<div class="row"><span>{{ $item->description }} @if($item->quantity > 1)× {{ $item->quantity }}@endif</span><strong>PHP {{ number_format((float) ($item->quantity * $item->unit_price), 2) }}</strong></div>@endforeach
        <div class="row"><span>Session</span><strong>{{ $payment->appointment->session_number }} of {{ $payment->appointment->service->session_count }}</strong></div>
        <div class="row"><span>Payment method</span><strong>{{ ucwords(str_replace('_', ' ', $payment->method)) }}</strong></div>
        @if($payment->reference)<div class="row"><span>Reference</span><strong>{{ $payment->reference }}</strong></div>@endif
        <div class="row total"><span>Total paid</span><span>PHP {{ number_format((float) $payment->amount, 2) }}</span></div>
        <p class="muted" style="font-size:12px;margin-top:28px">Recorded by {{ $payment->recorder?->name ?? 'Lunelle staff' }}. Thank you for choosing Lunelle Aesthetics.</p>
        <button class="no-print" onclick="window.print()" style="margin-top:18px;width:100%;border:0;border-radius:8px;background:#4d4037;color:#fff;padding:13px;font-weight:bold;cursor:pointer">Print or save as PDF</button>
    </main>
</body>
</html>
