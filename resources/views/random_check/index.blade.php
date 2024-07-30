@extends('layouts.app')

@section('title', __('Product Random Check'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header no-print">
        <h1>@lang('Product Random Check')</h1>
    </section>

    <!-- Main content -->
    <section class="content no-print">
        @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('random_check_filter_location_id',  __('purchase.business_location') . ':') !!}
                    {!! Form::select('random_check_filter_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label for="random_check_filter_physical_count">Physical Count:</label>
                    <select id="random_check_filter_physical_count" class="form-control select2" multiple="multiple" style="width: 100%;">
                        <option value="surplus">Surplus</option>
                        <option value="match">Match</option>
                        <option value="missing">Missing</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="category_name">Category:</label>
                    <select id="category_name" class="form-control select2" multiple="multiple" style="width: 100%;">
                        @foreach ($categories as $key => $value)
                            <option value="{{ $value }}">{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('random_check_table_filter_date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('random_check_table_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
                </div>
            </div>

        @endcomponent

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs">
            <li class="{{ request()->is('products/random-check-index') ? 'active' : '' }}">
                <a href="{{ url('products/random-check-index') }}">
                    <i class="fa fa-random"></i> @lang('Random Checks')
                </a>
            </li>
            <li class="{{ request()->is('products/random-check-details') ? 'active' : '' }}">
                <a href="{{ url('products/random-check-details') }}">
                    <i class="fa fa-list"></i> @lang('Random Check Details')
                </a>
            </li>
            <li class="{{ request()->is('products/check-report') ? 'active' : '' }}">
                <a href="{{ url('products/check-report') }}">
                    <i class="fas fa-file-invoice"></i> @lang('Check Report')
                </a>
            </li>
        </ul>


        <div class="tab-content">
            <!-- Random Check Tab -->
            <div class="tab-pane {{ request()->is('products/random-check-index') ? 'active' : '' }}" id="random-check-index">
                @component('components.widget', ['title' => __('All Random Checks')])
                    @slot('tool')
                        <div class="box-tools">
                            <!-- Product Random Check Button -->
                            <button type="button" class="btn btn-block btn-primary btn-modal" 
                            data-href="{{ action([\App\Http\Controllers\ProductController::class, 'createRandomCheck']) }}" 
                            data-container=".random_check_modal">
                            <i class="fa fa-random"></i> @lang('Check')
                            </button>
                        </div>
                    @endslot

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="random_check_table">
                            <thead>
                                <tr>
                                    <th>Check No.</th>
                                    <th>Date</th>
                                    <th>Checked By</th>
                                    <th>Location</th>
                                    <th>No. of Products</th>
                                    <th>Physical Count Summary</th>
                                    <th>Comment</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- DataTable body will be populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                @endcomponent
            </div>

            <!-- Random Check Details Tab -->
            <div class="tab-pane {{ request()->is('products/random-check-details') ? 'active' : '' }}" id="random-check-details">
                @component('components.widget', ['title' => __('All Random Check Details')])
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="random_check_details_table">
                            <thead>
                                <tr>
                                    <th>Check No.</th>
                                    <th>Category</th>
                                    <th>Product Name</th>
                                    <th>SKU</th>
                                    <th>Brand</th>
                                    <th>Soft. Count</th>
                                    <th>Physical Count</th>
                                    <th>Comment</th>
                                    <th>Checked At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- DataTable body will be populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                @endcomponent
            </div>
        </div>

        <!-- Random Check Modals -->
        <div class="modal fade random_check_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
        <div class="modal fade random_check_edit_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
        <div class="modal fade view_random_check_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    </section>
    <!-- /.content -->

@endsection

@section('javascript')
    <script>
$(document).ready(function(){
    $('#random_check_table_filter_date_range').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#random_check_table_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            random_check_table.ajax.reload();
            random_check_details_table.ajax.reload();
        }
    );
    $('#random_check_table_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
        $('#random_check_table_filter_date_range').val('');
        random_check_table.ajax.reload();
        random_check_details_table.ajax.reload();
    });

    var random_check_table = $('#random_check_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/products/random-check-index',
            data: function (d) {
                if ($('#random_check_table_filter_date_range').val()) {
                    var start = $('#random_check_table_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    var end = $('#random_check_table_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    d.start_date = start;
                    d.end_date = end;
                }
                if ($('#random_check_filter_location_id').length) {
                    d.location_id = $('#random_check_filter_location_id').val();
                }
                if ($('#random_check_filter_physical_count').length) {
                    d.physical_count_filter = $('#random_check_filter_physical_count').val();
                }
            }
        },
        columns: [
            { data: 'check_no', name: 'check_no' },
            { data: 'created_at', name: 'created_at', searchable: false},
            { data: 'checked_by', name: 'checked_by' },
            { data: 'location_name', name: 'location_name' },
            { data: 'total_product_count', name: 'total_product_count', searchable: false },
            { data: 'total_physical_count', name: 'total_physical_count', searchable: false },
            { data: 'random_check_comment', name: 'random_check_comment' },
            { data: 'action', name: 'action', searchable: false }
        ],
        order: [[0, 'desc']], // Default sorting by date descending
    });

    var random_check_details_table = $('#random_check_details_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('products.randomCheckDetails') }}',
            data: function (d) {
                if ($('#random_check_table_filter_date_range').val()) {
                    var start = $('#random_check_table_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    var end = $('#random_check_table_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    d.start_date = start;
                    d.end_date = end;
                }
                if ($('#random_check_filter_location_id').length) {
                    d.location_id = $('#random_check_filter_location_id').val();
                }
                if ($('#random_check_filter_physical_count').length) {
                    d.physical_count_filter = $('#random_check_filter_physical_count').val();
                }
                if ($('#category_name').length) {
                    d.category_name = $('#category_name').val();
                }
            }
        },
        columns: [
            { data: 'check_no', name: 'check_no', searchable: false },
            { data: 'category_name', name: 'category_name' },
            { data: 'product_name', name: 'product_name' },
            { data: 'sku', name: 'sku', searchable: false },
            { data: 'brand_name', name: 'brand_name', searchable: false },
            { data: 'current_stock', name: 'current_stock', searchable: false },
            { data: 'physical_count', name: 'physical_count', searchable: false },
            { data: 'comment', name: 'comment', searchable: false },
            { data: 'created_at', name: 'created_at', searchable: false }
        ],
        columnDefs: [
            {
                targets: [8], // Indices of created_at and updated_at
                render: function (data, type, row) {
                    return type === 'display' ? moment(data).format('D MMM YYYY, h:mm A') : data;
                }
            }
        ]
    });


    $(document).on('change', '#random_check_filter_location_id, #random_check_filter_physical_count, #category_name', function () {
        random_check_table.ajax.reload();
        random_check_details_table.ajax.reload();
    });

    // Initialize Product Random Check Modal
    $(document).on('click', 'button.btn-modal', function () {
        var container = $(this).data('container');
        var href = $(this).data('href');
        $(container).load(href, function () {
            $(this).modal('show');
        });
    });

    $(document).on('click', '.edit-random-check', function (e) {
        e.preventDefault();
        var href = $(this).data('href');
        $('.random_check_edit_modal').load(href, function () {
            $(this).modal('show');
        });
    });

    $(document).on('click', '.view_random_check', function (e) {
        e.preventDefault();
        var href = $(this).data('href');
        $('.view_random_check_modal').load(href, function () {
            $(this).modal('show');
        });
    });

});
    </script>
@endsection