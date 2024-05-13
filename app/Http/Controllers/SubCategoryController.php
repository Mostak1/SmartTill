<?php

namespace App\Http\Controllers;

use App\Category;
use App\SubCategory;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SubCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected $moduleUtil;
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    public function indexjson()
    {
        $business_id = request()->session()->get('user.business_id');

        $subcategories = SubCategory::where('business_id', $business_id)->with('category','creator')->get();
        return response()->json(['subcategories' => $subcategories]);
    }

    public function index()
    {

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $SubCategory = SubCategory::with('category')->where('business_id', $business_id)
                ->select('*');

            return Datatables::of($SubCategory)->addColumn(
                'action',
                function ($row) {
                    $html = '';
                    $html .= '<button data-href="' . action([\App\Http\Controllers\SubCategoryController::class, 'edit'], [$row->id]) . '" class="btn btn-xs btn-primary subcategories_modal"><i class="glyphicon glyphicon-edit"></i>' . __('messages.edit') . '</button>';
                    $html .= '&nbsp;<button data-href="' . action([\App\Http\Controllers\SubCategoryController::class, 'destroy'], [$row->id]) . '" class="btn btn-xs btn-danger delete_category_button"><i class="glyphicon glyphicon-trash"></i> ' . __('messages.delete') . '</button>';
                    return $html;
                }
            )
                ->editColumn('name', function ($row) {
                    return $row->name;
                })
                ->editColumn('category', function ($row) {
                    return $row->category->name;
                })
                ->editColumn('created_by', function ($row) {
                    return $row->created_by;
                })->orderColumn('name', 'asc')
                ->removeColumn('id')
                ->rawColumns(['action','created_by','name','category'])
                ->make(true);
        }
        return view('subcategory.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // if (!auth()->user()->can('brand.create')) {
        //     abort(403, 'Unauthorized action.');
        // }
        $business_id = request()->session()->get('user.business_id');
        $quick_add = false;
        if (!empty(request()->input('quick_add'))) {
            $quick_add = true;
        }
        // $categories = Category::pluck('name', 'id');
        $categories = Category::forDropdown($business_id, 'product');
        $is_repair_installed = $this->moduleUtil->isModuleInstalled('Repair');

        return view('subcategory.create')
            ->with(compact('quick_add', 'is_repair_installed', 'categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('brand.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['name', 'category_id']);
            $business_id = $request->session()->get('user.business_id');
            $input['business_id'] = $business_id;
            $input['created_by'] = $request->session()->get('user.id');

            if ($this->moduleUtil->isModuleInstalled('Repair')) {
                $input['use_for_repair'] = !empty($request->input('use_for_repair')) ? 1 : 0;
            }

            $subcategory = SubCategory::create($input);
            $output = [
                'success' => true,
                'data' => $subcategory,
                'msg' => __('subcategory.added_success'),
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
     * @param  \App\SubCategory  $subCategory
     * @return \Illuminate\Http\Response
     */
    public function show(SubCategory $subCategory)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\SubCategory  $subCategory
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('subcategory.edit')) {
            abort(403, 'Unauthorized action.');
        }
        $quick_add = false;
        if (!empty(request()->input('quick_add'))) {
            $quick_add = true;
        }
        $categories = Category::pluck('name', 'id');
        $business_id = request()->session()->get('user.business_id');
        $subcategory = SubCategory::where('business_id', $business_id)->find($id);

        $is_repair_installed = $this->moduleUtil->isModuleInstalled('Repair');

        return view('subcategory.edit')
            ->with(compact('quick_add', 'subcategory', 'is_repair_installed', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\SubCategory  $subCategory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // if (!auth()->user()->can('subcategory.update')) {
        //     abort(403, 'Unauthorized action.');
        // }
        try {
            $input = $request->only(['name', 'category_id']);
            $business_id = $request->session()->get('user.business_id');
            $subcategory = SubCategory::where('business_id', $business_id)->findOrFail($id);
            $subcategory->name = $input['name'];
            $subcategory->category_id = $input['category_id'];

            if ($this->moduleUtil->isModuleInstalled('Repair')) {
                $subcategory->use_for_repair = !empty($request->input('use_for_repair')) ? 1 : 0;
            }

            $subcategory->save();

            $output = [
                'success' => true,
                'msg' => __('subcategory.updated_success'),
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
     * Remove the specified resource from storage.
     *
     * @param  \App\SubCategory  $subCategory
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $business_id = request()->user()->business_id;

            $brand = SubCategory::where('business_id', $business_id)->findOrFail($id);
            $brand->delete();

            $output = ['success' => true,
                'msg' => __('brand.deleted_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }
}
