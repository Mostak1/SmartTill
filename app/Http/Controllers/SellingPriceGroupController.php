<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Product;
use App\SellingPriceGroup;
use App\Utils\Util;
use App\Utils\ProductUtil;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use App\VariationGroupPrice;
use DB;
use Excel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class SellingPriceGroupController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $commonUtil;
    protected $productUtil;
    protected $transactionUtil;
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil, ModuleUtil $moduleUtil, Util $commonUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->commonUtil = $commonUtil;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $price_groups = SellingPriceGroup::where('business_id', $business_id)
                ->select(['name', 'description', 'id', 'is_active']);

            return Datatables::of($price_groups)
                ->addColumn(
                    'action',
                    '<a href="{{action(\'App\Http\Controllers\SellingPriceGroupController@show\', [$id])}}" class="btn btn-xs btn-info" > @lang("messages.view") Products</a>
                    &nbsp;
                    <button data-href="{{action(\'App\Http\Controllers\SellingPriceGroupController@edit\', [$id])}}" class="btn btn-xs btn-primary btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                        &nbsp;
                        <button data-href="{{action(\'App\Http\Controllers\SellingPriceGroupController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_spg_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                        &nbsp;
                        <button data-href="{{action(\'App\Http\Controllers\SellingPriceGroupController@activateDeactivate\', [$id])}}" class="btn btn-xs @if($is_active) btn-danger @else btn-success @endif activate_deactivate_spg"><i class="fas fa-power-off"></i> @if($is_active) @lang("messages.deactivate") @else @lang("messages.activate") @endif</button>'
                )
                ->removeColumn('is_active')
                ->removeColumn('id')
                ->rawColumns([2])
                ->make(false);
        }

        return view('selling_price_group.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        $price_groups = SellingPriceGroup::where('business_id', $business_id)->pluck('name', 'id');
        return view('selling_price_group.create', compact('price_groups'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['name', 'description']);
            // dd($request->copy_group_id);
            $business_id = $request->session()->get('user.business_id');
            $input['business_id'] = $business_id;

            $spg = SellingPriceGroup::create($input);

            //Create a new permission related to the created selling price group
            Permission::create(['name' => 'selling_price_group.' . $spg->id]);
            // Retrieve the variation group prices from the provided copy_group_id
            $variation_group_prices = VariationGroupPrice::where('price_group_id', $request->copy_group_id)->get();
            // Iterate over the retrieved variation group prices and create new entries for the new selling price group
            foreach ($variation_group_prices as $variation_group_price) {
                VariationGroupPrice::create([
                    'price_group_id' => $spg->id,
                    'variation_id' => $variation_group_price->variation_id,
                    'price_inc_tax' => $variation_group_price->price_inc_tax,
                    'price_type' => $variation_group_price->price_type,
                ]);
            }
            $output = [
                'success' => true,
                'data' => $spg,
                'msg' => __('lang_v1.added_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\SellingPriceGroup  $sellingPriceGroup
     * @return \Illuminate\Http\Response
     */
    public function show(SellingPriceGroup $sellingPriceGroup)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }
        $id = $sellingPriceGroup->id;
        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id);
        $price_groups = SellingPriceGroup::where('business_id', $business_id)
            ->active()
            ->get();
        if (request()->ajax()) {
            $variationProducts = VariationGroupPrice::with(['variation.product'])
                ->where('price_group_id', $id)
                ->select('variation_id', 'price_group_id', 'price_inc_tax', 'price_type');

            return Datatables::of($variationProducts)
                ->addColumn('product', function ($row) {
                    return $row->variation->product->name . ' (' . $row->variation->name . ')';
                })
                ->addColumn('sku', function ($row) {
                    return $row->variation->sub_sku;
                })
                ->addColumn('selling_price', function ($row) {
                    return number_format($row->variation->sell_price_inc_tax, 0);
                })
                ->addColumn('price_group', function ($row) {
                    if ($row->price_type == 'percentage') {
                        $price = 100 - $row->price_inc_tax . ' %';
                        $html = $price . '%';
                    } elseif ($row->price_type == 'fixed') {
                        $price = number_format($row->price_inc_tax, 0) . ' (Fixed)';
                        $html = $price . 'Fixed';
                    }
                    return $price;
                })
                ->addColumn('price_group_price', function ($row) {

                    if ($row->price_type == 'percentage') {
                        $price = ($row->variation->sell_price_inc_tax * $row->price_inc_tax) / 100;
                        $html = $price . '%';
                    } elseif ($row->price_type == 'fixed') {
                        $price = ($row->variation->sell_price_inc_tax - $row->price_inc_tax);
                        $html = $price . 'Fixed';
                    }
                    return $price;
                })
                ->addColumn('profit_per', function ($row) {

                    if ($row->price_type == 'percentage') {
                        $price = ($row->variation->sell_price_inc_tax * $row->price_inc_tax) / 100;
                        $profit = ((($row->variation->sell_price_inc_tax - $price) * 100) / $price);
                    } elseif ($row->price_type == 'fixed') {
                        $price = ($row->variation->sell_price_inc_tax - $row->price_inc_tax);
                        $profit = ((($row->variation->sell_price_inc_tax - $price) * 100) / $price);
                    }
                    return number_format($profit, 2) . ' %';
                })
                ->addColumn('Remove', function ($row) {
                    return '<a href="' . route('selling-price-group.remove-item', [$row->price_group_id, $row->variation_id]) . '" class="remove_group_item text-danger" title="Remove"><i class="fa fa-times" style="cursor:pointer;"></i></a>';
                })
                ->rawColumns(['product', 'profit_per', 'sku', 'selling_price', 'price_group', 'price_group_price', 'Remove'])
                ->make(true);
        }
        return view('selling_price_group.show')
            ->with(compact('sellingPriceGroup', 'business_locations', 'price_groups'));
    }
    public function removeItem($id, $item_id)
    {
        if (!auth()->user()->can('product.delete')) {
            return response()->json(['success' => false, 'msg' => 'Unauthorized action.'], 403);
        }

        try {
            // Find the item and delete it
            $item = VariationGroupPrice::where('price_group_id', $id)->where('variation_id', $item_id)->first();

            if ($item) {
                $item->delete();
                return response()->json(['success' => true, 'msg' => 'Product removed successfully.']);
            } else {
                return response()->json(['success' => false, 'msg' => 'Product not found.']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'msg' => 'Something went wrong. Please try again.']);
        }
    }
    public function updatePriceGroup($id)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }
        $sellingPriceGroup = SellingPriceGroup::findOrFail($id);
        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id);
        $price_groups = SellingPriceGroup::where('business_id', $business_id)
            ->active()
            ->get();
        $variationProducts = VariationGroupPrice::with(['variation.product'])
            ->where('price_group_id', $id)
            ->select('variation_id', 'price_group_id', 'price_inc_tax', 'price_type');
        $products = $variationProducts->get();
        if (request()->ajax()) {


            return Datatables::of($variationProducts)
                ->addColumn('product', function ($row) {
                    return $row->variation->product->name . ' (' . $row->variation->name . ')';
                })
                ->addColumn('sku', function ($row) {
                    return $row->variation->sub_sku;
                })
                ->addColumn('selling_price', function ($row) {
                    return number_format($row->variation->sell_price_inc_tax, 0);
                })
                ->addColumn('price_group', function ($row) {
                    if ($row->price_type == 'percentage') {
                        $price = 100 - $row->price_inc_tax . ' %';
                        $html = $price . '%';
                    } elseif ($row->price_type == 'fixed') {
                        $price = number_format($row->price_inc_tax, 0) . ' (Fixed)';
                        $html = $price . 'Fixed';
                    }
                    return $price;
                })
                ->addColumn('price_group_price', function ($row) {

                    if ($row->price_type == 'percentage') {
                        $price = ($row->variation->sell_price_inc_tax * $row->price_inc_tax) / 100;
                        $html = $price . '%';
                    } elseif ($row->price_type == 'fixed') {
                        $price = ($row->variation->sell_price_inc_tax - $row->price_inc_tax);
                        $html = $price . 'Fixed';
                    }
                    return $price;
                })
                ->addColumn('profit_per', function ($row) {

                    if ($row->price_type == 'percentage') {
                        $price = ($row->variation->sell_price_inc_tax * $row->price_inc_tax) / 100;
                        $profit = ((($row->variation->sell_price_inc_tax - $price) * 100) / $price);
                    } elseif ($row->price_type == 'fixed') {
                        $price = ($row->variation->sell_price_inc_tax - $row->price_inc_tax);
                        $profit = ((($row->variation->sell_price_inc_tax - $price) * 100) / $price);
                    }
                    return number_format($profit, 2) . ' %';
                })
                ->rawColumns(['product', 'profit_per', 'sku', 'selling_price', 'price_group', 'price_group_price'])
                ->make(true);
        }
        return view('selling_price_group.update_price_group')->with(compact('sellingPriceGroup', 'business_locations', 'price_groups', 'products'));
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\SellingPriceGroup  $sellingPriceGroup
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $spg = SellingPriceGroup::where('business_id', $business_id)->find($id);

            return view('selling_price_group.edit')
                ->with(compact('spg'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\SellingPriceGroup  $sellingPriceGroup
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $input = $request->only(['name', 'description']);
                $business_id = $request->session()->get('user.business_id');

                $spg = SellingPriceGroup::where('business_id', $business_id)->findOrFail($id);
                $spg->name = $input['name'];
                $spg->description = $input['description'];
                $spg->save();

                $output = [
                    'success' => true,
                    'msg' => __('lang_v1.updated_success'),
                ];
            } catch (\Exception $e) {
                \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\SellingPriceGroup  $sellingPriceGroup
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->user()->business_id;

                $spg = SellingPriceGroup::where('business_id', $business_id)->findOrFail($id);
                $spg->delete();

                $output = [
                    'success' => true,
                    'msg' => __('lang_v1.deleted_success'),
                ];
            } catch (\Exception $e) {
                \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    /**
     * Show interface to download product price excel file.
     *
     * @return \Illuminate\Http\Response
     */
    public function updateProductPrice()
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        return view('selling_price_group.update_product_price');
    }


    /**
     * Exports selling price group prices for all the products in xls format
     *
     * @return \Illuminate\Http\Response
     */
    public function export()
    {
        $business_id = request()->user()->business_id;
        $price_groups = SellingPriceGroup::where('business_id', $business_id)->active()->get();

        $variations = Variation::join('products as p', 'variations.product_id', '=', 'p.id')
            ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
            ->where('p.business_id', $business_id)
            ->whereIn('p.type', ['single', 'variable'])
            ->select('sub_sku', 'p.name as product_name', 'variations.name as variation_name', 'p.type', 'variations.id', 'pv.name as product_variation_name', 'sell_price_inc_tax')
            ->with(['group_prices'])
            ->get();
        $export_data = [];
        foreach ($variations as $variation) {
            $temp = [];
            $temp['product'] = $variation->type == 'single' ? $variation->product_name : $variation->product_name . ' - ' . $variation->product_variation_name . ' - ' . $variation->variation_name;
            $temp['sku'] = $variation->sub_sku;
            $temp['Selling Price Including Tax'] = $variation->sell_price_inc_tax;

            foreach ($price_groups as $price_group) {
                $price_group_id = $price_group->id;
                $variation_pg = $variation->group_prices->filter(function ($item) use ($price_group_id) {
                    return $item->price_group_id == $price_group_id;
                });

                $temp[$price_group->name] = $variation_pg->isNotEmpty() ? $variation_pg->first()->price_inc_tax : '';
            }
            $export_data[] = $temp;
        }

        if (ob_get_contents()) {
            ob_end_clean();
        }
        ob_start();

        return collect($export_data)->downloadExcel(
            'product_prices.xlsx',
            null,
            true
        );
    }

    /**
     * Imports the uploaded file to database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function import(Request $request)
    {
        try {
            $notAllowed = $this->commonUtil->notAllowedInDemo();
            if (!empty($notAllowed)) {
                return $notAllowed;
            }

            //Set maximum php execution time
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);

            if ($request->hasFile('product_group_prices')) {
                $file = $request->file('product_group_prices');

                $parsed_array = Excel::toArray([], $file);

                $headers = $parsed_array[0][0];

                //Remove header row
                $imported_data = array_splice($parsed_array[0], 1);

                $business_id = request()->user()->business_id;
                $price_groups = SellingPriceGroup::where('business_id', $business_id)->active()->get();

                //Get price group names from headers
                $imported_pgs = [];
                foreach ($headers as $key => $value) {
                    if (!empty($value) && $key > 2) {
                        $imported_pgs[$key] = $value;
                    }
                }
                $error_msg = '';
                DB::beginTransaction();
                foreach ($imported_data as $key => $value) {
                    $variation = Variation::where('sub_sku', $value[1])
                        ->first();
                    if (empty($variation)) {
                        $row = $key + 1;
                        $error_msg = __('lang_v1.product_not_found_exception', ['sku' => $value[1], 'row' => $row]);

                        throw new \Exception($error_msg);
                    }
                    //Check if product base price is changed
                    if ($variation->sell_price_inc_tax != $value[2]) {
                        //update price for base selling price, adjust default_sell_price, profit %
                        $variation->sell_price_inc_tax = $value[2];
                        $tax = $variation->product->product_tax()->get();
                        $tax_percent = !empty($tax) && !empty($tax->first()) ? $tax->first()->amount : 0;
                        $variation->default_sell_price = $this->commonUtil->calc_percentage_base($value[2], $tax_percent);
                        $variation->profit_percent = $this->commonUtil
                            ->get_percent($variation->default_purchase_price, $variation->default_sell_price);
                        $variation->update();
                    }

                    //update selling price
                    foreach ($imported_pgs as $k => $v) {
                        $price_group = $price_groups->filter(function ($item) use ($v) {
                            return strtolower($item->name) == strtolower($v);
                        });

                        if ($price_group->isNotEmpty()) {
                            //Check if price is numeric
                            if (!is_null($value[$k]) && !is_numeric($value[$k])) {
                                $row = $key + 1;
                                $error_msg = __('lang_v1.price_group_non_numeric_exception', ['row' => $row]);

                                throw new \Exception($error_msg);
                            }

                            if (!is_null($value[$k])) {
                                VariationGroupPrice::updateOrCreate(
                                    [
                                        'variation_id' => $variation->id,
                                        'price_group_id' => $price_group->first()->id,
                                    ],
                                    [
                                        'price_inc_tax' => $value[$k],
                                    ]
                                );
                            }
                        } else {
                            $row = $key + 1;
                            $error_msg = __('lang_v1.price_group_not_found_exception', ['pg' => $v, 'row' => $row]);

                            throw new \Exception($error_msg);
                        }
                    }
                }
                DB::commit();
            }
            $output = [
                'success' => 1,
                'msg' => __('lang_v1.product_prices_imported_successfully'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => $e->getMessage(),
            ];

            return redirect('update-product-price')->with('notification', $output);
        }

        return redirect('update-product-price')->with('status', $output);
    }

    /**
     * Activate/deactivate selling price group.
     */
    public function activateDeactivate($id)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $spg = SellingPriceGroup::where('business_id', $business_id)->find($id);
            $spg->is_active = $spg->is_active == 1 ? 0 : 1;
            $spg->save();

            $output = [
                'success' => true,
                'msg' => __('lang_v1.updated_success'),
            ];

            return $output;
        }
    }

    public function getProductRow(Request $request)
    {
        if ($request->ajax()) {
            try {
                $row_index = $request->input('row_index');
                $variation_id = $request->input('variation_id');
                $location_id = $request->input('location_id');
                $price_group_id = $request->input('price_group_id');
                Log::info('getProductRow called', [
                    'row_index' => $row_index,
                    'variation_id' => $variation_id,
                    'location_id' => $location_id
                ]);

                $business_id = $request->session()->get('user.business_id');
                Log::info('Business ID retrieved', ['business_id' => $business_id]);
                // $product1 = $this->productUtil->getDetailsFromVariation($variation_id, $business_id, $location_id);
                // if (!$product1) {
                //     throw new \Exception('No product details retrieved from variation');
                // }
                // Log::info('Product details retrieved from variation', ['product1' => $product1]);
                // // Ensure product1 contains a valid id
                // if (!isset($product1->product_id)) {
                //     throw new \Exception('Product ID not found in variation details');
                // }
                $variation = Variation::where('id', $variation_id)
                    ->whereHas('product', function ($query) use ($business_id) {
                        $query->where('business_id', $business_id);
                    })
                    ->with(['product', 'group_prices', 'product_variation'])
                    ->firstOrFail();
                Log::info('Variation retrieved with relations', ['variation' => $variation]);
                $product = $variation->product;
                Log::info('Product retrieved with relations', ['product' => $product]);
                $price_groups = SellingPriceGroup::where('business_id', $business_id)->where('id', $price_group_id)
                    ->active()
                    ->get();
                Log::info('Price groups retrieved', ['price_groups' => $price_groups]);
                $variation_prices = [];
                foreach ($product->variations as $variation) {
                    foreach ($variation->group_prices as $group_price) {
                        $variation_prices[$variation->id][$group_price->price_group_id] = [
                            'price' => $group_price->price_inc_tax,
                            'price_type' => $group_price->price_type
                        ];
                    }
                }
                Log::info('Variation prices calculated', ['variation_prices' => $variation_prices]);
                return view('selling_price_group.product_group_row')
                    ->with(compact('row_index', 'product', 'price_groups', 'variation_prices'));
            } catch (ModelNotFoundException $e) {
                Log::error('Model not found in getProductRow', [
                    'row_index' => $row_index,
                    'variation_id' => $variation_id,
                    'location_id' => $location_id,
                    'exception' => $e->getMessage()
                ]);
                return response()->json(['error' => 'Product not found.'], 404);
            } catch (\Exception $e) {
                Log::error('Error in getProductRow', [
                    'row_index' => $row_index,
                    'variation_id' => $variation_id,
                    'location_id' => $location_id,
                    'exception' => $e->getMessage()
                ]);
                return response()->json(['error' => 'An error occurred while processing your request.'], 500);
            }
        }
        return response()->json(['error' => 'Invalid request.'], 400);
    }
}
