<?php

namespace App\Http\Controllers;

use App\Brands;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\PurchaseLine;
use App\PurchaseRequisition;
use App\TaxRate;
use App\Transaction;
use App\TransactionSellLine;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\Variation;
use App\VariationLocationDetails;
use Carbon\Carbon;
use App\Utils\ProductUtil;
use DB;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PurchaseRequisitionController extends Controller
{
    protected $commonUtil;

    protected $transactionUtil;

    protected $productUtil;
    /**
     * Constructor
     *
     * @param  Util  $commonUtil
     * @return void
     */

    public function __construct(Util $commonUtil, TransactionUtil $transactionUtil, ProductUtil $productUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;

        $this->purchaseRequisitionStatuses = [
            'ordered' => [
                'label' => __('lang_v1.ordered'),
                'class' => 'bg-info',
            ],
            'partial' => [
                'label' => __('lang_v1.partial'),
                'class' => 'bg-yellow',
            ],
            'completed' => [
                'label' => __('restaurant.completed'),
                'class' => 'bg-green',
            ],
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('purchase_requisition.view_all') && !auth()->user()->can('purchase_requisition.view_own')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $purchase_requisitions = PurchaseRequisition::join(
                'business_locations AS BS',
                'purchase_requisitions.location_id',
                '=',
                'BS.id'
            )
            ->join('users as u', 'purchase_requisitions.requisition_by', '=', 'u.id')
            ->select(
                'purchase_requisitions.id',
                'purchase_requisitions.requisition_no',
                'BS.name as location_name',
                'purchase_requisitions.created_at',
                DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by")
            )
            ->groupBy('purchase_requisitions.id');

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $purchase_requisitions->whereIn('purchase_requisitions.location_id', $permitted_locations);
            }

            if (!empty(request()->location_id)) {
                $purchase_requisitions->where('purchase_requisitions.location_id', request()->location_id);
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end = request()->end_date;
                $purchase_requisitions->whereDate('purchase_requisitions.created_at', '>=', $start)
                    ->whereDate('purchase_requisitions.created_at', '<=', $end);
            }

            if (!auth()->user()->can('purchase_requisition.view_all') && auth()->user()->can('purchase_requisition.view_own')) {
                $purchase_requisitions->where('purchase_requisitions.requisition_by', request()->session()->get('user.id'));
            }

            return Datatables::of($purchase_requisitions)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                                data-toggle="dropdown" aria-expanded="false">' .
                        __('messages.actions') .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                </span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-left" role="menu">';
                    $html .= '<li><a href="#" data-href="' . action([\App\Http\Controllers\PurchaseRequisitionController::class, 'show'], [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-eye" aria-hidden="true"></i>' . __('messages.view') . '</a></li>';

                    if (auth()->user()->can('purchase_requisition.delete')) {
                        $html .= '<li><a href="' . action([\App\Http\Controllers\PurchaseRequisitionController::class, 'destroy'], [$row->id]) . '" class="delete-purchase-requisition"><i class="fas fa-trash"></i>' . __('messages.delete') . '</a></li>';
                    }

                    $html .= '</ul></div>';

                    return $html;
                })
                ->removeColumn('id')
                ->editColumn('created_at', '{{@format_datetime($created_at)}}')
                ->setRowAttr([
                    'data-href' => function ($row) {
                        return  action([\App\Http\Controllers\PurchaseRequisitionController::class, 'show'], [$row->id]);
                    },
                ])
                ->rawColumns(['action'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('purchase_requisition.index')->with(compact('business_locations'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('purchase_requisition.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id);

        $categories = Category::forDropdown($business_id, 'product');

        $brands = Brands::forDropdown($business_id);

        return view('purchase_requisition.create')->with(compact('business_locations', 'categories', 'brands'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function generateRequisitionNumber()
    {
        $last_requisition = PurchaseRequisition::latest('id')->first();
        $next_id = $last_requisition ? $last_requisition->id + 1 : 1;
        return 'Requisition-' . $next_id;
    }

    protected function createPurchaseOrder($supplier_id, $purchase_lines, $transaction_data, $purchase_requisition_id)
    {
        $transaction = Transaction::create($transaction_data);

        foreach ($purchase_lines as $purchase_line) {
            $quantity = isset($purchase_line['quantity']) ? $this->commonUtil->num_uf($purchase_line['quantity']) : 0;
            $secondary_unit_quantity = isset($purchase_line['secondary_unit_quantity']) ? $this->commonUtil->num_uf($purchase_line['secondary_unit_quantity']) : 0;

            if (!empty($quantity) || !empty($secondary_unit_quantity)) {
                $variation = Variation::findOrFail($purchase_line['variation_id']);

                $transaction->purchase_lines()->create([
                    'product_id' => $purchase_line['product_id'],
                    'variation_id' => $purchase_line['variation_id'],
                    'quantity' => $quantity,
                    'secondary_unit_quantity' => $secondary_unit_quantity,
                    'pp_without_discount' => $variation->default_purchase_price,
                    'purchase_price' => $variation->default_purchase_price,
                    'purchase_price_inc_tax' => $variation->dpp_inc_tax,
                    'purchase_requisition_id' => $purchase_requisition_id,
                ]);
            }
        }
    }


    public function store(Request $request)
    {
        if (!auth()->user()->can('purchase_requisition.create')) {
            abort(403, 'Unauthorized action.');
        }

        $validatedData = $request->validate([
            'purchases.*.supplier_id' => 'required|integer|exists:contacts,id',
            'purchases.*.product_id' => 'required|integer|exists:products,id',
            'purchases.*.variation_id' => 'required|integer|exists:variations,id',
            'purchases.*.quantity' => 'required|numeric|min:1',
            'purchases.*.secondary_unit_quantity' => 'nullable|numeric|min:0',
            'location_id' => 'required|integer|exists:business_locations,id',
            'notes' => 'nullable|string',
            'delivery_date' => 'nullable|date',
        ]);

        $business_id = auth()->user()->business_id;
        $created_by = auth()->user()->id;
        $transaction_date = \Carbon\Carbon::now()->toDateTimeString();
        $delivery_date = !empty($request->input('delivery_date')) ? $this->commonUtil->uf_date($request->input('delivery_date'), true) : \Carbon\Carbon::now()->toDateTimeString();

        $purchases_by_supplier = [];

        \Log::info('Request Data:', $request->all());

        $purchases = $request->input('purchases', []);
        if (!is_array($purchases)) {
            return response()->json(['error' => 'Invalid purchases data'], 400);
        }

        foreach ($purchases as $purchase_line) {
            $supplier_id = $purchase_line['supplier_id'];
            if (!isset($purchases_by_supplier[$supplier_id])) {
                $purchases_by_supplier[$supplier_id] = [];
            }
            $purchases_by_supplier[$supplier_id][] = $purchase_line;
        }

        DB::beginTransaction();

        try {
            $requisition_no = $this->generateRequisitionNumber();

            $purchase_requisition = PurchaseRequisition::create([
                'requisition_no' => $requisition_no,
                'requisition_by' => $created_by,
                'updated_by' => $request->input('updated_by', $created_by),
                'location_id' => $request->input('location_id'),
                'notes' => $request->input('notes'),
            ]);

            foreach ($purchases_by_supplier as $supplier_id => $purchase_lines) {
                $transaction_data = [
                    'business_id' => $business_id,
                    'location_id' => $request->input('location_id'),
                    'type' => 'purchase_order',
                    'status' => 'ordered',
                    'created_by' => $created_by,
                    'transaction_date' => $transaction_date,
                    'contact_id' => $supplier_id,
                    'delivery_date' => $delivery_date,
                    'ref_no' => $this->productUtil->generateReferenceNumber('purchase_order', $this->productUtil->setAndGetReferenceCount('purchase_order')),
                ];

                $this->createPurchaseOrder($supplier_id, $purchase_lines, $transaction_data, $purchase_requisition->id);
            }

            DB::commit();

            return response()->json(['success' => true, 'msg' => 'Purchase Requisition created successfully.', 'url' => route('purchase-requisition.index')]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error occurred while creating purchase requisition: ' . $e->getMessage());
            return response()->json(['success' => false, 'msg' => 'An error occurred while creating the purchase requisition.'], 500);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!auth()->user()->can('purchase_requisition.view_all') && !auth()->user()->can('purchase_requisition.view_own')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // Fetch the purchase requisition details
        $purchaseRequisition = PurchaseRequisition::with('location', 'requisitionBy')->findOrFail($id);
        $location_name = $purchaseRequisition->location->name;
        $requisition_date = $purchaseRequisition->created_at;
        $requisition_by_name = $purchaseRequisition->requisitionBy->first_name . ' ' . $purchaseRequisition->requisitionBy->last_name;

        // Fetch the purchase requisition lines associated with the given ID
        $purchaseLines = PurchaseLine::where('purchase_requisition_id', $id)
            ->with(['product', 'variations.product_variation', 'sub_unit'])
            ->get();

        $last30DaysStart = now()->subDays(30)->startOfDay();
        $last30DaysEnd = now()->endOfDay();

        // Fetch current stock, sold quantity, and last supplier for each purchase line
        foreach ($purchaseLines as $line) {
            // Get current stock
            $line->current_stock = VariationLocationDetails::where('variation_id', $line->variation_id)
                ->where('location_id', $purchaseRequisition->location_id)
                ->sum('qty_available');

            // Get the quantity of products sold in the last 30 days
            $line->products_sold = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
                ->where('t.business_id', $business_id)
                ->where('t.location_id', $purchaseRequisition->location_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->where('transaction_sell_lines.variation_id', $line->variation_id)
                ->whereBetween('t.transaction_date', [$last30DaysStart, $last30DaysEnd])
                ->sum('transaction_sell_lines.quantity');

            // Get last supplier
            $lastSupplier = PurchaseLine::join('transactions as t', 'purchase_lines.transaction_id', '=', 't.id')
                ->join('contacts as c', 't.contact_id', '=', 'c.id')
                ->where('purchase_lines.variation_id', $line->variation_id)
                ->where('t.location_id', $purchaseRequisition->location_id)
                ->where('t.type', 'purchase')
                ->orderBy('t.transaction_date', 'desc')
                ->select('c.id as supplier_id', 'c.supplier_business_name as supplier_name')
                ->first();

            $line->last_supplier = $lastSupplier ? $lastSupplier->supplier_name : 'N/A';
        }

        return view('purchase_requisition.show')
            ->with(compact('purchaseRequisition', 'purchaseLines', 'location_name', 'requisition_by_name', 'requisition_date'));
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('purchase_requisition.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            if (request()->ajax()) {

                $purchaseRequisition = PurchaseRequisition::findOrFail($id);

                $purchaseRequisition->delete();

                $output = [
                    'success' => true,
                    'msg' => __('lang_v1.deleted_success'),
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => $e->getMessage(),
            ];
        }

        return $output;
    }

    public function getRequisitionProducts(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id', null);

        $vld_str = '';
        if (!empty($location_id)) {
            $vld_str = "AND vld.location_id=$location_id";
        }
        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);
            $today = $request->get('transaction_date');
            $single = $request->get('single');

            $query = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
                ->join('variations as v', 'transaction_sell_lines.variation_id', '=', 'v.id')
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->leftjoin('categories as cat', 'p.category_id', '=', 'cat.id')
                ->leftjoin('brands as b', 'p.brand_id', '=', 'b.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->select(
                    'p.name as product_name',
                    'p.enable_stock',
                    'cat.name as category_name',
                    'b.name as brand_name',
                    'p.type as product_type',
                    'pv.name as product_variation',
                    'v.name as variation_name',
                    'v.sub_sku',
                    't.id as transaction_id',
                    't.transaction_date as transaction_date',
                    'transaction_sell_lines.parent_sell_line_id',
                    DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m-%d") as formated_date'),
                    DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.variation_id=v.id $vld_str) as current_stock"),
                    DB::raw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as total_qty_sold'),
                    'u.short_name as unit',
                    DB::raw('SUM((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal')
                )
                ->groupBy('v.id');

            if ($single == 2) {
                $query->groupBy('formated_date');
            }
            if (!empty($today)) {
                $query->whereDate('t.transaction_date', $today);
            }

            if (!empty($variation_id)) {
                $query->where('transaction_sell_lines.variation_id', $variation_id);
            }
            $end_date = now()->format('Y-m-d');
            $start_date = now()->subDays(60)->format('Y-m-d');
            $query->whereDate('t.transaction_date', '>=', $start_date)
                ->whereDate('t.transaction_date', '<=', $end_date);


            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $customer_id = $request->get('customer_id', null);
            if (!empty($customer_id)) {
                $query->where('t.contact_id', $customer_id);
            }

            $customer_group_id = $request->get('customer_group_id', null);
            if (!empty($customer_group_id)) {
                $query->leftjoin('contacts AS c', 't.contact_id', '=', 'c.id')
                    ->leftjoin('customer_groups AS CG', 'c.customer_group_id', '=', 'CG.id')
                    ->where('CG.id', $customer_group_id);
            }

            $category_id = $request->get('category_id', null);
            if (!empty($category_id)) {
                $query->whereIn('p.category_id', $category_id);
            }

            $brand_id = $request->get('brand_id', null);
            if (!empty($brand_id)) {
                $query->whereIn('p.brand_id', $brand_id);
            }
            $unit_ids = request()->get('unit_id', null);
            if (!empty($unit_ids)) {
                $query->whereIn('p.unit_id', $unit_ids);
            }

            $tax_ids = request()->get('tax_id', null);
            if (!empty($tax_ids)) {
                $query->whereIn('p.tax', $tax_ids);
            }

            $types = request()->get('type', null);
            if (!empty($types)) {
                $query->whereIn('p.type', $types);
            }
            return Datatables::of($query)
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }
                    $html = '<a href="#" data-href="' . action([\App\Http\Controllers\SellController::class, 'show'], [$row->transaction_id]) . '" class="btn-modal" data-container=".view_modal">' . $product_name . '</a>';
                    return $html;
                })
                ->editColumn('transaction_date', '{{@format_date($formated_date)}}')
                ->editColumn('total_qty_sold', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float) $row->total_qty_sold . '" data-unit="' . $row->unit . '" >' . (float) $row->total_qty_sold . '</span> ' . $row->unit;
                })
                ->editColumn('current_stock', function ($row) use ($start_date) {
                    if ($row->enable_stock) {
                        return '<span data-is_quantity="true" class="display_currency current_stock" data-currency_symbol=false data-orig-value="' . (float) $row->current_stock . '" data-unit="' . $row->unit . '" >' . (float) $row->current_stock . '</span> ' . $row->unit;
                    } else {
                        return '';
                    }
                })
                ->editColumn('subtotal', function ($row) {
                    $class = is_null($row->parent_sell_line_id) ? 'row_subtotal' : '';

                    return '<span class="' . $class . '" data-orig-value="' . $row->subtotal . '">' .
                        $this->transactionUtil->num_f($row->subtotal, true) . '</span>';
                })
                ->editColumn('transaction_date', '{{format_datetime($transaction_date)}}')
                ->rawColumns(['product_name', 'current_stock', 'subtotal', 'total_qty_sold'])
                ->make(true);
        }
    }

    public function getPurchaseRequisitions($location_id)
    {
        $business_id = request()->session()->get('user.business_id');

        $purchase_requisitions = Transaction::where('business_id', $business_id)
            ->where('type', 'purchase_requisition')
            ->whereIn('status', ['partial', 'ordered'])
            ->where('location_id', $location_id)
            ->select('ref_no as text', 'id')
            ->get();

        return $purchase_requisitions;
    }

    public function getPurchaseRequisitionLines($purchase_requisition_id)
    {
        $business_id = request()->session()->get('user.business_id');

        $purchase_requisition = Transaction::where('business_id', $business_id)
            ->where('type', 'purchase_requisition')
            ->with([
                'purchase_lines', 'purchase_lines.variations',
                'purchase_lines.product', 'purchase_lines.product.unit', 'purchase_lines.variations.product_variation',
            ])
            ->findOrFail($purchase_requisition_id);

        $taxes = TaxRate::where('business_id', $business_id)
            ->ExcludeForTaxGroup()
            ->get();

        $sub_units_array = [];
        foreach ($purchase_requisition->purchase_lines as $pl) {
            $sub_units_array[$pl->id] = $this->transactionUtil->getSubUnits($business_id, $pl->product->unit->id, false, $pl->product_id);
        }
        $hide_tax = request()->session()->get('business.enable_inline_tax') == 1 ? '' : 'hide';
        $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);
        $row_count = request()->input('row_count');
        $is_purchase_order = true;
        $html = view('purchase_requisition.partials.purchase_requisition_lines')
            ->with(compact(
                'purchase_requisition',
                'taxes',
                'hide_tax',
                'currency_details',
                'row_count',
                'sub_units_array',
                'is_purchase_order'
            ))->render();

        return [
            'html' => $html,
        ];
    }

    protected function getProductRequisitionDetails(Request $request)
    {
        $business_id = auth()->user()->business_id;
        $location_id = request('business_location_id') ?? 1;
        $start_date = Carbon::parse(request('start_date'))->startOfDay();
        $end_date = Carbon::parse(request('end_date'))->endOfDay();
        $category_ids = request('category_id', []);
        $brand_ids = request('brand_id', []);
        $last30DaysStart = now()->subDays(30)->startOfDay();
        $last30DaysEnd = now()->endOfDay();

        // Step 1: Get all products sold in the specified date range
        $productsSold = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
                        ->join('variations as v', 'transaction_sell_lines.variation_id', '=', 'v.id')
                        ->join('products as p', 'v.product_id', '=', 'p.id')
                        ->leftJoin('categories as cat', 'p.category_id', '=', 'cat.id')
                        ->leftJoin('brands as b', 'p.brand_id', '=', 'b.id')
                        ->where('t.business_id', $business_id)
                        ->where('t.location_id', $location_id)
                        ->where('t.type', 'sell')
                        ->where('t.status', 'final')
                        ->whereBetween('t.transaction_date', [$last30DaysStart, $last30DaysEnd]);

        if (!empty($category_ids)) {
            $productsSold->whereIn('p.category_id', $category_ids);
        }

        if (!empty($brand_ids)) {
            $productsSold->whereIn('p.brand_id', $brand_ids);
        }

        $productsSold = $productsSold->select(
                            'p.id as product_id',
                            'p.name as product_name',
                            'p.sku',
                            'b.name as brand_name',
                            'cat.name as category_name',
                            'v.id as variation_id',
                            DB::raw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as total_qty_sold')
                        )
                        ->groupBy('v.id', 'p.name', 'p.sku', 'b.name', 'cat.name')
                        ->havingRaw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) > 0')
                        ->orderBy('cat.name')
                        ->orderBy('p.name')
                        ->get();

        $requisitionDetails = [];

        foreach ($productsSold as $product) {
            $currentStock = VariationLocationDetails::where('variation_id', $product->variation_id)
                            ->where('location_id', $location_id)
                            ->sum('qty_available');

            $totalUnitsSoldLast30Days = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
                                    ->where('t.business_id', $business_id)
                                    ->where('t.location_id', $location_id)
                                    ->where('t.type', 'sell')
                                    ->where('t.status', 'final')
                                    ->whereBetween('t.transaction_date', [$last30DaysStart, $last30DaysEnd])
                                    ->where('transaction_sell_lines.variation_id', $product->variation_id)
                                    ->select(DB::raw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as total_qty_sold'))
                                    ->pluck('total_qty_sold')
                                    ->first();

            $daysInRange = $start_date->diffInDays($end_date) + 1;
            $suggestedOrder = ceil(($product->total_qty_sold / 30) * $daysInRange) - $currentStock;
            $suggestedOrder = max(6, ceil($suggestedOrder / 6) * 6);

            if ($currentStock < $suggestedOrder && $currentStock > 0) {
                $lastSupplier = PurchaseLine::join('transactions as t', 'purchase_lines.transaction_id', '=', 't.id')
                                ->join('contacts as c', 't.contact_id', '=', 'c.id')
                                ->where('purchase_lines.variation_id', $product->variation_id)
                                ->where('t.location_id', $location_id)
                                ->where('t.type', 'purchase')
                                ->orderBy('t.transaction_date', 'desc')
                                ->select('c.id as supplier_id', 'c.supplier_business_name as supplier_name')
                                ->first();

                $suggestedSupplierId = $lastSupplier ? $lastSupplier->supplier_id : null;
                $suggestedSupplierName = $lastSupplier ? $lastSupplier->supplier_name : 'No Supplier';

                $requisitionDetails[] = (object) [
                    'product_id' => $product->product_id,
                    'product_name' => $product->product_name,
                    'sku' => $product->sku,
                    'brand' => $product->brand_name,
                    'category' => $product->category_name,
                    'current_stock' => $currentStock,
                    'total_units_sold_last_30_days' => $totalUnitsSoldLast30Days,
                    'suggested_order' => $suggestedOrder,
                    'suggested_supplier_id' => $suggestedSupplierId,
                    'suggested_supplier_name' => $suggestedSupplierName,
                    'variation_id' => $product->variation_id // Include variation_id
                ];
            }
        }

        $location = BusinessLocation::findOrFail($location_id);
        $suppliers = Contact::where('business_id', $business_id)->where('type', 'supplier')->pluck('supplier_business_name', 'id')->toArray();

        $content = view('purchase_requisition.order_content', compact('requisitionDetails', 'suppliers', 'start_date', 'end_date', 'location'))->render();

        return response()->json(['content' => $content]);
    }





    public function getProductEntryRow()
    {
        $search_term = request()->input('term', '');
        $location_id = request()->input('location_id', null);
        $business_id = auth()->user()->business_id;

        $products = $this->productUtil->filterProduct($business_id, $search_term, $location_id, false, null, [], ['name', 'sku']);

        $requisitionDetails = [];
        foreach ($products as $product) {
            $currentStock = VariationLocationDetails::where('variation_id', $product->variation_id)
                                ->where('location_id', $location_id)
                                ->sum('qty_available');

            $totalUnitsSoldLast30Days = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
                                        ->where('t.business_id', $business_id)
                                        ->where('t.location_id', $location_id)
                                        ->where('t.type', 'sell')
                                        ->where('t.status', 'final')
                                        ->whereBetween('t.transaction_date', [now()->subDays(30)->startOfDay(), now()->endOfDay()])
                                        ->where('transaction_sell_lines.variation_id', $product->variation_id)
                                        ->sum(DB::raw('transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned'));

            $suggestedOrder = max(6, ceil($totalUnitsSoldLast30Days / 6) * 6);

            $lastSupplier = PurchaseLine::join('transactions as t', 'purchase_lines.transaction_id', '=', 't.id')
                                    ->join('contacts as c', 't.contact_id', '=', 'c.id')
                                    ->where('purchase_lines.variation_id', $product->variation_id)
                                    ->where('t.location_id', $location_id)
                                    ->where('t.type', 'purchase')
                                    ->orderBy('t.transaction_date', 'desc')
                                    ->select('c.id as supplier_id', 'c.supplier_business_name as supplier_name')
                                    ->first();

            $suggestedSupplierId = $lastSupplier ? $lastSupplier->supplier_id : null;
            $suggestedSupplierName = $lastSupplier ? $lastSupplier->supplier_name : 'No Supplier';

            $requisitionDetails[] = (object) [
                'product_id' => $product->product_id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'brand' => $product->brand ? $product->brand->name : 'No Brand',
                'category' => $product->category ? $product->category->name : 'No Category',
                'current_stock' => $currentStock,
                'total_units_sold_last_30_days' => $totalUnitsSoldLast30Days,
                'suggested_order' => $suggestedOrder,
                'suggested_supplier_id' => $suggestedSupplierId,
                'suggested_supplier_name' => $suggestedSupplierName,
                'variation_id' => $product->variation_id // Include variation_id
            ];
        }

        $suppliers = Contact::where('business_id', $business_id)->where('type', 'supplier')->pluck('supplier_business_name', 'id')->toArray();

        $content = view('purchase_requisition.product_entry_row', compact('requisitionDetails', 'suppliers'))->render();

        return response()->json(['content' => $content]);
    }


}