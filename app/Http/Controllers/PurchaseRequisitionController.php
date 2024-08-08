<?php

namespace App\Http\Controllers;

use App\Brands;
use App\BusinessLocation;
use App\Category;
use App\PurchaseLine;
use App\TaxRate;
use App\Transaction;
use App\TransactionSellLine;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\VariationLocationDetails;
use DB;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PurchaseRequisitionController extends Controller
{
    protected $commonUtil;

    protected $transactionUtil;

    /**
     * Constructor
     *
     * @param  Util  $commonUtil
     * @return void
     */
    public function __construct(Util $commonUtil, TransactionUtil $transactionUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->transactionUtil = $transactionUtil;

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
            $purchase_requisitions = Transaction::join(
                'business_locations AS BS',
                'transactions.location_id',
                '=',
                'BS.id'
            )
                ->join('users as u', 'transactions.created_by', '=', 'u.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'purchase_requisition')
                ->select(
                    'transactions.id',
                    'transactions.delivery_date',
                    'transactions.ref_no',
                    'transactions.status',
                    'BS.name as location_name',
                    'transactions.transaction_date',
                    DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by")
                )
                ->groupBy('transactions.id');

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $purchase_requisitions->whereIn('transactions.location_id', $permitted_locations);
            }

            if (!empty(request()->location_id)) {
                $purchase_requisitions->where('transactions.location_id', request()->location_id);
            }

            if (!empty(request()->status)) {
                $purchase_requisitions->where('transactions.status', request()->status);
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end = request()->end_date;
                $purchase_requisitions->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end);
            }

            if (!empty(request()->required_by_start) && !empty(request()->required_by_end)) {
                $start = request()->required_by_start;
                $end = request()->required_by_end;
                $purchase_requisitions->whereDate('transactions.delivery_date', '>=', $start)
                    ->whereDate('transactions.delivery_date', '<=', $end);
            }

            if (!auth()->user()->can('purchase_requisition.view_all') && auth()->user()->can('purchase_requisition.view_own')) {
                $purchase_requisitions->where('transactions.created_by', request()->session()->get('user.id'));
            }

            if (!empty(request()->from_dashboard)) {
                $purchase_requisitions->where('transactions.status', '!=', 'completed');
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
                ->editColumn('delivery_date', '@if(!empty($delivery_date)){{@format_datetime($delivery_date)}}@endif')
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn('status', function ($row) {
                    $status = '';
                    $order_statuses = $this->purchaseRequisitionStatuses;
                    if (array_key_exists($row->status, $order_statuses)) {
                        $status = '<span class="label ' . $order_statuses[$row->status]['class']
                            . '" >' . $order_statuses[$row->status]['label'] . '</span>';
                    }

                    return $status;
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        return  action([\App\Http\Controllers\PurchaseRequisitionController::class, 'show'], [$row->id]);
                    },
                ])
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        $purchaseRequisitionStatuses = [];
        foreach ($this->purchaseRequisitionStatuses as $key => $value) {
            $purchaseRequisitionStatuses[$key] = $value['label'];
        }

        return view('purchase_requisition.index')->with(compact('business_locations', 'purchaseRequisitionStatuses'));
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
    public function store(Request $request)
    {
        if (!auth()->user()->can('purchase_requisition.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $transaction_data = [
                'business_id' => $business_id,
                'location_id' => $request->input('location_id'),
                'type' => 'purchase_requisition',
                'status' => 'ordered',
                'created_by' => auth()->user()->id,
                'transaction_date' => \Carbon::now()->toDateTimeString(),
            ];

            $transaction_data['delivery_date'] = !empty($request->input('delivery_date')) ? $this->commonUtil->uf_date($request->input('delivery_date'), true) : null;

            $purchase_lines = [];
            foreach ($request->input('purchases') as $purchase_line) {
                $quantity = isset($purchase_line['quantity']) ? $this->commonUtil->num_uf($purchase_line['quantity']) : 0;
                $secondary_unit_quantity = isset($purchase_line['secondary_unit_quantity']) ? $this->commonUtil->num_uf($purchase_line['secondary_unit_quantity']) : 0;

                if (!empty($quantity) || !empty($secondary_unit_quantity)) {
                    $purchase_lines[] = [
                        'variation_id' => $purchase_line['variation_id'],
                        'product_id' => $purchase_line['product_id'],
                        'quantity' => $quantity,
                        'purchase_price_inc_tax' => 0,
                        'item_tax' => 0,
                        'secondary_unit_quantity' => $secondary_unit_quantity,
                    ];
                }
            }

            DB::beginTransaction();

            //Update reference count
            $ref_count = $this->commonUtil->setAndGetReferenceCount($transaction_data['type']);
            //Generate reference number
            if (empty($transaction_data['ref_no'])) {
                $transaction_data['ref_no'] = $this->commonUtil->generateReferenceNumber($transaction_data['type'], $ref_count);
            }

            $purchase_requisition = Transaction::create($transaction_data);
            $purchase_requisition->purchase_lines()->createMany($purchase_lines);

            DB::commit();

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.added_success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->action([\App\Http\Controllers\PurchaseRequisitionController::class, 'index'])->with('status', $output);
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

        $query = Transaction::where('business_id', $business_id)
            ->where('type', 'purchase_requisition')
            ->where('id', $id)
            ->with(
                'purchase_lines',
                'purchase_lines.product',
                'purchase_lines.product.unit',
                'purchase_lines.product.second_unit',
                'purchase_lines.variations',
                'purchase_lines.variations.product_variation',
                'location',
                'sales_person'
            );

        $purchase = $query->firstOrFail();

        return view('purchase_requisition.show')
            ->with(compact('purchase'));
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
                $business_id = request()->session()->get('user.business_id');

                $transaction = Transaction::where('business_id', $business_id)
                    ->where('type', 'purchase_requisition')
                    ->with(['purchase_lines'])
                    ->find($id);

                //unset purchase_order_line_id if set
                PurchaseLine::whereIn('purchase_requisition_line_id', $transaction->purchase_lines->pluck('id'))
                    ->update(['purchase_requisition_line_id' => null]);

                $transaction->delete();

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
}
