<x-app-layout>
    <x-slot name="header"><div><p class="portal-eyebrow">Owner's workspace</p><h2 class="mt-1 font-serif text-3xl">Business overview</h2></div></x-slot>
    <div class="py-7 sm:py-10"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <form method="GET" class="report-filter report-panel">
            <div><label for="report-from">From</label><input id="report-from" type="date" name="from" value="{{ old('from', $from->toDateString()) }}" required /></div>
            <div><label for="report-to">To</label><input id="report-to" type="date" name="to" value="{{ old('to', $to->toDateString()) }}" required /></div>
            <button class="report-button">Update overview</button>
            <a href="{{ route('admin.reports.index') }}" class="report-link">This month</a>
            <a href="{{ route('admin.reports.index', ['from' => now('Asia/Manila')->toDateString(), 'to' => now('Asia/Manila')->toDateString()]) }}" class="report-link">Today</a>
            <a href="{{ route('admin.reports.index', ['from' => now('Asia/Manila')->subDays(6)->toDateString(), 'to' => now('Asia/Manila')->toDateString()]) }}" class="report-link">Last 7 days</a>
            @if($errors->any())<ul class="w-full text-sm text-red-700" role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
        </form>

        <div class="report-metrics">
            <section class="report-panel"><p class="report-label">Total collected</p><p class="report-value">&#8369;{{ number_format($collectedCents / 100, 2) }}</p><p class="report-note">@if($change !== null)<strong class="{{ $change >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $change > 0 ? '+' : '' }}{{ number_format($change, 1) }}%</strong> vs. previous {{ $days }} days@else No collections in the previous {{ $days }} days for comparison.@endif</p></section>
            <section class="report-panel"><p class="report-label">Paid appointments</p><p class="report-value">{{ number_format($paymentCount) }}</p><p class="report-note">Payments recorded within this period.</p></section>
            <section class="report-panel"><p class="report-label">Average payment</p><p class="report-value">&#8369;{{ number_format($averageCents / 100, 2) }}</p><p class="report-note">Collected per paid appointment.</p></section>
            <section class="report-panel"><p class="report-label">Discounts given</p><p class="report-value">&#8369;{{ number_format($discountCents / 100, 2) }}</p><p class="report-note">Already deducted from total collected.</p></section>
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="report-panel xl:col-span-2"><div class="report-heading"><div><h3>Collections over time</h3><p class="report-note">Recorded payments after discounts.</p></div><span class="report-tag">{{ $days }} days</span></div>
                @if($paymentCount)
                    <div class="report-chart" style="--report-columns: {{ $trend->count() }}" role="list" aria-label="Collections by date">
                        @foreach($trend as $bucket)<div role="listitem" class="report-chart-column"><span class="report-chart-amount">&#8369;{{ number_format($bucket['cents'] / 100) }}</span><div class="report-chart-track"><div class="report-chart-bar" style="height: {{ $bucket['cents'] ? max(2, round($bucket['cents'] / $trendMax * 100)) : 0 }}%"></div></div><span class="report-chart-date">{{ $bucket['label'] }}</span><span class="sr-only">PHP {{ number_format($bucket['cents'] / 100, 2) }}</span></div>@endforeach
                    </div>
                @else<p class="report-empty">No payments recorded in this period. Choose another date range to see collections.</p>@endif
            </section>
            <section class="report-panel"><h3>Payment methods</h3><p class="report-note">How clients paid in this period.</p><div class="mt-5 space-y-4">@forelse($methods as $method)<div class="report-row"><div><strong>{{ ucwords(str_replace('_', ' ', $method->method)) }}</strong><p class="report-note">{{ $method->total }} payment(s)</p></div><strong>&#8369;{{ number_format($method->collected, 2) }}</strong></div>@empty<p class="report-empty">No payment methods to show.</p>@endforelse</div><div class="report-subtotal"><span>Retail item sales</span><strong>&#8369;{{ number_format($retailGross, 2) }}</strong></div><p class="report-note">Retail sales are before appointment discounts. Collections include both services and retail.</p></section>
        </div>

        <section class="report-panel">
            <div class="report-heading"><div><h3>Inventory and product sales</h3><p class="report-note">Sales and stock movements for {{ $from->format('M j, Y') }} &ndash; {{ $to->format('M j, Y') }} (Philippine time).</p></div><a href="{{ route('admin.products.index') }}" class="report-link">Manage inventory &rarr;</a></div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div><p class="report-label">Active inventory items</p><p class="report-value">{{ number_format($inventorySummary->item_count) }}</p><p class="report-note">{{ $inventorySummary->retail_count }} retail &middot; {{ $inventorySummary->supply_count }} treatment supplies</p></div>
                <div><p class="report-label">Current stock at cost</p><p class="report-value">&#8369;{{ number_format($inventorySummary->stock_cost, 2) }}</p><p class="report-note">Active stock using current cost prices.</p></div>
                <div><p class="report-label">Retail sales in period</p><p class="report-value">&#8369;{{ number_format($retailGross, 2) }}</p><p class="report-note">Recorded product sales before appointment discounts.</p></div>
            </div>
            <p class="report-note mt-4">Stock on hand is the current balance, independent of the date filter. Received includes opening stock and restocking. Supplies used in treatments reduce inventory without adding retail sales.</p>
            <div class="mt-4 overflow-x-auto"><table class="report-table"><thead><tr><th scope="col">Item</th><th scope="col">Stock on hand</th><th scope="col">Received</th><th scope="col">Units sold</th><th scope="col">Retail sales</th><th scope="col">Treatment use</th><th scope="col">Adjustments</th></tr></thead><tbody>
                @forelse($inventoryRows as $product)
                    <tr><td><a href="{{ route('admin.products.show', $product->id) }}" class="report-link">{{ $product->name }}</a><span class="block text-xs text-[#786570]">{{ $product->type === 'retail' ? 'Retail product' : 'Treatment supply' }}{{ !$product->is_active ? ' · Inactive' : '' }}</span></td><td>{{ number_format($product->stock_quantity, 2) }}@if($product->is_active && $product->stock_quantity <= $product->reorder_level)<span class="block text-xs text-amber-700">Low stock</span>@endif</td><td>{{ number_format($product->stock_received, 2) }}</td><td>{{ number_format($product->sold_units, 2) }}</td><td>&#8369;{{ number_format($product->sales_gross, 2) }}</td><td>{{ number_format($product->treatment_used, 2) }}</td><td>{{ number_format($product->stock_adjusted, 2) }}</td></tr>
                @empty<tr><td colspan="7" class="report-empty">No inventory items yet. Add retail products or treatment supplies in Inventory.</td></tr>@endforelse
            </tbody></table></div>
            <div class="mt-5">{{ $inventoryRows->links() }}</div>
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="report-panel"><div class="report-heading"><div><h3>Booking activity</h3><p class="report-note">Visits scheduled within the selected dates.</p></div><a href="{{ route('studio.appointments.index') }}" class="report-link">View sessions &rarr;</a></div><div class="report-status-grid">
                @foreach(['pending' => 'Pending', 'scheduled' => 'Scheduled', 'completed' => 'Completed', 'declined' => 'Declined', 'cancelled' => 'Cancelled', 'no_show' => 'No-show'] as $status => $label)<div><span class="report-status-label report-status-{{ $status }}">{{ $label }}</span><strong>{{ number_format($statuses[$status] ?? 0) }}</strong></div>@endforeach
                </div><div class="report-row mt-5"><p>{{ number_format($visitCount) }} visits &middot; {{ number_format($clientCount) }} distinct clients</p><strong>{{ $completionRate }}% completed</strong></div><p class="report-note mt-3">Each appointment counts once, including visits with several services. Booking dates and payment dates may fall in different periods.</p>
            </section>
            <section class="report-panel"><div class="report-heading"><div><h3>Top services</h3><p class="report-note">Paid service items, ranked by sales before discounts.</p></div></div><div class="overflow-x-auto"><table class="report-table"><thead><tr><th scope="col">Service</th><th scope="col">Sessions sold</th><th scope="col">Item sales</th></tr></thead><tbody>@forelse($topServices as $service)<tr><td>{{ $service->description }}</td><td>{{ number_format($service->units) }}</td><td>&#8369;{{ number_format($service->gross, 2) }}</td></tr>@empty<tr><td colspan="3" class="report-empty">No paid services in this period.</td></tr>@endforelse</tbody></table></div></section>
        </div>

        <section class="report-panel"><div class="report-heading"><div><h3>Needs attention</h3><p class="report-note">Current studio activity, independent of the selected report dates.</p></div></div><div class="grid gap-5 md:grid-cols-3"><a class="report-attention" href="{{ route('studio.appointments.index', ['status' => 'pending']) }}"><strong>{{ $pendingCount }}</strong><span>Requests awaiting approval &rarr;</span></a><a class="report-attention" href="{{ route('studio.appointments.index', ['status' => 'scheduled']) }}"><strong>{{ $upcomingCount }}</strong><span>Scheduled visits in the next 7 days &rarr;</span></a><a class="report-attention" href="{{ route('admin.products.index', ['type' => 'low_stock']) }}"><strong>{{ $lowStockCount }}</strong><span>Active items at or below reorder level &rarr;</span></a></div>@if($lowStock->isNotEmpty())<div class="mt-5 flex flex-wrap gap-2">@foreach($lowStock as $product)<a href="{{ route('admin.products.show', $product) }}" class="report-stock-tag">{{ $product->name }} &middot; {{ number_format($product->stock_quantity, 2) }} left</a>@endforeach</div>@endif</section>

        <section class="report-panel"><div class="report-heading"><div><h3>Payment records</h3><p class="report-note">Latest payments within this period. Open a receipt for the item breakdown.</p></div></div><div class="overflow-x-auto"><table class="report-table"><thead><tr><th scope="col">Receipt / client</th><th scope="col">Paid</th><th scope="col">Method</th><th scope="col">Discount</th><th scope="col">Collected</th></tr></thead><tbody>@forelse($recentPayments as $payment)<tr><td><a href="{{ route('studio.payments.receipt', $payment) }}" class="report-link">{{ $payment->receipt_number ?? 'Receipt #'.$payment->id }}</a><span class="block text-xs text-[#786570]">{{ $payment->appointment->client->name }}</span></td><td>{{ $payment->paid_at->timezone('Asia/Manila')->format('M j, Y · g:i A') }}</td><td>{{ ucwords(str_replace('_', ' ', $payment->method)) }}</td><td>&#8369;{{ number_format($payment->discount_amount, 2) }}</td><td><strong>&#8369;{{ number_format($payment->amount, 2) }}</strong></td></tr>@empty<tr><td colspan="5" class="report-empty">No payments in this period.</td></tr>@endforelse</tbody></table></div><div class="mt-5">{{ $recentPayments->links() }}</div></section>

        <p class="report-note">Amounts reflect recorded collections, not profit. Expenses, refunds, and unpaid balances are not tracked in this overview.</p>
    </div></div>
</x-app-layout>
