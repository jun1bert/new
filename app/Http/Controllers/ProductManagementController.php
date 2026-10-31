<?php
namespace App\Http\Controllers;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
class ProductManagementController extends Controller {
 public function index(Request $request): View { $type=$request->validate(['type'=>['nullable','in:retail,supply,low_stock']])['type']??null; $search=$request->string('search')->trim()->toString(); return view('admin.products.index',['products'=>Product::query()->when($search,fn($q)=>$q->where(fn($q)=>$q->where('name','like',"%{$search}%")->orWhere('sku','like',"%{$search}%")))->when($type==='low_stock',fn($q)=>$q->whereColumn('stock_quantity','<=','reorder_level'))->when(in_array($type,['retail','supply']),fn($q)=>$q->where('type',$type))->latest()->paginate(20)->withQueryString(),'type'=>$type,'search'=>$search,'counts'=>Product::selectRaw("count(*) total, sum(type = 'retail') retail, sum(type = 'supply') supply, sum(stock_quantity <= reorder_level) low_stock")->first()]); }
 public function store(Request $request): RedirectResponse { $request->merge(['_creating_product' => '1']); $data=$request->validate(['name'=>['required','string','max:120'],'sku'=>['nullable','string','max:60','unique:products,sku'],'type'=>['required','in:retail,supply'],'cost_price'=>['required','numeric','min:0','max:99999999.99','decimal:0,2'],'selling_price'=>['required','numeric','min:0','max:99999999.99','decimal:0,2'],'stock_quantity'=>['required','numeric','min:0','max:99999999.99','decimal:0,2'],'reorder_level'=>['nullable','numeric','min:0','max:99999999.99','decimal:0,2']]); $product=Product::create($data+['reorder_level'=>$data['reorder_level']??0]); if($product->stock_quantity>0) \App\Models\InventoryMovement::create(['product_id'=>$product->id,'recorded_by'=>$request->user()->id,'quantity'=>$product->stock_quantity,'type'=>'opening_stock','notes'=>'Opening stock']); return back()->with('status','Product added.'); }
 public function show(Product $product): View { return view('admin.products.show',['product'=>$product,'movements'=>$product->movements()->with('recorder')->latest()->get(),'sales'=>$product->paymentItems()->with('payment.appointment.client')->latest()->get()]); }
 public function adjust(Request $request, Product $product): RedirectResponse
 {
     $request->merge(['_adjusting_product_id' => (string) $product->id]);
     $data = $request->validate(['quantity' => ['required', 'numeric', 'not_in:0', 'decimal:0,2', 'between:-99999999.99,99999999.99'], 'notes' => ['nullable', 'string', 'max:255']]);
     \Illuminate\Support\Facades\DB::transaction(function () use ($request, $product, $data) {
         $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
         $quantityCents = (int) round((float) $data['quantity'] * 100);
         $newStockCents = (int) round((float) $product->stock_quantity * 100) + $quantityCents;
         if ($newStockCents < 0 || $newStockCents > 9999999999) {
             throw \Illuminate\Validation\ValidationException::withMessages(['quantity' => 'Stock must remain between 0 and 99,999,999.99.']);
         }
         $product->update(['stock_quantity' => number_format($newStockCents / 100, 2, '.', '')]);
         $product->movements()->create(['recorded_by' => $request->user()->id, 'quantity' => $data['quantity'], 'type' => $quantityCents > 0 ? 'stock_in' : 'adjustment', 'notes' => $data['notes'] ?? null]);
     });
     return back()->with('status', 'Stock updated.');
 }
}
