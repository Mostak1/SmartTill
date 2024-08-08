<?php

namespace App\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Category;
use App\FinalizeReport;
use App\Product;
use App\RandomCheck;
use App\RandomCheckDetail;
use App\ReportItem;
use App\Utils\ProductUtil;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\DB;


class CheckController extends Controller
{

    protected $productUtil;

    
    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil)
    {
        $this->productUtil = $productUtil;
    }


    public function randomCheck(Request $request)
    {  
        if (!auth()->user()->can('stock_audit.view')) {
        abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        try {
            if ($request->ajax()) {
                // Aggregate the sum of physical_count for each check
                $random_checks = RandomCheck::leftJoin('random_check_details', 'random_check_details.random_check_id', '=', 'random_checks.id')
                    ->leftJoin('users as checked_by_user', 'random_checks.checked_by', '=', 'checked_by_user.id') // Join with users table for checked_by
                    ->leftJoin('users as modified_by_user', 'random_checks.modified_by', '=', 'modified_by_user.id') // Join with users table for modified_by
                    ->leftJoin('business_locations', 'random_check_details.location_id', '=', 'business_locations.id') // Join with business_locations table
                    ->select(
                        'random_checks.id as check_id',
                        'random_checks.check_no as check_no',
                        'random_checks.comment as random_check_comment',
                        DB::raw("CONCAT(checked_by_user.first_name, ' ', checked_by_user.last_name) as checked_by"), // Concatenate first and last name
                        DB::raw("CONCAT(modified_by_user.first_name, ' ', modified_by_user.last_name) as modified_by"), // Concatenate first and last name
                        'business_locations.name as location_name', // Select location name
                        DB::raw('SUM(random_check_details.physical_count) as total_physical_count'),
                        DB::raw('COUNT(DISTINCT random_check_details.product_name) as total_product_count'),
                        'random_checks.created_at'
                    );

                // Filter by location_id if provided in the request
                if ($request->has('location_id') && $request->location_id != null) {
                    $random_checks->where('random_check_details.location_id', $request->location_id);
                }

                // Filter by physical count type (multiple options)
                if ($request->has('physical_count_filter') && $request->physical_count_filter != 'all') {
                    switch ($request->physical_count_filter) {
                        case 'surplus':
                            $random_checks->where('random_check_details.physical_count', '>', 0);
                            break;
                        case 'match':
                            $random_checks->where('random_check_details.physical_count', '=', 0);
                            break;
                        case 'missing':
                            $random_checks->where('random_check_details.physical_count', '<', 0);
                            break;
                    }
                }

                // Filter by date range
                if (!empty(request()->start_date) && !empty(request()->end_date)) {
                    $start = request()->start_date;
                    $end = request()->end_date;
                    $random_checks->whereDate('random_checks.created_at', '>=', $start)
                                ->whereDate('random_checks.created_at', '<=', $end);
                }

                // Ensure the check_no is searchable
                if ($request->has('search') && !empty($request->search['value'])) {
                    $searchValue = $request->search['value'];
                    $random_checks->where('check_no', 'like', '%' . $searchValue . '%');
                }

                $random_checks->groupBy(
                    'random_checks.id', 
                    'random_checks.check_no', 
                    'random_checks.comment', 
                    'checked_by_user.first_name', 
                    'checked_by_user.last_name', 
                    'modified_by_user.first_name', 
                    'modified_by_user.last_name', 
                    'business_locations.name', // Group by location name
                    'random_checks.created_at'
                )->orderBy('random_checks.created_at', 'desc');
                // Log the count of the retrieved records
                \Log::info('Total random checks fetched: ' . $random_checks->count());

                return Datatables::of($random_checks)
                    ->addColumn('action', function ($row) {
                        $html = '<div class="btn-group">
                                    <button class="btn btn-info dropdown-toggle btn-xs" type="button" data-toggle="dropdown" aria-expanded="false">
                                        ' . __('messages.action') . '
                                        <span class="caret"></span>
                                        <span class="sr-only">' . __('messages.action') . '</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                        <li>
                                            <a data-href="' . action([\App\Http\Controllers\CheckController::class, 'checkShow'], [$row->check_id]) . '" class="cursor-pointer view_random_check">
                                                <i class="fa fa-eye"></i> ' . __('messages.view') . '
                                            </a>
                                        </li>';
    if (auth()->user()->can('stock_audit.update')) {
                        $html .=     '<li>
                                        <a href="#" data-href="' . action([\App\Http\Controllers\CheckController::class, 'checkEdit'], [$row->check_id]) . '" class="cursor-pointer edit-random-check">
                                            <i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '
                                        </a>
                                    </li>';
        }
    if (auth()->user()->can('stock_audit.delete')) {
                        $html .=
                                        '<li>
                                        <a href="#" data-href="' . action([\App\Http\Controllers\CheckController::class, 'checkDelete'], [$row->check_id]) . '" class="cursor-pointer delete-random-check">
                                            <i class="fas fa-file-archive"></i> ' . __('Archive') . '
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" data-href="' . action([\App\Http\Controllers\CheckController::class, 'checkPermanentDelete'], [$row->check_id]) . '" class="cursor-pointer delete-permanent-check">
                                            <i class="fa fa-trash"></i> ' . __('messages.delete') . '
                                        </a>
                                    </li>';
        }
                        $html .=  '</ul>
                                </div>';
                        return $html;
                    })
                    ->addColumn('check_no', function ($row) {
                        $url = action([\App\Http\Controllers\CheckController::class, 'checkShow'], [$row->check_id]);
                        return '<a href="#" data-href="' . $url . '" class="view_random_check text-black">' . $row->check_no . '</a>';
                    })                    
                    ->addColumn('random_check_comment', function ($row) {
                        $comment = $row->random_check_comment;
                        $url = action([\App\Http\Controllers\CheckController::class, 'checkShow'], [$row->check_id]);
                        return '<a href="#" data-href="' . $url . '" class="view_random_check text-black">' . (strlen($comment) > 30 ? substr($comment, 0, 30) . '...' : $comment) . '</a>';
                    })
                    ->addColumn('checked_by', function ($row) {
                        $url = action([\App\Http\Controllers\CheckController::class, 'checkShow'], [$row->check_id]);
                        return '<a href="#" data-href="' . $url . '" class="view_random_check text-black">' . $row->checked_by . '</a>';
                    })
                    ->addColumn('modified_by', function ($row) {
                        return '<span>' . $row->modified_by . '</span>';
                    })
                    ->addColumn('location_name', function ($row) {
                        $url = action([\App\Http\Controllers\CheckController::class, 'checkShow'], [$row->check_id]);
                        return '<a href="#" data-href="' . $url . '" class="view_random_check text-black">' . $row->location_name . '</a>';
                    })
                    ->addColumn('total_physical_count', function ($row) {
                        // Fetch all physical counts for the current check
                        $details = RandomCheckDetail::where('random_check_id', $row->check_id)->get();

                        $positiveCount = $details->where('physical_count', '>', 0)->sum('physical_count');
                        $negativeCount = $details->where('physical_count', '<', 0)->sum('physical_count');
                        $url = action([\App\Http\Controllers\CheckController::class, 'checkShow'], [$row->check_id]);

                        if ($positiveCount == 0 && $negativeCount == 0) {
                            return '<a href="#" data-href="' . $url . '" class="view_random_check text-black">' . '0 (match)' . '</a>';
                        } elseif ($positiveCount > 0 && $negativeCount < 0) {
                            return '<a href="#" data-href="' . $url . '" class="view_random_check text-black">+' . number_format($positiveCount) . ' (surplus)<br>' . number_format($negativeCount) . ' (missing)</a>';
                        } elseif ($positiveCount > 0) {
                            return '<a href="#" data-href="' . $url . '" class="view_random_check text-black">+' . number_format($positiveCount) . ' (surplus)</a>';
                        } else {
                            return '<a href="#" data-href="' . $url . '" class="view_random_check text-black">' . number_format($negativeCount) . ' (missing)</a>';
                        }
                    })
                    ->addColumn('total_product_count', function ($row) {
                        $url = action([\App\Http\Controllers\CheckController::class, 'checkShow'], [$row->check_id]);
                        return '<a href="#" data-href="' . $url . '" class="view_random_check text-black">' . $row->total_product_count . '</a>';
                    })
                    ->editColumn('created_at', function ($row) {
                        $url = action([\App\Http\Controllers\CheckController::class, 'checkShow'], [$row->check_id]);
                        return '<a href="#" data-href="' . $url . '" class="view_random_check text-black">' . \Carbon\Carbon::parse($row->created_at)->format('d F Y, g:i A') . '</a>';
                    })
                    ->rawColumns(['action', 'created_at', 'check_no', 'random_check_comment', 'checked_by', 'modified_by', 'location_name', 'total_physical_count', 'total_product_count'])
                    ->make(true);
            }

            // For non-AJAX requests, prepare necessary data like dropdowns, etc.
            $business_locations = BusinessLocation::forDropdown($business_id, false);
            $categories = Category::forDropdown($business_id, 'product');

            return view('random_check.index')->with(compact('business_locations', 'categories'));
        } catch (\Exception $e) {
            // Log any exceptions for troubleshooting
            \Log::error('Error in randomCheck method: ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong. Please try again.'], 500);
        }
    }


    public function archivedRandomCheck(Request $request)
    {  
        if (!auth()->user()->can('stock_audit.view')) {
        abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        try {
            if ($request->ajax()) {
                // Aggregate the sum of physical_count for each check
                $random_checks = RandomCheck::onlyTrashed()
                    ->leftJoin('random_check_details', 'random_check_details.random_check_id', '=', 'random_checks.id')
                    ->leftJoin('users as checked_by_user', 'random_checks.checked_by', '=', 'checked_by_user.id') // Join with users table for checked_by
                    ->leftJoin('users as modified_by_user', 'random_checks.modified_by', '=', 'modified_by_user.id') // Join with users table for modified_by
                    ->leftJoin('business_locations', 'random_check_details.location_id', '=', 'business_locations.id') // Join with business_locations table
                    ->select(
                        'random_checks.id as check_id',
                        'random_checks.check_no as check_no',
                        'random_checks.comment as random_check_comment',
                        DB::raw("CONCAT(checked_by_user.first_name, ' ', checked_by_user.last_name) as checked_by"), // Concatenate first and last name
                        DB::raw("CONCAT(modified_by_user.first_name, ' ', modified_by_user.last_name) as modified_by"), // Concatenate first and last name
                        'business_locations.name as location_name', // Select location name
                        DB::raw('SUM(random_check_details.physical_count) as total_physical_count'),
                        DB::raw('COUNT(DISTINCT random_check_details.product_name) as total_product_count'),
                        'random_checks.created_at'
                    );

                // Filter by location_id if provided in the request
                if ($request->has('location_id') && $request->location_id != null) {
                    $random_checks->where('random_check_details.location_id', $request->location_id);
                }

                // Filter by physical count type (multiple options)
                if ($request->has('physical_count_filter') && $request->physical_count_filter != 'all') {
                    switch ($request->physical_count_filter) {
                        case 'surplus':
                            $random_checks->where('random_check_details.physical_count', '>', 0);
                            break;
                        case 'match':
                            $random_checks->where('random_check_details.physical_count', '=', 0);
                            break;
                        case 'missing':
                            $random_checks->where('random_check_details.physical_count', '<', 0);
                            break;
                    }
                }

                // Filter by date range
                if (!empty(request()->start_date) && !empty(request()->end_date)) {
                    $start = request()->start_date;
                    $end = request()->end_date;
                    $random_checks->whereDate('random_checks.created_at', '>=', $start)
                                ->whereDate('random_checks.created_at', '<=', $end);
                }

                // Ensure the check_no is searchable
                if ($request->has('search') && !empty($request->search['value'])) {
                    $searchValue = $request->search['value'];
                    $random_checks->where('check_no', 'like', '%' . $searchValue . '%');
                }

                $random_checks->groupBy(
                    'random_checks.id', 
                    'random_checks.check_no', 
                    'random_checks.comment', 
                    'checked_by_user.first_name', 
                    'checked_by_user.last_name', 
                    'modified_by_user.first_name', 
                    'modified_by_user.last_name', 
                    'business_locations.name', // Group by location name
                    'random_checks.created_at'
                )->orderBy('random_checks.created_at', 'desc');
                // Log the count of the retrieved records
                \Log::info('Total random checks fetched: ' . $random_checks->count());

                return Datatables::of($random_checks)
                    ->addColumn('action', function ($row) {
                        $html = '<div class="btn-group">
                                    <button class="btn btn-info dropdown-toggle btn-xs" type="button" data-toggle="dropdown" aria-expanded="false">
                                        ' . __('messages.action') . '
                                        <span class="caret"></span>
                                        <span class="sr-only">' . __('messages.action') . '</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right" role="menu">';
                    
                        if (auth()->user()->can('stock_audit.create')) {
                            $html .= '<li>
                                        <a href="#" data-href="' . action([\App\Http\Controllers\CheckController::class, 'checkRestore'], [$row->check_id]) . '" class="cursor-pointer restore-check">
                                            <i class="fas fa-trash-restore"></i> ' . __('Restore') . '
                                        </a>
                                    </li>';
                        }
                    
                        if (auth()->user()->can('stock_audit.delete')) {
                            $html .= '<li>
                                        <a href="#" data-href="' . action([\App\Http\Controllers\CheckController::class, 'checkPermanentDelete'], [$row->check_id]) . '" class="cursor-pointer delete-permanent-check">
                                            <i class="fa fa-trash"></i> ' . __('messages.delete') . '
                                        </a>
                                    </li>';
                        }
                    
                        $html .= '</ul>
                                </div>';
                    
                        return $html;
                    })
                
                    ->addColumn('check_no', function ($row) {
                        
                        return $row->check_no;
                    })                    
                    ->addColumn('random_check_comment', function ($row) {
                        $comment = $row->random_check_comment;
                        
                        return (strlen($comment) > 30 ? substr($comment, 0, 30) . '...' : $comment);
                    })
                    ->addColumn('checked_by', function ($row) {
                        
                        return $row->checked_by;
                    })
                    ->addColumn('modified_by', function ($row) {
                        return '<span>' . $row->modified_by . '</span>';
                    })
                    ->addColumn('location_name', function ($row) {
                        
                        return $row->location_name;
                    })
                    ->addColumn('total_physical_count', function ($row) {
                        // Fetch all physical counts for the current check
                        $details = RandomCheckDetail::where('random_check_id', $row->check_id)->onlyTrashed()->get();

                        $positiveCount = $details->where('physical_count', '>', 0)->sum('physical_count');
                        $negativeCount = $details->where('physical_count', '<', 0)->sum('physical_count');
                        

                        if ($positiveCount == 0 && $negativeCount == 0) {
                            return '0 (match)';
                        } elseif ($positiveCount > 0 && $negativeCount < 0) {
                            return '+' . number_format($positiveCount) . ' (surplus)<br>' . number_format($negativeCount) . '(missing)';
                        } elseif ($positiveCount > 0) {
                            return '+' . number_format($positiveCount) . ' (surplus)';
                        } else {
                            return number_format($negativeCount) . ' (missing)';
                        }

                    })
                    ->addColumn('total_product_count', function ($row) {
                        
                        return $row->total_product_count;
                    })
                    ->editColumn('created_at', function ($row) {
                        
                        return \Carbon\Carbon::parse($row->created_at)->format('d F Y, g:i A');
                    })
                    ->rawColumns(['action', 'created_at', 'check_no', 'random_check_comment', 'checked_by', 'modified_by', 'location_name', 'total_physical_count', 'total_product_count'])
                    ->make(true);
            }

            // For non-AJAX requests, prepare necessary data like dropdowns, etc.
            $business_locations = BusinessLocation::forDropdown($business_id, false);
            $categories = Category::forDropdown($business_id, 'product');

            return view('random_check.index')->with(compact('business_locations', 'categories'));
        } catch (\Exception $e) {
            // Log any exceptions for troubleshooting
            \Log::error('Error in randomCheck method: ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong. Please try again.'], 500);
        }
    }


    public function randomCheckDetails(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        try {
            if ($request->ajax()) {
                $query = RandomCheckDetail::leftJoin('random_checks', 'random_checks.id', '=', 'random_check_details.random_check_id')
                    ->leftJoin('products', 'random_check_details.product_id', '=', 'products.id')
                    ->select(
                        'random_checks.id as check_id',
                        'random_checks.check_no',
                        'random_check_details.category_name',
                        'random_check_details.product_name',
                        'random_check_details.sku',
                        'random_check_details.brand_name',
                        'random_check_details.current_stock',
                        'random_check_details.physical_count',
                        'random_check_details.comment',
                        'random_check_details.created_at',
                        'random_check_details.updated_at',
                        'random_check_details.location_id'
                    )
                    ->orderBy('random_check_details.created_at', 'desc');

                    // Filter by location_id if provided in the request
                    if ($request->has('location_id') && $request->location_id != null) {
                        $query->where('random_check_details.location_id', $request->location_id);
                    }

                    // Filter by physical count type (multiple options)
                    if ($request->has('physical_count_filter') && $request->physical_count_filter != 'all') {
                        switch ($request->physical_count_filter) {
                            case 'surplus':
                                $query->where('random_check_details.physical_count', '>', 0);
                                break;
                            case 'match':
                                $query->where('random_check_details.physical_count', '=', 0);
                                break;
                            case 'missing':
                                $query->where('random_check_details.physical_count', '<', 0);
                                break;
                        }
                    }

                    // Filter by category_name if provided in the request
                    if ($request->has('category_name') && !empty($request->category_name)) {
                        $categories = $request->category_name;
                        $query->whereIn('random_check_details.category_name', $categories);
                    }

                     // Filter by category_name if provided in the request
                    if ($request->has('category_name') && $request->category_name != null) {
                        $query->where('random_check_details.category_name', $request->category_name);
                    }

                    // Filter by date range
                    if (!empty(request()->start_date) && !empty(request()->end_date)) {
                        $start = request()->start_date;
                        $end = request()->end_date;
                        $query->whereDate('random_check_details.created_at', '>=', $start)
                                    ->whereDate('random_check_details.created_at', '<=', $end);
                    }

                    // Ensure both check_no and checked_by are searchable
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $searchValue = $request->search['value'];
                        $query->where(function($q) use ($searchValue) {
                            $q->where('random_check_details.random_check_id', 'like', '%' . $searchValue . '%')
                                ->orWhere('random_check_details.sku', 'like', '%' . $searchValue . '%')
                                ->orWhere('random_check_details.product_name', 'like', '%' . $searchValue . '%');
                        });
                    }

                return Datatables::of($query)
                    ->addColumn('action', function ($row) {
                        $html = '<div class="btn-group">
                                    <button class="btn btn-info dropdown-toggle btn-xs" type="button" data-toggle="dropdown" aria-expanded="false">
                                        ' . __('messages.action') . '
                                        <span class="caret"></span>
                                        <span class="sr-only">' . __('messages.action') . '</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                        <li>
                                            <a data-href="' . action([\App\Http\Controllers\CheckController::class, 'checkShow'], [$row->check_id]) . '" class="cursor-pointer view_random_check">
                                                <i class="fa fa-eye"></i> ' . __('messages.view') . '
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#" data-href="' . action([\App\Http\Controllers\CheckController::class, 'checkEdit'], [$row->check_id]) . '" class="cursor-pointer edit-random-check">
                                                <i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '
                                            </a>
                                        </li>
                                    </ul>
                                </div>';
                        return $html;
                    })  
                    ->editColumn('physical_count', function ($row) {
                        $p_count = $row->physical_count;
                        $html = "";
                        if ($p_count > 0) {
                            $html = '+' . number_format($p_count) . ' (surplus)';
                        }
                        elseif($p_count < 0)
                        {
                            $html =   number_format($p_count) . ' (missing)';
                        }
                         else {
                            $html =   number_format($p_count) . ' (match)';
                        }
                        return  $html;
                    })
                    ->editColumn('created_at', function ($row) {
                        return \Carbon\Carbon::parse($row->created_at)->format('d F Y, g:i A');
                    })
                    ->editColumn('updated_at', function ($row) {
                        if ($row->created_at == $row->updated_at) {
                            return "";
                        } else {
                            return \Carbon\Carbon::parse($row->updated_at)->format('d F Y, g:i A');
                        }
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }

            $business_locations = BusinessLocation::forDropdown($business_id, false);
            $categories = Category::forDropdown($business_id, 'product');

            return view('random_check.index')->with(compact('business_locations', 'categories'));
        } catch (\Exception $e) {
            \Log::error('Error in randomCheckDetails method: ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong. Please try again.'], 500);
        }
    }


    public function checkReportIndex(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        try {
            if ($request->ajax()) {
                $query = FinalizeReport::leftJoin('business_locations', 'finalize_reports.location_id', '=', 'business_locations.id')
                    ->leftJoin('users', 'finalize_reports.finalized_by', '=', 'users.id') // Join users table
                    ->select(
                        'finalize_reports.id as report_id',
                        'finalize_reports.report_no',
                        'finalize_reports.date',
                        'users.first_name',
                        'users.last_name',
                        'business_locations.name as location_name',
                        'finalize_reports.date_range_covered',
                        'finalize_reports.number_of_checks_covered',
                        'finalize_reports.net_result',
                        'finalize_reports.comments'
                    )
                    ->orderBy('finalize_reports.created_at', 'desc');

                // Filter by location_id if provided in the request
                if ($request->has('location_id') && $request->location_id != null) {
                    $query->where('finalize_reports.location_id', $request->location_id);
                }

                // Filter by date range
                if (!empty($request->start_date) && !empty($request->end_date)) {
                    $start = $request->start_date;
                    $end = $request->end_date;
                    $query->whereDate('finalize_reports.date', '>=', $start)
                        ->whereDate('finalize_reports.date', '<=', $end);
                }

                // Ensure the search field works
                if ($request->has('search') && !empty($request->search['value'])) {
                    $searchValue = $request->search['value'];
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('finalize_reports.report_no', 'like', '%' . $searchValue . '%')
                            ->orWhere('finalize_reports.comments', 'like', '%' . $searchValue . '%');
                    });
                }

                return Datatables::of($query)

                    ->addColumn('report_no', function ($row) {
                        $url = action([\App\Http\Controllers\CheckController::class, 'viewReportItem'], [$row->report_id]);
                        return '<a href="#" data-href="' . $url . '" class="view_report_item">' . $row->report_no . '</a>';
                    }) 

                    ->editColumn('date', function ($row) {
                        return \Carbon\Carbon::parse($row->date)->format('d F Y');
                    })
                    ->editColumn('finalized_by', function ($row) {
                        return $row->first_name . ' ' . $row->last_name;
                    })
                    ->editColumn('net_result', function ($row) {
                        return '৳ ' . abs($row->net_result);
                    })
                    ->editColumn('status', function ($row) {
                        $status = $row->net_result < 0 ? 'Loss' : 'Profit';
                        $color = $row->net_result < 0 ? 'red' : 'green';
                        return "<span style='color: {$color};'>{$status}</span>";
                    })
                    ->rawColumns(['date', 'finalized_by', 'status', 'report_no'])
                    ->make(true);
            }

            $business_locations = BusinessLocation::forDropdown($business_id, false);
            $categories = Category::forDropdown($business_id, 'product');

            return view('random_check.index')->with(compact('business_locations', 'categories'));

        } catch (\Exception $e) {
            \Log::error('Error in checkReportIndex method: ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong. Please try again.'], 500);
        }
    }



    

    public function createRandomCheck()
    {
        if (!auth()->user()->can('stock_audit.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id);
        $category_type = 'product';

        // Get categories
        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->where('category_type', $category_type)
            ->select(['name', 'id'])
            ->get();

        $parent_categories = [];
        if (!empty($categories)) {
            foreach ($categories as $category) {
                $parent_categories[$category->id] = $category->name;
            }
        }

        return view('random_check.create')
            ->with(compact('parent_categories', 'business_locations'));
    }


    public function generateRandom(Request $request)
    {
        if (!auth()->user()->can('stock_audit.create')) {
            abort(403, 'Unauthorized action.');
        }

        // $business_id = $request->session()->get('user.business_id');
        $location_id = $request->input('random_check_filter_location_id');
        $categories = $request->input('categories');
    
        $categories_products = [];
    
        foreach ($categories as $category) {
            $query = Product::with(['media'])
                ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
                ->join('units', 'products.unit_id', '=', 'units.id')
                ->leftJoin('categories as c', 'products.category_id', '=', 'c.id')
                ->leftJoin('tax_rates', 'products.tax', '=', 'tax_rates.id')
                ->join('variations as v', 'v.product_id', '=', 'products.id')
                ->leftJoin('variation_location_details as vld', 'vld.variation_id', '=', 'v.id') 
                ->whereNull('products.deleted_at')
                ->where('products.business_id', $location_id)
                ->where('products.type', '!=', 'modifier')
                ->Active();
    
            // Exclude products that have not been sold in the last year
            $query->whereHas('transactionSellLines', function ($query) {
                $query->where('created_at', '>=', now()->subYear());
            });


            // Additional filter to ensure products have been sold on at least one day
            $query->whereHas('transactionSellLines', function ($query) {
                $query->where('created_at', '<', now()->subYear());
            });

            // Exclude products checked within the last week
            $query->whereDoesntHave('randomCheckDetails', function ($query) {
                $query->where('created_at', '>=', now()->subWeek())->whereNull('deleted_at');
            });
            
    
            $category_products = $query->where('products.category_id', $category['category_id'])
                ->inRandomOrder()
                ->take($category['number_of_products'])
                ->select(
                    'c.name as category_name',
                    'products.id',
                    'products.name as product',
                    'products.sku',
                    'brands.name as brand',
                    'v.id as variation_id',
                    DB::raw('SUM(vld.qty_available) as current_stock')
                )
                ->groupBy('products.id')
                ->get();
    
            if ($category_products->isNotEmpty()) {
                $categories_products[$category['category_id']] = $category_products;
            }
        }
            $location = BusinessLocation::findOrFail($location_id);

        return view('random_check.results', compact('categories_products', 'location'));
    }
    
    
    public function checkConfirm(Request $request)
    {
        try {
            // Retrieve all the input data
            $products = $request->input('products', []);
            $location_id = $request->input('location_id');

            // Create a new random check record
            $lastCheck = RandomCheck::withTrashed()->latest()->first();
            $check_no = $lastCheck ? $lastCheck->id + 1 : 1;
            if ($lastCheck) {
                // Get details of the last random check
                $lastCheckDetails = RandomCheckDetail::where('random_check_id', $lastCheck->id)->get();
            } else {
                $lastCheckDetails = collect();
            }
            // Check for duplicates
            $duplicateCount = 0;
            // Check for duplicates
            foreach ($products as $id => $product) {
                $duplicate = $lastCheckDetails->firstWhere(function ($detail) use ($product, $id) {
                    return $detail->product_id == $id &&
                        $detail->location_id == ($product['location_id'] ) &&
                        $detail->variation_id == ($product['variation_id'] ) &&
                        $detail->category_name == $product['category_name'] &&
                        $detail->product_name == $product['product_name'] &&
                        $detail->sku == $product['sku'];
                });
                $location = BusinessLocation::findOrFail($product['location_id']);
                if ($duplicate) {
                    $duplicateCount++;
                }
            }
            // If all products are duplicates, return with an error
            // dd($duplicateCount .'==='. count($products));
            if ($duplicateCount === count($products)) {
                $randomCheckId = $lastCheck->id;
                $output = [
                    'success' => 0,
                    'msg' =>'Duplicate Entries found for all products.',
                ];
                return view('random_check.confirm', compact('products', 'randomCheckId', 'location'))->with('output', $output);
            }

            $business_id = request()->session()->get('user.business_id');
            $business = Business::where('id', $business_id)->first();
            $check_prefix = !empty($business->ref_no_prefixes['check']) ? $business->ref_no_prefixes['check'] : '';

            $randomCheck = RandomCheck::create([
                'checked_by' => auth()->id(),
                'check_no' => $check_prefix . '-' . $check_no,
                'comment' => '<i>Not Saved</i>'
            ]);

            $randomCheckId = $randomCheck->id;

            // Save or update random check details
            foreach ($products as $id => $product) {
                RandomCheckDetail::updateOrCreate(
                    [
                        'random_check_id' => $randomCheckId,
                        'product_id' => $id
                    ],
                    [
                        'location_id' => $product['location_id'] ?? null,
                        'variation_id' => $product['variation_id'] ?? null,
                        'category_name' => $product['category_name'],
                        'product_name' => $product['product_name'],
                        'sku' => $product['sku'],
                        'brand_name' => $product['brand_name'],
                        'current_stock' => $product['current_stock'] ?? 0,
                        'physical_count' => $product['physical_count'] ?? 0,
                        'comment' => $product['comment'] ?? null,
                        'updated_at' => null
                    ]
                );
                $location = BusinessLocation::findOrFail($product['location_id']);
            }

            // Redirect to the confirmation page with the products data
            return view('random_check.confirm', compact('products', 'randomCheckId', 'location'));
        } catch (\Exception $e) {
            \Log::error('Error in checkConfirm method: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to process data. Please try again.']);
        }
    }



    public function checkUpdate(Request $request)
    {
        if (!auth()->user()->can('stock_audit.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $products = $request->input('products', []);

            // Retrieve the random_check_id and any additional comments
            $randomCheckId = $request->input('random_check_id');
            $comment = $request->input('comment') ?? '<i>No comments</i>';

            // Update the random check record if necessary
            $randomCheck = RandomCheck::findOrFail($randomCheckId);
            $randomCheck->comment = $comment ?? null;
            $randomCheck->updated_at = null;
            $randomCheck->save();

            // Update or create random check details
            foreach ($products as $id => $product) {
                $randomCheckDetail = RandomCheckDetail::where('random_check_id', $randomCheckId)
                    ->where('product_id', $id)
                    ->first();

                if ($randomCheckDetail) {
                    // Update existing record
                    $randomCheckDetail->physical_count = $product['physical_count'] ?? 0;
                    $randomCheckDetail->comment = $product['comment'] ?? null;
                    $randomCheckDetail->updated_at = null;
                    $randomCheckDetail->save();
                }
            }

            // Redirect to the index or any desired route with a success message
            return redirect()->route('random.randomCheckIndex')
            ->with('success', 'Random check data saved successfully.');

        } catch (\Exception $e) {
            \Log::error('Error storing random check data: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to save random check data. Please try again.']);
        }
    }


    public function checkEdit($id)
    {
        if (!auth()->user()->can('stock_audit.update')) {
            abort(403, 'Unauthorized action.');
        }

        // Fetch the RandomCheck data by ID
        $randomCheck = RandomCheck::with('randomCheckDetails.product')->findOrFail($id);
        $categories = Category::all()->pluck('name', 'id');

        // Fetch the first randomCheckDetail associated with the RandomCheck model
        $randomCheckDetail = $randomCheck->randomCheckDetails->first();

        // Fetch the associated BusinessLocation model using the location_id from the randomCheckDetail
        $location = BusinessLocation::findOrFail($randomCheckDetail->location_id);

        // Return the edit view with data
        return view('random_check.edit', compact('randomCheck', 'categories', 'location'));
    }


    public function checkDetailUpdate(Request $request, $id)
    {
        if (!auth()->user()->can('stock_audit.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $details = $request->input('details', []);
            $comment = $request->input('comment');

            // Fetch the RandomCheck by ID
            $randomCheck = RandomCheck::findOrFail($id);
            
            // Update the random check details
            $randomCheckDetails = [];
            foreach ($details as $detailId => $detail) {
                // Fetch the RandomCheckDetail and store it in the array before updating
                $randomCheckDetail = RandomCheckDetail::where('random_check_id', $id)
                    ->where('id', $detailId)
                    ->first();
                
                if ($randomCheckDetail) {
                    // Store a copy of the original detail before updating
                    $randomCheckDetails[] = clone $randomCheckDetail;

                    // Update the detail
                    $randomCheckDetail->physical_count = $detail['physical_count'] ?? 0;
                    $randomCheckDetail->comment = $detail['comment'] ?? null;
                    $randomCheckDetail->save();
                }
            }

            // Update the overall comment
            $randomCheck->comment = $comment;
            $randomCheck->modified_by = auth()->id();
            $randomCheck->save();

            // Log the activity
            $this->productUtil->activityLog($randomCheck, 'updated', null, $randomCheckDetails);
            
            // Redirect to the index or any desired route with a success message
            return redirect()->route('random.randomCheckIndex')
            ->with('success', 'Random check details updated successfully.');

        } catch (\Exception $e) {
            \Session::flash('error', 'Failed to update.');
            \Log::error('Error updating random check details: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to update random check details. Please try again.']);
        }
    }

    public function checkShow($id)
    {
        if (!auth()->user()->can('stock_audit.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            // Fetch the RandomCheck record by ID
            $randomCheck = RandomCheck::with('randomCheckDetails.product.category', 'checkedBy', 'modifiedBy')->findOrFail($id);
            $activities = Activity::forSubject($randomCheck)
           ->with(['causer', 'subject'])
           ->get();

           // Fetch the first randomCheckDetail associated with the RandomCheck model
            $randomCheckDetail = $randomCheck->randomCheckDetails->first();

            // Fetch the associated BusinessLocation model using the location_id from the randomCheckDetail
            $location = BusinessLocation::findOrFail($randomCheckDetail->location_id);
            
            // Return the view with data
            return view('random_check.show', compact('randomCheck', 'activities', 'location'));
        } catch (\Exception $e) {
            \Log::error('Error fetching random check details: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch details.'], 500);
        }
    }

    public function checkReport()
    {
        if (!auth()->user()->can('stock_audit.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id, false);

        return view('random_check.check_report', compact('business_locations'));
    }

    public function generateReport(Request $request)
    {
        if (!auth()->user()->can('stock_audit.view')) {
            abort(403, 'Unauthorized action.');
        }
        $location_id = $request->input('location_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Adjust dates to include one day before start and one day after end
        $endDateAdjusted = \Carbon\Carbon::parse($endDate)->addDay()->format('Y-m-d');

        $randomCheckDetails = RandomCheckDetail::select(
            'random_check_details.category_name',
            'random_check_details.product_name',
            'random_check_details.sku',
            'random_check_details.brand_name',
            'random_check_details.physical_count',
            'variations.sell_price_inc_tax',
            'random_check_details.comment',
            'random_check_details.created_at',
            'random_check_details.location_id'
        )
        ->join('variations', 'random_check_details.variation_id', '=', 'variations.id')
        ->where('random_check_details.location_id', $location_id)
        ->whereBetween('random_check_details.created_at', [$startDate, $endDateAdjusted])
        ->orderBy('random_check_details.category_name')
        ->orderBy('random_check_details.product_name')
        ->get();

        $missingItems = $randomCheckDetails->where('physical_count', '<', 0);
        $surplusItems = $randomCheckDetails->where('physical_count', '>', 0);

        $totalMissingSellPrice = $missingItems->sum(function ($item) {
            return abs($item->physical_count) * $item->sell_price_inc_tax;
        });

        $totalSurplusSellPrice = $surplusItems->sum(function ($item) {
            return $item->physical_count * $item->sell_price_inc_tax;
        });

        $netResult = $totalSurplusSellPrice - $totalMissingSellPrice;
        $resultStatus = $netResult < 0 ? 'Loss' : 'Profit';

        $location = BusinessLocation::findOrFail($location_id);

         // Store missing and surplus items
        $reportId = null; // This will be set in finalizeReport

        // Temporarily store items in the session or pass to finalizeReport if necessary
        session(['report_data' => compact('missingItems', 'surplusItems', 'totalMissingSellPrice', 'totalSurplusSellPrice', 'netResult', 'resultStatus', 'startDate', 'endDate', 'location', 'reportId')]);

        $content = view('random_check.check_report_content', compact('missingItems', 'surplusItems', 'totalMissingSellPrice', 'totalSurplusSellPrice', 'netResult', 'resultStatus', 'startDate', 'endDate', 'location'))->render();

        return response()->json(['content' => $content]);
    }

    public function finalizeReport(Request $request)
    {
        if (!auth()->user()->can('stock_audit.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $data = $request->only(['location_id', 'start_date', 'end_date', 'net_result', 'comments']);

            // Get the latest report number (assuming incremental numbers)
            $lastReport = FinalizeReport::latest('report_no')->first();
            $report_no = $lastReport ? (int)str_replace('REPORT-', '', $lastReport->report_no) + 1 : 1;

            // Retrieve the number of checks covered
            $number_of_checks_covered = RandomCheck::whereHas('randomCheckDetails', function ($query) use ($data) {
                $query->where('location_id', $data['location_id'])
                    ->whereBetween('created_at', [$data['start_date'], $data['end_date']]);
            })->count();

            // Store the report in the database
            $report = FinalizeReport::create([
                'report_no' => 'REPORT-' . $report_no,
                'date' => now(),
                'location_id' => $data['location_id'],
                'date_range_covered' => $data['start_date'] . ' to ' . $data['end_date'],
                'number_of_checks_covered' => $number_of_checks_covered,
                'net_result' => $data['net_result'],
                'finalized_by' => auth()->id(),
                'comments' => $data['comments']
            ]);

            // Get the report ID
            $reportId = $report->id;

            // Retrieve stored report data from the session
            $reportData = session('report_data');
            if ($reportData) {
                $missingItems = $reportData['missingItems'];
                $surplusItems = $reportData['surplusItems'];

                // Store missing items
                foreach ($missingItems as $item) {
                    ReportItem::create([
                        'report_id' => $reportId,
                        'type' => 'missing',
                        'category_name' => $item->category_name,
                        'product_name' => $item->product_name,
                        'sku' => $item->sku ?? 'N/A',
                        'brand_name' => $item->brand_name ?? 'N/A',
                        'quantity' => abs($item->physical_count),
                        'subtotal' => abs($item->physical_count) * $item->sell_price_inc_tax,
                        'comment' => $item->comment,
                    ]);
                }

                // Store surplus items
                foreach ($surplusItems as $item) {
                    ReportItem::create([
                        'report_id' => $reportId,
                        'type' => 'surplus',
                        'category_name' => $item->category_name,
                        'product_name' => $item->product_name,
                        'sku' => $item->sku ?? 'N/A',
                        'brand_name' => $item->brand_name ?? 'N/A',
                        'quantity' => $item->physical_count,
                        'subtotal' => $item->physical_count * $item->sell_price_inc_tax,
                        'comment' => $item->comment,
                    ]);
                }

                // Clear the session data
                session()->forget('report_data');
            }


            // Delete checks and their details
            $this->deleteChecksByLocation($data['location_id'], $data['start_date'], $data['end_date']);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            \Log::error('Error finalizing report: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to finalize report. Please try again.'], 500);
        }
    }

    protected function deleteChecksByLocation($location_id, $start_date, $end_date)
    {
        DB::beginTransaction();
        try {
            // Adjust dates to include one day before start and one day after end
            $endDateAdjusted = \Carbon\Carbon::parse($end_date)->addDay()->format('Y-m-d');
            // Retrieve checks to delete
            $checks = RandomCheck::whereHas('randomCheckDetails', function ($query) use ($location_id, $start_date, $endDateAdjusted) {
                $query->where('location_id', $location_id)
                    ->whereBetween('created_at', [$start_date, $endDateAdjusted]);
            })->get();

            foreach ($checks as $check) {
                $check->randomCheckDetails()->delete(); // Delete associated details
                $check->delete(); // Delete the check itself
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting checks: ' . $e->getMessage());
            throw $e; // Rethrow the exception to handle it in the finalizeReport method
        }
    }


    public function viewReportItem($id)
    {
        $report = FinalizeReport::findOrFail($id);

        // Fetch missing and surplus items based on the report ID
        $missingItems = ReportItem::where('report_id', $id)->where('type', 'missing')->get();
        $surplusItems = ReportItem::where('report_id', $id)->where('type', 'surplus')->get();

        $totalMissingSellPrice = $missingItems->sum('subtotal');
        $totalSurplusSellPrice = $surplusItems->sum('subtotal');
        $netResult = $totalSurplusSellPrice - $totalMissingSellPrice;
        $resultStatus = $netResult < 0 ? 'Loss' : 'Profit';

        $location = BusinessLocation::findOrFail($report->location_id);

        return view('random_check.report_item', compact('missingItems', 'surplusItems', 'totalMissingSellPrice', 'totalSurplusSellPrice', 'netResult', 'resultStatus', 'report', 'location'));
    }


    
    public function checkDelete($id)
    {
        if (!auth()->user()->can('stock_audit.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();
            $randomCheck = RandomCheck::findOrFail($id);
            $randomCheck->delete();

            RandomCheckDetail::where('random_check_id', $id)->delete();
            DB::commit();

            return response()->json(['success' => 'Random check deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in checkDelete method: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete random check. Please try again.']);
        }
    }

    public function checkRestore($id)
    {
        if (!auth()->user()->can('stock_audit.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            // Restore the random check
            $randomCheck = RandomCheck::onlyTrashed()->findOrFail($id);
            $randomCheck->restore();

            // Restore the associated details
            RandomCheckDetail::onlyTrashed()->where('random_check_id', $id)->restore();

            DB::commit();

            return response()->json(['success' => 'Random check restored successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in restore method: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to restore random check. Please try again.']);
        }
    }


    public function checkPermanentDelete($id)
    {
        if (!auth()->user()->can('stock_audit.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            // Fetch the random check to be permanently deleted
            $randomCheck = RandomCheck::findOrFail($id);
            
            // Permanently delete the associated details
            RandomCheckDetail::where('random_check_id', $id)->forceDelete();
            
            // Permanently delete the random check
            $randomCheck->forceDelete();
            
            DB::commit();

            return response()->json(['success' => 'Random check permanently deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in checkPermanentDelete method: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to permanently delete random check. Please try again.']);
        }
    }



    public function printA4($id)
    {
        if (!auth()->user()->can('stock_audit.view')) {
            abort(403, 'Unauthorized action.');
        }
    
        try {
            $randomCheck = RandomCheck::with('randomCheckDetails.product.category')->findOrFail($id);

            // Fetch the first randomCheckDetail associated with the RandomCheck model
            $randomCheckDetail = $randomCheck->randomCheckDetails->first();

            // Fetch the associated BusinessLocation model using the location_id from the randomCheckDetail
            $location = BusinessLocation::findOrFail($randomCheckDetail->location_id);
            return view('random_check.print_a4', compact('randomCheck', 'location'));
        } catch (\Exception $e) {
            \Log::error('Error fetching random check details: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch details.'], 500);
        }
    }
    
    public function printPOS($id)
    {
        if (!auth()->user()->can('stock_audit.view')) {
            abort(403, 'Unauthorized action.');
        }
    
        try {
            $randomCheck = RandomCheck::with('randomCheckDetails.product.category')->findOrFail($id);

            // Fetch the first randomCheckDetail associated with the RandomCheck model
            $randomCheckDetail = $randomCheck->randomCheckDetails->first();

            // Fetch the associated BusinessLocation model using the location_id from the randomCheckDetail
            $location = BusinessLocation::findOrFail($randomCheckDetail->location_id);


            return view('random_check.print_pos', compact('randomCheck', 'location'));
        } catch (\Exception $e) {
            \Log::error('Error fetching random check details: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch details.'], 500);
        }
    }
}
