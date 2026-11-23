<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Product;
use App\Models\InventoryMovement;
use App\Models\TreatmentInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BusinessReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $dates = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $today = now('Asia/Manila');
        $from = Carbon::parse($dates['from'] ?? $today->copy()->startOfMonth()->toDateString(), 'Asia/Manila')->startOfDay();
        $to = Carbon::parse($dates['to'] ?? $today->toDateString(), 'Asia/Manila')->endOfDay();
        $days = (int) $from->diffInDays($to->copy()->startOfDay(), false) + 1;
        if ($days < 1 || $days > 366) {
            throw ValidationException::withMessages(['to' => 'Choose an end date on or after the start date, covering up to 366 days.']);
        }
        $storageTimezone = config('app.timezone');
        $start = $from->copy()->timezone($storageTimezone);
        $end = $to->copy()->timezone($storageTimezone);
        $payments = Payment::whereBetween('paid_at', [$start, $end]);
        $summary = (clone $payments)->selectRaw('COUNT(*) as payment_count, COALESCE(SUM(amount), 0) as collected, COALESCE(SUM(discount_amount), 0) as discounts')->first();
        $collectedCents = (int) round((float) $summary->collected * 100);
        $discountCents = (int) round((float) $summary->discounts * 100);
        $previousCollected = Payment::whereBetween('paid_at', [
            $from->copy()->subDays($days)->timezone($storageTimezone),
            $from->copy()->subMicrosecond()->timezone($storageTimezone),
        ])->sum('amount');
        $change = (float) $previousCollected > 0 ? round((($collectedCents / 100) - (float) $previousCollected) / (float) $previousCollected * 100, 1) : null;

        $visits = Appointment::whereBetween('scheduled_at', [$start, $end]);
        $statuses = (clone $visits)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $visitCount = (int) $statuses->sum();
        $clientCount = (clone $visits)->distinct()->count('user_id');
        $methods = (clone $payments)->selectRaw('method, COUNT(*) as total, SUM(amount) as collected')->groupBy('method')->orderByDesc('collected')->get();
        $items = PaymentItem::whereHas('payment', fn ($query) => $query->whereBetween('paid_at', [$start, $end]));
        $topServices = (clone $items)->whereNull('product_id')
            ->selectRaw('description, SUM(quantity) as units, SUM(quantity * unit_price) as gross')
            ->groupBy('description')->orderByDesc('gross')->limit(7)->get();
        $retailGross = (clone $items)->whereNotNull('product_id')->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as gross')->first()->gross;
        $productSales = (clone $items)->whereNotNull('product_id')
            ->selectRaw('product_id, SUM(quantity) as sold_units, SUM(quantity * unit_price) as sales_gross')->groupBy('product_id');
        $stockActivity = InventoryMovement::whereBetween('created_at', [$start, $end])
            ->selectRaw("product_id, SUM(CASE WHEN type IN ('opening_stock', 'stock_in') THEN quantity ELSE 0 END) as stock_received, SUM(CASE WHEN type = 'treatment_usage' THEN -quantity ELSE 0 END) as treatment_used, SUM(CASE WHEN type = 'adjustment' THEN quantity ELSE 0 END) as stock_adjusted")
            ->groupBy('product_id');
        $inventoryRows = Product::query()
            ->leftJoinSub($productSales, 'period_sales', fn ($join) => $join->on('products.id', '=', 'period_sales.product_id'))
            ->leftJoinSub($stockActivity, 'period_stock', fn ($join) => $join->on('products.id', '=', 'period_stock.product_id'))
            ->select('products.*')
            ->selectRaw('COALESCE(sold_units, 0) as sold_units, COALESCE(sales_gross, 0) as sales_gross, COALESCE(stock_received, 0) as stock_received, COALESCE(treatment_used, 0) as treatment_used, COALESCE(stock_adjusted, 0) as stock_adjusted')
            ->orderBy('products.name')->paginate(10, ['*'], 'inventory_page')->withQueryString();
        $inventorySummary = Product::where('is_active', true)
            ->selectRaw("COUNT(*) as item_count, COALESCE(SUM(CASE WHEN type = 'retail' THEN 1 ELSE 0 END), 0) as retail_count, COALESCE(SUM(CASE WHEN type = 'supply' THEN 1 ELSE 0 END), 0) as supply_count, COALESCE(SUM(stock_quantity * cost_price), 0) as stock_cost")->first();

        // Use Philippine calendar dates for the trend, including days with no collections.
        $bucketSize = (int) ceil($days / 12);
        $trend = collect();
        for ($offset = 0; $offset < $days; $offset += $bucketSize) {
            $bucketFrom = $from->copy()->addDays($offset);
            $bucketTo = $bucketFrom->copy()->addDays(min($bucketSize - 1, $days - $offset - 1));
            $trend->push(['label' => $bucketFrom->format('M j').($bucketSize > 1 ? '–'.$bucketTo->format('M j') : ''), 'cents' => 0]);
        }
        foreach ((clone $payments)->select(['paid_at', 'amount'])->cursor() as $payment) {
            $offset = (int) $from->diffInDays($payment->paid_at->copy()->timezone('Asia/Manila')->startOfDay());
            $index = intdiv($offset, $bucketSize);
            $bucket = $trend[$index];
            $bucket['cents'] += (int) round((float) $payment->amount * 100);
            $trend[$index] = $bucket;
        }

        return view('admin.reports.index', [
            'from' => $from, 'to' => $to, 'days' => $days,
            'collectedCents' => $collectedCents, 'discountCents' => $discountCents,
            'paymentCount' => (int) $summary->payment_count,
            'averageCents' => $summary->payment_count ? (int) round($collectedCents / $summary->payment_count) : 0,
            'change' => $change, 'statuses' => $statuses, 'visitCount' => $visitCount, 'clientCount' => $clientCount,
            'completionRate' => $visitCount ? round(($statuses['completed'] ?? 0) / $visitCount * 100) : 0,
            'salesStaffTotals' => (clone $payments)->whereNotNull('sales_staff_id')->selectRaw('sales_staff_id, COUNT(*) as payment_count, SUM(amount) as collected')->with('salesStaff')->groupBy('sales_staff_id')->orderByDesc('collected')->get(),
            'outstandingInvoices' => TreatmentInvoice::with('firstAppointment.client')->withSum('payments as paid', 'amount')->get()->filter(fn ($invoice) => round((float) $invoice->total * 100) > round(((float) $invoice->paid + (float) $invoice->opening_credit) * 100)),
            'methods' => $methods, 'topServices' => $topServices, 'retailGross' => $retailGross,
            'inventoryRows' => $inventoryRows, 'inventorySummary' => $inventorySummary,
            'trend' => $trend, 'trendMax' => max(1, $trend->max('cents')),
            'recentPayments' => (clone $payments)->with('appointment.client')->orderByDesc('paid_at')->orderByDesc('id')->paginate(10)->withQueryString(),
            'lowStockCount' => Product::where('is_active', true)->whereColumn('stock_quantity', '<=', 'reorder_level')->count(),
            'lowStock' => Product::where('is_active', true)->whereColumn('stock_quantity', '<=', 'reorder_level')->orderBy('stock_quantity')->limit(5)->get(),
            'pendingCount' => Appointment::where('status', 'pending')->count(),
            'upcomingCount' => Appointment::where('status', 'scheduled')->whereBetween('scheduled_at', [
                $today->copy()->timezone($storageTimezone), $today->copy()->addDays(7)->timezone($storageTimezone),
            ])->count(),
        ]);
    }
}

