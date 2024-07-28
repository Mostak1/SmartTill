<?php

namespace App\Http\Controllers;

use App\Category;
use App\Utils\ModuleUtil;
use App\Variation;
use App\VariationPriceHistory;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class TaxonomyController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $category_type = request()->get('type');
        if ($category_type == 'product' && !auth()->user()->can('category.view') && !auth()->user()->can('category.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $can_edit = true;
            if ($category_type == 'product' && !auth()->user()->can('category.update')) {
                $can_edit = false;
            }

            $can_delete = true;
            if ($category_type == 'product' && !auth()->user()->can('category.delete')) {
                $can_delete = false;
            }

            $business_id = request()->session()->get('user.business_id');

            $category = Category::where('business_id', $business_id)
                ->where('category_type', $category_type)
                ->select(['name', 'short_code', 'description', 'id', 'parent_id', 'is_us_product']);

            return Datatables::of($category)
                ->addColumn(
                    'action',
                    function ($row) use ($can_edit, $can_delete, $category_type) {
                        $html = '';
                        if ($row->is_us_product == 0 && $can_edit) {
                            $html .= '<button data-href="' . action([\App\Http\Controllers\TaxonomyController::class, 'edit'], [$row->id]) . '?type=' . $category_type . '" class="btn btn-xs btn-primary edit_category_button"><i class="glyphicon glyphicon-edit"></i>' . __('messages.edit') . '</button>';
                        } elseif (auth()->user()->can('category.usa')) {
                            $html .= '<button data-href="' . action([\App\Http\Controllers\TaxonomyController::class, 'edit'], [$row->id]) . '?type=' . $category_type . '" class="btn btn-xs btn-primary edit_category_button"><i class="glyphicon glyphicon-edit"></i>' . __('messages.edit') . '</button>';
                        }
                        if ($row->is_us_product == 0 && $can_delete) {
                            $html .= '&nbsp;<button data-href="' . action([\App\Http\Controllers\TaxonomyController::class, 'destroy'], [$row->id]) . '" class="btn btn-xs btn-danger delete_category_button"><i class="glyphicon glyphicon-trash"></i> ' . __('messages.delete') . '</button>';
                        }
                        // elseif(auth()->user()->can('superadmin') && $row->is_us_product == 1){
                        //     $html .= '&nbsp;<button data-href="' . action([\App\Http\Controllers\TaxonomyController::class, 'destroy'], [$row->id]) . '" class="btn btn-xs btn-danger delete_category_button"><i class="glyphicon glyphicon-trash"></i> ' . __('messages.delete') . '</button>';  
                        // }
                        if ($row->is_us_product == 1 && auth()->user()->can('category.history')) {
                            $html .= '&nbsp;<button data-href="' . action([\App\Http\Controllers\TaxonomyController::class, 'getRate']) . '" class="btn btn-xs btn-info rate_category_button"><i class="fas fa-history"></i> ' . __('History') . '</button>';
                        }
                        return $html;
                    }
                )
                ->editColumn('name', function ($row) {
                    if ($row->parent_id != 0) {
                        return '--' . $row->name;
                    } else {
                        return $row->name;
                    }
                })
                ->removeColumn('id')
                ->removeColumn('parent_id')
                ->rawColumns(['action'])
                ->make(true);
        }

        $module_category_data = $this->moduleUtil->getTaxonomyData($category_type);
        $foreign_cat = Category::where('is_us_product', 1)->first();
        if ($foreign_cat) {
            $PriceHistory = VariationPriceHistory::where('variation_id', (isset($foreign_cat) ? $foreign_cat->id : null))
                ->where('type', 'category')
                ->orderBy('created_at', 'desc') // You can change the order as per your requirement
                ->get();
        } else {
            $PriceHistory = VariationPriceHistory::where('type', 'category')
                ->orderBy('created_at', 'desc') // You can change the order as per your requirement
                ->get();
        }

        return view('taxonomy.index')->with(compact('module_category_data', 'module_category_data', 'PriceHistory'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $category_type = request()->get('type');
        if ($category_type == 'product' && !auth()->user()->can('category.create')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        $module_category_data = $this->moduleUtil->getTaxonomyData($category_type);

        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->where('category_type', $category_type)
            ->select(['name', 'short_code', 'id'])
            ->get();

        $parent_categories = [];
        if (!empty($categories)) {
            foreach ($categories as $category) {
                $parent_categories[$category->id] = $category->name;
            }
        }

        return view('taxonomy.create')
            ->with(compact('parent_categories', 'module_category_data', 'category_type'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $category_type = request()->input('category_type');
        if ($category_type == 'product' && !auth()->user()->can('category.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['name', 'short_code', 'category_type', 'description']);
            if (!empty($request->input('add_as_sub_cat')) && $request->input('add_as_sub_cat') == 1 && !empty($request->input('parent_id'))) {
                $input['parent_id'] = $request->input('parent_id');
            } else {
                $input['parent_id'] = 0;
            }
            $input['business_id'] = $request->session()->get('user.business_id');
            $input['created_by'] = $request->session()->get('user.id');

            $category = Category::create($input);
            $output = [
                'success' => true,
                'data' => $category,
                'msg' => __('category.added_success'),
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
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $category_type = request()->get('type');
        if ($category_type == 'product' && !auth()->user()->can('category.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $category = Category::where('business_id', $business_id)->find($id);

            $module_category_data = $this->moduleUtil->getTaxonomyData($category_type);

            $parent_categories = Category::where('business_id', $business_id)
                ->where('parent_id', 0)
                ->where('category_type', $category_type)
                ->where('id', '!=', $id)
                ->pluck('name', 'id');
            $is_parent = false;

            if ($category->parent_id == 0) {
                $is_parent = true;
                $selected_parent = null;
            } else {
                $selected_parent = $category->parent_id;
            }

            return view('taxonomy.edit')
                ->with(compact('category', 'parent_categories', 'is_parent', 'selected_parent', 'module_category_data'));
        }
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
        if (request()->ajax()) {
            try {
                $input = $request->only(['name', 'description']);
                $business_id = $request->session()->get('user.business_id');

                $category = Category::where('business_id', $business_id)->findOrFail($id);

                if ($category->category_type == 'product' && !auth()->user()->can('category.update')) {
                    abort(403, 'Unauthorized action.');
                }

                $category->name = $input['name'];
                $category->short_code = $request->input('short_code');

                if (!empty($request->input('add_as_sub_cat')) && $request->input('add_as_sub_cat') == 1 && !empty($request->input('parent_id'))) {
                    $category->parent_id = $request->input('parent_id');
                } else {
                    $category->parent_id = 0;
                }

                $foreign_cat = $category->is_us_product;
               if ($foreign_cat == 1) {

                    if ($category->description != $input['description']) {
                        // Create a new price history entry
                        VariationPriceHistory::create([
                            'variation_id' => $category->id,
                            'old_price' => $category->description,
                            'new_price' => $input['description'],
                            'updated_by' => auth()->id(),
                            'type' => 'category',
                        ]);
                    }
                    $category->description = $input['description'];
                    $category->save();

                    $foreign_variations = Variation::where('is_foreign', 1)->get();
                    foreach ($foreign_variations as $foreign_variation) {
                        $foreign_variation->currency_rate = $category->description;
                        $foreign_variation->default_purchase_price = $foreign_variation->foreign_p_price * $category->description;
                        $foreign_variation->dpp_inc_tax = round(($foreign_variation->foreign_p_price_inc_tex * $category->description) / 10) * 10;
                        $foreign_variation->default_sell_price = round(($foreign_variation->foreign_s_price * $category->description) / 10) * 10;

                        // Update the variation's price
                        $old_price = $foreign_variation->dpp_inc_tax;
                        $newPrice = round(($foreign_variation->foreign_s_price_inc_tex * $category->description) / 10) * 10;
                        if ($foreign_variation->sell_price_inc_tax != $newPrice) {
                            // Create a new price history entry
                            VariationPriceHistory::create([
                                'variation_id' => $foreign_variation->product_id,
                                'old_price' => '$ ' . number_format($foreign_variation->foreign_p_price_inc_tex, 2) . '<br>৳ ' . number_format($old_price, 2) . '<br>$⇄৳ ' . number_format($category->description, 2),
                                'new_price' => '$ ' . number_format($foreign_variation->foreign_s_price_inc_tex, 2) . '<br>৳ ' . number_format($newPrice, 2) . '<br>$⇄৳ ' . number_format($category->description, 2),
                                'updated_by' => auth()->id(),
                                'type' => 'product',
                                'h_type' => 'Dollar Rate Change'
                            ]);
                        }
                        $foreign_variation->sell_price_inc_tax = round(($foreign_variation->foreign_s_price_inc_tex * $category->description) / 10) * 10;

                        $foreign_variation->save();
                    }
                } else {
                    $category->save();
                }

                $output = [
                    'success' => true,
                    'msg' => __('category.updated_success'),
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');

                $category = Category::where('business_id', $business_id)->findOrFail($id);

                if ($category->category_type == 'product' && !auth()->user()->can('category.delete')) {
                    abort(403, 'Unauthorized action.');
                }

                $category->delete();

                $output = [
                    'success' => true,
                    'msg' => __('category.deleted_success'),
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

    public function getCategoriesApi()
    {
        try {
            $api_token = request()->header('API-TOKEN');

            $api_settings = $this->moduleUtil->getApiSettings($api_token);

            $categories = Category::catAndSubCategories($api_settings->business_id);
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            return $this->respondWentWrong($e);
        }

        return $this->respond($categories);
    }

    /**
     * get taxonomy index page
     * through ajax
     *
     * @return \Illuminate\Http\Response
     */
    public function getTaxonomyIndexPage(Request $request)
    {
        if (request()->ajax()) {
            $category_type = $request->get('category_type');
            $module_category_data = $this->moduleUtil->getTaxonomyData($category_type);

            return view('taxonomy.ajax_index')
                ->with(compact('module_category_data', 'category_type'));
        }
    }

    public function getRate()
    {
        $foreign_cat = Category::where('is_us_product', 1)->first();
        $PriceHistory = VariationPriceHistory::where('variation_id', $foreign_cat->id)
            ->where('type', 'category')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('taxonomy.rate')->with(compact('PriceHistory'));
    }
}
