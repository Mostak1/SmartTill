<?php

namespace App\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\CustomerGroup;
use App\Events\PurchaseCreatedOrModified;
use App\PurchaseLine;
use App\Transaction;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\BusinessUtil;
use Datatables;
use DB;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use App\Events\StockAdjustmentCreatedOrModified;
use App\TaxRate;
use Illuminate\Support\Facades\Log;

class StockAdjustmentController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $productUtil;

    protected $transactionUtil;

    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil, ProductUtil $productUtil, TransactionUtil $transactionUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->businessUtil = $businessUtil;
        $this->dummyPaymentLine = [
            'method' => 'cash',
            'amount' => 0,
            'note' => '',
            'card_transaction_number' => '',
            'card_number' => '',
            'card_type' => '',
            'card_holder_name' => '',
            'card_month' => '',
            'card_year' => '',
            'card_security' => '',
            'cheque_number' => '',
            'bank_account_number' => '',
            'is_return' => 0,
            'transaction_no' => '',
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function stockSurplusStore(Request $request)
    {
        if (!auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }
        $cat_desck = $request->input('cat_desck', null);
        try {
            $business_id = $request->session()->get('user.business_id');

            //Check if subscribed or not
            if (!$this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse(action([\App\Http\Controllers\PurchaseController::class, 'index']));
            }
            $business = Business::where('id', $business_id)->first();

            $common_settings = is_string($business->common_settings) ? json_decode($business->common_settings, true) : (array) $business->common_settings;
            $transaction_data = $request->only(['ref_no', 'status', 'contact_id', 'transaction_date', 'total_before_tax', 'location_id', 'discount_type', 'discount_amount', 'tax_id', 'tax_amount', 'shipping_details', 'shipping_charges', 'final_total', 'additional_notes', 'exchange_rate', 'pay_term_number', 'pay_term_type', 'purchase_order_ids', 'add_surplus','adjustment_sign']);

            $exchange_rate = $transaction_data['exchange_rate'];          
            $transaction_data['contact_id'] = $common_settings['contact_id'];
            $transaction_data['status'] = 'received';
                if($transaction_data['contact_id']==null){
                    $output = [
                        'success' => 0,
                        'msg' => 'Set Supplier For Surplus From Settings->Purchase',
                    ];
                    return redirect('purchases')->with('status', $output);
                };
              
                $request->validate([
                    'transaction_date' => 'required',
                    'total_before_tax' => 'required',
                    'location_id' => 'required',
                    'final_total' => 'required',
                    'cat_desck' => 'nullable',
                    'document' => 'file|max:' . (config('constants.document_size_limit') / 1000),
                ]);
           
            $user_id = $request->session()->get('user.id');
            $enable_product_editing = $request->session()->get('business.enable_editing_product_from_purchase');

            //Update business exchange rate.
            Business::update_business($business_id, ['p_exchange_rate' => ($transaction_data['exchange_rate'])]);

            $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);
            
            // If discount type is fixed them multiply by exchange rate, else don't
            if ($transaction_data['discount_type'] == 'fixed') {
                if ($request->has('cat_desck')) {
                    $transaction_data['discount_amount'] = ($this->productUtil->num_uf($transaction_data['discount_amount'], $currency_details) * $exchange_rate) * $cat_desck;
                } else {
                    $transaction_data['discount_amount'] = $this->productUtil->num_uf($transaction_data['discount_amount'], $currency_details) * $exchange_rate;
                }
            } elseif ($transaction_data['discount_type'] == 'percentage') {
                $transaction_data['discount_amount'] = $this->productUtil->num_uf($transaction_data['discount_amount'], $currency_details);
            } else {
                $transaction_data['discount_amount'] = 0;
            }

            $transaction_data['tax_amount'] = $this->productUtil->num_uf($transaction_data['tax_amount'], $currency_details) * $exchange_rate;
            $transaction_data['shipping_charges'] = $this->productUtil->num_uf($transaction_data['shipping_charges'], $currency_details) * $exchange_rate;
            if ($request->has('cat_desck')) {
                //unformat input values
                $transaction_data['total_before_tax'] = ($this->productUtil->num_uf($transaction_data['total_before_tax'], $currency_details) * $exchange_rate) * $cat_desck;
                $transaction_data['final_total'] = ($transaction_data['final_total'] * $exchange_rate) * $cat_desck;
            } else {
                //unformat input values
                $transaction_data['total_before_tax'] = $this->productUtil->num_uf($transaction_data['total_before_tax'], $currency_details) * $exchange_rate;
                $transaction_data['final_total'] = $this->productUtil->num_uf($transaction_data['final_total'], $currency_details) * $exchange_rate;
            }
            $transaction_data['business_id'] = $business_id;
            $transaction_data['created_by'] = $user_id;
            $transaction_data['type'] = 'purchase';
            $transaction_data['payment_status'] = 'due';
            $transaction_data['transaction_date'] = $this->productUtil->uf_date($transaction_data['transaction_date'], true);

            //upload document
            $transaction_data['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');

            $transaction_data['custom_field_1'] = $request->input('custom_field_1', null);
            $transaction_data['custom_field_2'] = $request->input('custom_field_2', null);
            $transaction_data['custom_field_3'] = $request->input('custom_field_3', null);
            $transaction_data['custom_field_4'] = $request->input('custom_field_4', null);

            $transaction_data['shipping_custom_field_1'] = $request->input('shipping_custom_field_1', null);
            $transaction_data['shipping_custom_field_2'] = $request->input('shipping_custom_field_2', null);
            $transaction_data['shipping_custom_field_3'] = $request->input('shipping_custom_field_3', null);
            $transaction_data['shipping_custom_field_4'] = $request->input('shipping_custom_field_4', null);
            $transaction_data['shipping_custom_field_5'] = $request->input('shipping_custom_field_5', null);

            if ($request->input('additional_expense_value_1') != '') {
                $transaction_data['additional_expense_key_1'] = $request->input('additional_expense_key_1');
                $transaction_data['additional_expense_value_1'] = $this->productUtil->num_uf($request->input('additional_expense_value_1'), $currency_details) * $exchange_rate;
            }

            if ($request->input('additional_expense_value_2') != '') {
                $transaction_data['additional_expense_key_2'] = $request->input('additional_expense_key_2');
                $transaction_data['additional_expense_value_2'] = $this->productUtil->num_uf($request->input('additional_expense_value_2'), $currency_details) * $exchange_rate;
            }

            if ($request->input('additional_expense_value_3') != '') {
                $transaction_data['additional_expense_key_3'] = $request->input('additional_expense_key_3');
                $transaction_data['additional_expense_value_3'] = $this->productUtil->num_uf($request->input('additional_expense_value_3'), $currency_details) * $exchange_rate;
            }

            if ($request->input('additional_expense_value_4') != '') {
                $transaction_data['additional_expense_key_4'] = $request->input('additional_expense_key_4');
                $transaction_data['additional_expense_value_4'] = $this->productUtil->num_uf($request->input('additional_expense_value_4'), $currency_details) * $exchange_rate;
            }

            DB::beginTransaction();

            //Update reference count
            $ref_count = $this->productUtil->setAndGetReferenceCount($transaction_data['type']);
            //Generate reference number
            if (empty($transaction_data['ref_no'])) {
                $transaction_data['ref_no'] = $this->productUtil->generateReferenceNumber($transaction_data['type'], $ref_count);
            }

            $transaction = Transaction::create($transaction_data);

            $purchase_lines = [];
            $purchases = $request->input('purchases');

            $this->productUtil->createOrUpdatePurchaseLines($transaction, $purchases, $currency_details, $enable_product_editing);
            $uf_data = true;
            //Add Purchase payments
            $this->transactionUtil->createOrUpdatePaymentLines($transaction, $request->input('payment'), $business_id, $user_id, $uf_data, $cat_desck);
            //update payment status
            $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

            if (!empty($transaction->purchase_order_ids)) {
                $this->transactionUtil->updatePurchaseOrderStatus($transaction->purchase_order_ids);
            }

            //Adjust stock over selling if found
            $this->productUtil->adjustStockOverSelling($transaction);

            $this->transactionUtil->activityLog($transaction, 'added');

            PurchaseCreatedOrModified::dispatch($transaction);

            DB::commit();

            $output = [
                'success' => 1,
                'msg' => __('purchase.purchase_add_success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect('purchases')->with('status', $output);
    }
    public function stockSurplus()
    {
        if (!auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        }


        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        }

        $taxes = TaxRate::where('business_id', $business_id)
            ->ExcludeForTaxGroup()
            ->get();
        $orderStatuses = $this->productUtil->orderStatuses();
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

        $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);

        $default_purchase_status = null;
        if (request()->session()->get('business.enable_purchase_status') != 1) {
            $default_purchase_status = 'received';
        }

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }
        if (auth()->user()->can('supplier.create') && auth()->user()->can('customer.create')) {
            $types['both'] = __('lang_v1.both_supplier_customer');
        }
        $customer_groups = CustomerGroup::forDropdown($business_id);

        $business_details = $this->businessUtil->getDetails($business_id);
        $shortcuts = json_decode($business_details->keyboard_shortcuts, true);

        $payment_line = $this->dummyPaymentLine;
        $payment_types = $this->productUtil->payment_types(null, true, $business_id);

        //Accounts
        $accounts = $this->moduleUtil->accountsDropdown($business_id, true);

        $common_settings = !empty(session('business.common_settings')) ? session('business.common_settings') : [];


        return view('stock_adjustment.surplus')
            ->with(compact('taxes', 'orderStatuses', 'business_locations', 'currency_details', 'default_purchase_status', 'customer_groups', 'types', 'shortcuts', 'payment_line', 'payment_types', 'accounts', 'bl_attributes', 'common_settings'));
    }
    public function index()
    {
        if (!auth()->user()->can('purchase.view') && !auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $stock_adjustments = Transaction::join(
                'business_locations AS BL',
                'transactions.location_id',
                '=',
                'BL.id'
            )
                ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'stock_adjustment')
                ->select(
                    'transactions.id',
                    'transaction_date',
                    'ref_no',
                    'BL.name as location_name',
                    'adjustment_type',
                    'final_total',
                    'adjustment_sign',
                    'total_amount_recovered',
                    'additional_notes',
                    'transactions.id as DT_RowId',
                    DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by")
                );

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $stock_adjustments->whereIn('transactions.location_id', $permitted_locations);
            }

            $hide = '';
            $start_date = request()->get('start_date');
            $end_date = request()->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $stock_adjustments->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
                $hide = 'hide';
            }
            $location_id = request()->get('location_id');
            if (!empty($location_id)) {
                $stock_adjustments->where('transactions.location_id', $location_id);
            }

            return Datatables::of($stock_adjustments)
                ->addColumn('action', '<button type="button" data-href="{{action([\App\Http\Controllers\StockAdjustmentController::class, \'show\'], [$id]) }}" class="btn btn-primary btn-xs btn-modal" data-container=".view_modal"><i class="fa fa-eye" aria-hidden="true"></i> @lang("messages.view")</button>
                 &nbsp;
                    <button type="button" data-href="{{  action([\App\Http\Controllers\StockAdjustmentController::class, \'destroy\'], [$id]) }}" class="btn btn-danger btn-xs delete_stock_adjustment ' . $hide . '"><i class="fa fa-trash" aria-hidden="true"></i> @lang("messages.delete")</button>')
                ->removeColumn('id')
                ->editColumn(
                    'final_total',
                    function ($row) {
                        return $this->transactionUtil->num_f($row->final_total, true);
                    }
                )
                ->editColumn(
                    'adjustment_sign',
                    function ($row) {
                        if ($row->adjustment_sign == 'Plus') {
                            return 'Surplus';
                        } elseif ($row->adjustment_sign == 'Minus') {
                            return 'Damage';
                        } else {
                            return $row->adjustment_sign;
                        }
                    }
                )
                ->editColumn(
                    'total_amount_recovered',
                    function ($row) {
                        return $this->transactionUtil->num_f($row->total_amount_recovered, true);
                    }
                )
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn('adjustment_type', function ($row) {
                    return __('stock_adjustment.' . $row->adjustment_type);
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        return  action([\App\Http\Controllers\StockAdjustmentController::class, 'show'], [$row->id]);
                    },
                ])
                ->rawColumns(['final_total', 'adjustment_sign', 'action', 'total_amount_recovered'])
                ->make(true);
        }

        return view('stock_adjustment.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action([\App\Http\Controllers\StockAdjustmentController::class, 'index']));
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('stock_adjustment.create')
            ->with(compact('business_locations'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $input_data = $request->only(['location_id', 'transaction_date', 'adjustment_type', 'adjustment_sign', 'additional_notes', 'total_amount_recovered', 'final_total', 'ref_no']);
            $business_id = $request->session()->get('user.business_id');

            //Check if subscribed or not
            if (!$this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse(action([\App\Http\Controllers\StockAdjustmentController::class, 'index']));
            }

            $user_id = $request->session()->get('user.id');

            $input_data['type'] = 'stock_adjustment';
            if ($request->adjustment_sign == 'Plus') {
                $input_data['status'] = 'received';
            }
            $input_data['business_id'] = $business_id;
            $input_data['created_by'] = $user_id;
            $input_data['transaction_date'] = $this->productUtil->uf_date($input_data['transaction_date'], true);
            // dd($input_data['transaction_date']);
            $input_data['total_amount_recovered'] = $this->productUtil->num_uf($input_data['total_amount_recovered']);

            //Update reference count
            $ref_count = $this->productUtil->setAndGetReferenceCount('stock_adjustment');
            //Generate reference number
            if (empty($input_data['ref_no'])) {
                $input_data['ref_no'] = $this->productUtil->generateReferenceNumber('stock_adjustment', $ref_count);
            }

            $products = $request->input('products');

            if (!empty($products)) {
                $product_data = [];

                foreach ($products as $product) {
                    //Decrease available quantity increaseProductQuantity
                    if ($request->adjustment_sign == 'Minus') {
                        $adjustment_line = [
                            'product_id' => $product['product_id'],
                            'variation_id' => $product['variation_id'],
                            'quantity' => $this->productUtil->num_uf($product['quantity']),
                            'unit_price' => $this->productUtil->num_uf($product['unit_price']),
                            'sign' => $request->adjustment_sign,
                        ];
                        if (!empty($product['lot_no_line_id'])) {
                            //Add lot_no_line_id to stock adjustment line
                            $adjustment_line['lot_no_line_id'] = $product['lot_no_line_id'];
                        }
                        $product_data[] = $adjustment_line;
                        $this->productUtil->decreaseProductQuantity(
                            $product['product_id'],
                            $product['variation_id'],
                            $input_data['location_id'],
                            $this->productUtil->num_uf($product['quantity'])
                        );
                    } elseif ($request->adjustment_sign == 'Plus') {
                        $adjustment_line = [
                            'product_id' => $product['product_id'],
                            'variation_id' => $product['variation_id'],
                            'quantity' => $this->productUtil->num_uf(-$product['quantity']),
                            'unit_price' => $this->productUtil->num_uf($product['unit_price']),
                            'sign' => $request->adjustment_sign,
                        ];
                        if (!empty($product['lot_no_line_id'])) {
                            //Add lot_no_line_id to stock adjustment line
                            $adjustment_line['lot_no_line_id'] = $product['lot_no_line_id'];
                        }
                        $product_data[] = $adjustment_line;
                        $this->productUtil->increaseProductQuantity(
                            $product['product_id'],
                            $product['variation_id'],
                            $input_data['location_id'],
                            $this->productUtil->num_uf($product['quantity'])
                        );
                    }
                }

                $stock_adjustment = Transaction::create($input_data);
                $stock_adjustment->stock_adjustment_lines()->createMany($product_data);

                //Map Stock adjustment & Purchase.
                $business = [
                    'id' => $business_id,
                    'accounting_method' => $request->session()->get('business.accounting_method'),
                    'location_id' => $input_data['location_id'],
                    'adjustment_sign' => $request->adjustment_sign,
                ];
                // dd($stock_adjustment->stock_adjustment_lines);
                if ($request->adjustment_sign == 'Plus') {
                    $this->transactionUtil->mapPurchaseSellPlus($business, $stock_adjustment->stock_adjustment_lines, 'stock_adjustment');
                } elseif ($request->adjustment_sign == 'Minus') {
                    $this->transactionUtil->mapPurchaseSell($business, $stock_adjustment->stock_adjustment_lines, 'stock_adjustment');
                }

                event(new StockAdjustmentCreatedOrModified($stock_adjustment, 'added'));

                $this->transactionUtil->activityLog($stock_adjustment, 'added', null, [], false);
            }

            $output = [
                'success' => 1,
                'msg' => __('stock_adjustment.stock_adjustment_added_successfully'),
            ];
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $msg = trans('messages.something_went_wrong');

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = 'Products must have an opening stock before adding surplus. Please add an opening stock first to proceed.';
            }

            $output = [
                'success' => 0,
                'msg' => $msg,
            ];
        }

        return redirect('stock-adjustments')->with('status', $output);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!auth()->user()->can('purchase.view')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        $stock_adjustment = Transaction::where('transactions.business_id', $business_id)
            ->where('transactions.id', $id)
            ->where('transactions.type', 'stock_adjustment')
            ->with(['stock_adjustment_lines', 'location', 'business', 'stock_adjustment_lines.variation', 'stock_adjustment_lines.variation.product', 'stock_adjustment_lines.variation.product_variation', 'stock_adjustment_lines.lot_details'])
            ->first();

        $lot_n_exp_enabled = false;
        if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
            $lot_n_exp_enabled = true;
        }

        $activities = Activity::forSubject($stock_adjustment)
            ->with(['causer', 'subject'])
            ->latest()
            ->get();

        return view('stock_adjustment.show')
            ->with(compact('stock_adjustment', 'lot_n_exp_enabled', 'activities'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Transaction  $stockAdjustment
     * @return \Illuminate\Http\Response
     */
    public function edit(Transaction $stockAdjustment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Transaction  $stockAdjustment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Transaction $stockAdjustment)
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
        if (!auth()->user()->can('purchase.delete')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            if (request()->ajax()) {
                DB::beginTransaction();

                $stock_adjustment = Transaction::where('id', $id)
                    ->where('type', 'stock_adjustment')
                    ->with(['stock_adjustment_lines'])
                    ->first();
                $sign = $stock_adjustment->adjustment_sign;

                //Add deleted product quantity to available quantity
                $stock_adjustment_lines = $stock_adjustment->stock_adjustment_lines;
                if (!empty($stock_adjustment_lines)) {
                    $line_ids = [];
                    foreach ($stock_adjustment_lines as $stock_adjustment_line) {
                        $this->productUtil->updateProductQuantity(
                            $stock_adjustment->location_id,
                            $stock_adjustment_line->product_id,
                            $stock_adjustment_line->variation_id,
                            $this->productUtil->num_f($stock_adjustment_line->quantity)
                        );
                        $line_ids[] = $stock_adjustment_line->id;
                    }

                    $this->transactionUtil->mapPurchaseQuantityForDeleteStockAdjustment($line_ids, $sign);
                }
                $stock_adjustment->delete();

                event(new StockAdjustmentCreatedOrModified($stock_adjustment, 'deleted'));


                //Remove Mapping between stock adjustment & purchase.

                $output = [
                    'success' => 1,
                    'msg' => __('stock_adjustment.delete_success'),
                ];

                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Return product rows
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getProductRow(Request $request)
    {
        if ($request->ajax()) {
            try {
                $row_index = $request->input('row_index');
                $variation_id = $request->input('variation_id');
                $location_id = $request->input('location_id');

                $business_id = $request->session()->get('user.business_id');
                $product = $this->productUtil->getDetailsFromVariation($variation_id, $business_id, $location_id, $check_qty = false);
                $product->formatted_qty_available = $this->productUtil->num_f($product->qty_available);
                $product->sign = $request->input('sign');
                $type = !empty($request->input('type')) ? $request->input('type') : 'stock_adjustment';

                // Get lot number dropdown if enabled 
                $lot_numbers = [];
                if ($request->session()->get('business.enable_lot_number') == 1 || $request->session()->get('business.enable_product_expiry') == 1) {
                    $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($variation_id, $business_id, $location_id, true);
                    foreach ($lot_number_obj as $lot_number) {
                        $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                        $lot_numbers[] = $lot_number;
                    }
                }
                $product->lot_numbers = $lot_numbers;
                $sub_units = $this->productUtil->getSubUnits($business_id, $product->unit_id, false, $product->id);
                if ($type == 'stock_transfer') {
                    return view('stock_transfer.partials.product_table_row')
                        ->with(compact('product', 'row_index', 'sub_units'));
                } else {
                    return view('stock_adjustment.partials.product_table_row')
                        ->with(compact('product', 'row_index', 'sub_units'));
                }
            } catch (\Exception $e) {
                // Log the error
                \Log::error('Error in getProductRow: ' . $e->getMessage(), [
                    'exception' => $e,
                    'row_index' => $request->input('row_index'),
                    'variation_id' => $request->input('variation_id'),
                    'location_id' => $request->input('location_id'),
                    'business_id' => $request->session()->get('user.business_id'),
                ]);

                // Return error response
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while processing your request.',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }
    }


    /**
     * Sets expired purchase line as stock adjustmnet
     *
     * @param  int  $purchase_line_id
     * @return json $output
     */
    public function removeExpiredStock($purchase_line_id)
    {
        if (!auth()->user()->can('purchase.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $purchase_line = PurchaseLine::where('id', $purchase_line_id)
                ->with(['transaction'])
                ->first();

            if (!empty($purchase_line)) {
                DB::beginTransaction();

                $qty_unsold = $purchase_line->quantity - $purchase_line->quantity_sold - $purchase_line->quantity_adjusted - $purchase_line->quantity_returned;
                $final_total = $purchase_line->purchase_price_inc_tax * $qty_unsold;

                $user_id = request()->session()->get('user.id');
                $business_id = request()->session()->get('user.business_id');

                //Update reference count
                $ref_count = $this->productUtil->setAndGetReferenceCount('stock_adjustment');

                $stock_adjstmt_data = [
                    'type' => 'stock_adjustment',
                    'business_id' => $business_id,
                    'created_by' => $user_id,
                    'transaction_date' => \Carbon::now()->format('Y-m-d'),
                    'total_amount_recovered' => 0,
                    'location_id' => $purchase_line->transaction->location_id,
                    'adjustment_type' => 'normal',
                    'final_total' => $final_total,
                    'ref_no' => $this->productUtil->generateReferenceNumber('stock_adjustment', $ref_count),
                ];

                //Create stock adjustment transaction
                $stock_adjustment = Transaction::create($stock_adjstmt_data);

                $stock_adjustment_line = [
                    'product_id' => $purchase_line->product_id,
                    'variation_id' => $purchase_line->variation_id,
                    'quantity' => $qty_unsold,
                    'unit_price' => $purchase_line->purchase_price_inc_tax,
                    'removed_purchase_line' => $purchase_line->id,
                ];

                //Create stock adjustment line with the purchase line
                $stock_adjustment->stock_adjustment_lines()->create($stock_adjustment_line);

                //Decrease available quantity
                $this->productUtil->decreaseProductQuantity(
                    $purchase_line->product_id,
                    $purchase_line->variation_id,
                    $purchase_line->transaction->location_id,
                    $qty_unsold
                );

                //Map Stock adjustment & Purchase.
                $business = [
                    'id' => $business_id,
                    'accounting_method' => request()->session()->get('business.accounting_method'),
                    'location_id' => $purchase_line->transaction->location_id,
                ];
                $this->transactionUtil->mapPurchaseSell($business, $stock_adjustment->stock_adjustment_lines, 'stock_adjustment', false, $purchase_line->id);

                DB::commit();

                $output = [
                    'success' => 1,
                    'msg' => __('lang_v1.stock_removed_successfully'),
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $msg = trans('messages.something_went_wrong');

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }

            $output = [
                'success' => 0,
                'msg' => $msg,
            ];
        }

        return $output;
    }
}
