@extends('layouts.app')
@section('title', __('Sell and Return Details'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('Sell and Return Details')
        <small>@lang('Reports')</small>
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
@component('components.filters', ['title' => __('report.filters')])
<div class="row">
    <div class="col-md-12">
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('type[]', __('product.product_type') . ':') !!}
                {!! Form::select('type[]', ['single' => __('lang_v1.single'), 'variable' => __('lang_v1.variable'), 'combo' => __('lang_v1.combo')], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_type','multiple' => 'multiple']); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('category_id[]', __('product.category') . ':') !!}
                {!! Form::select('category_id[]', $categories, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_category_id', 'multiple' => 'multiple']); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('unit_id[]', __('product.unit') . ':') !!}
                {!! Form::select('unit_id[]', $units, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_unit_id', 'multiple' => 'multiple']); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('tax_id[]', __('product.tax') . ':') !!}
                {!! Form::select('tax_id[]', $taxes, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_tax_id', 'multiple' => 'multiple']); !!}
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('brand_id[]', __('product.brand') . ':') !!}
                {!! Form::select('brand_id[]', $brands, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_brand_id', 'multiple' => 'multiple']); !!}
            </div>
        </div>
        <div class="col-md-3" id="location_filter">
            <div class="form-group">
                {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('stock_status', __('Stock Status') . ':') !!}
                {!! Form::select('stock_status', ['in_stock' => __('In stock'), 'out_of_stock' => __('Out of stock')], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_stock_status', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('product_sr_date_filter', __('report.date_range') . ':') !!}
                {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'product_sr_date_filter', 'readonly']); !!}
            </div>
        </div>
    </div>
</div>
@endcomponent
@can('product.view')
    <div class="row">
        <div class="col-md-12">
           <!-- Custom Tabs -->
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#product_sell_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-clipboard-check"></i> Sell Details</a>
                    </li>
                    <li>
                        <a href="#product_return_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-undo-alt"></i> Return Products</a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane active" id="product_sell_tab">
                        @include('report.partials.sell_details')
                    </div>
                    <div class="tab-pane" id="product_return_tab">
                        @include('report.partials.return_details')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endcan
<input type="hidden" id="is_rack_enabled" value="{{$rack_enabled}}">

<div class="modal fade product_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

<div class="modal fade" id="view_product_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

<div class="modal fade" id="opening_stock_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

@if($is_woocommerce)
    @include('product.partials.toggle_woocommerce_sync_modal')
@endif
@include('product.partials.edit_product_location_modal')

</section>
<!-- /.content -->

@endsection

@section('javascript')
    <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
$(document).ready(function() {
    // Initialize daterangepicker
    $('#product_sr_date_filter').daterangepicker(dateRangeSettings, function(start, end) {
        $('#product_sr_date_filter').val(
            start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
        );
        product_sell_table.ajax.reload();
        sell_return_table.ajax.reload();
    });

    $('#product_sr_date_filter').on('cancel.daterangepicker', function(ev, picker) {
        $('#product_sr_date_filter').val('');
        product_sell_table.ajax.reload();
        sell_return_table.ajax.reload();
    });

    // DataTable initialization for product sell
    var product_sell_table = $('#product_sell_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[0, 'asc']],
        ajax: {
            url: '/reports/product-sell-grouped-report',
            data: function(d) {
                var start = '';
                var end = '';
                var start_time = $('#product_sr_start_time').val();
                var end_time = $('#product_sr_end_time').val();
                if ($('#product_sr_date_filter').val()) {
                    start = $('input#product_sr_date_filter')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    end = $('input#product_sr_date_filter')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');

                    start = moment(start + " " + start_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                    end = moment(end + " " + end_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                }
                d.start_date = start;
                d.end_date = end;

                d.variation_id = $('#variation_id').val();
                d.customer_id = $('select#customer_id').val();
                d.customer_group_id = $('#psr_customer_group_id').val();
                d.type = $('#product_list_filter_type').val();
                d.category_id = $('#product_list_filter_category_id').val();
                d.brand_id = $('#product_list_filter_brand_id').val();
                d.unit_id = $('#product_list_filter_unit_id').val();
                d.tax_id = $('#product_list_filter_tax_id').val();
                d.active_state = $('#active_state').val();
                d.location_id = $('#location_id').val();
                d.stock_status = $('#product_list_filter_stock_status').val();
            },
        },
        columns: [
            { data: 'product_name', name: 'p.name' },
            { data: 'sub_sku', name: 'v.sub_sku' },
            { data: 'category_name', name: 'cat.name' },
            { data: 'brand_name', name: 'b.name' },
            { data: 'current_stock', name: 'current_stock', searchable: false, orderable: false },
            { data: 'total_qty_sold', name: 'total_qty_sold', searchable: false },
            { data: 'subtotal', name: 'subtotal', searchable: false },
        ],
        fnDrawCallback: function(oSettings) {
            let api = this.api();

            // Calculate the total quantity sold
            let totalQtySold = api.column(5, { page: 'current' }).data().reduce(function(a, b) {
                let numericValueB = parseFloat(b.replace(/[^\d.]/g, ''));
                return parseFloat(a) + numericValueB;
            }, 0);

            // Calculate the total sold subtotal
            let totalSubtotal = api.column(6, { page: 'current' }).data().reduce(function(a, b) {
                let numericValueB = parseFloat(b.replace(/[^\d.]/g, ''));
                return parseFloat(a) + numericValueB;
            }, 0);

            // Update the footer with the totals
            $('#footer_today_subtotal').text(totalSubtotal.toFixed(2));

            __currency_convert_recursively($('#product_sell_table'));
        },
    });

    // Trigger DataTable reload on filter change
    $('#product_list_filter_category_id, #product_list_filter_brand_id, #product_list_filter_type, #product_list_filter_unit_id, #product_list_filter_tax_id, #active_state, #location_id, #product_list_filter_stock_status').change(function() {
        product_sell_table.draw();
    });

    // DataTable initialization for sell return
    var sell_return_table = $('#sell_return_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[0, 'asc']],
        ajax: {
            url: "/today-sell-return",
            data: function(d) {
                var start = '';
                var end = '';
                var start_time = $('#product_sr_start_time').val();
                var end_time = $('#product_sr_end_time').val();
                if ($('#product_sr_date_filter').val()) {
                    start = $('input#product_sr_date_filter')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    end = $('input#product_sr_date_filter')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');

                    start = moment(start + " " + start_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                    end = moment(end + " " + end_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                }
                d.start_date = start;
                d.end_date = end;

                d.variation_id = $('#variation_id').val();
                d.customer_id = $('select#customer_id').val();
                d.customer_group_id = $('#psr_customer_group_id').val();
                d.type = $('#product_list_filter_type').val();
                d.category_id = $('#product_list_filter_category_id').val();
                d.brand_id = $('#product_list_filter_brand_id').val();
                d.unit_id = $('#product_list_filter_unit_id').val();
                d.tax_id = $('#product_list_filter_tax_id').val();
                d.active_state = $('#active_state').val();
                d.location_id = $('#location_id').val();
                d.stock_status = $('#product_list_filter_stock_status').val();
            },
        },
        columnDefs: [{
            targets: [6, 7],
            orderable: false,
            searchable: false
        }],
        columns: [
            { data: 'product', name: 'product' },
            { data: 'sku', name: 'sku' },
            { data: 'category', name: 'category' },
            { data: 'brand', name: 'brand' },
            { data: 'parent_sale', name: 'T1.invoice_no' },
            { data: 'payment_status', name: 'payment_status' },
            { data: 'current_stock', name: 'current_stock' },
            { data: 'total_return_qty', name: 'total_return_qty' },
            { data: 'final_total', name: 'final_total' }
        ],
        fnDrawCallback: function(oSettings) {
            var total_sell = sum_table_col($('#sell_return_table'), 'final_total');
            $('#footer_sell_return_total').text(total_sell);

            $('#footer_payment_status_count_sr').html(__sum_status_html($('#sell_return_table'), 'payment-status-label'));

            var total_due = sum_table_col($('#sell_return_table'), 'payment_due');
            $('#footer_total_due_sr').text(total_due);

            __currency_convert_recursively($('#sell_return_table'));
        },
        createdRow: function(row, data, dataIndex) {
            $(row).find('td:eq(2)').attr('class', 'clickable_td');
        }
    });

    // Trigger DataTable reload on filter change
    $('#product_list_filter_category_id, #product_list_filter_brand_id, #product_list_filter_type, #product_list_filter_unit_id, #product_list_filter_tax_id, #active_state, #location_id, #product_list_filter_stock_status').change(function() {
        sell_return_table.draw();
    });

    // Additional event listeners and handlers

    $('table#product_table tbody').on('click', 'a.activate-product', function(e) {
        e.preventDefault();
        var href = $(this).attr('href');
        $.ajax({
            method: "get",
            url: href,
            dataType: "json",
            success: function(result) {
                if (result.success == true) {
                    toastr.success(result.msg);
                    product_table.ajax.reload();
                    product_sell_table.ajax.reload();
                    sell_return_table.ajax.reload();
                } else {
                    toastr.error(result.msg);
                }
            }
        });
    });

    $(document).on('change', '#product_list_filter_type, #product_list_filter_category_id, #product_list_filter_brand_id, #product_list_filter_unit_id, #product_list_filter_tax_id, #product_list_filter_stock_status, #location_id, #active_state, #repair_model_id',
        function() {
            if ($("#product_list_tab").hasClass('active')) {
                product_table.ajax.reload();
            }
            if ($("#product_stock_report").hasClass('active')) {
                stock_report_table.ajax.reload();
            }
            if ($("#product_sell_tab").hasClass('active')) {
                product_sell_table.ajax.reload();
            }
            if ($("#product_return_tab").hasClass('active')) {
                sell_return_table.ajax.reload();
            }
        });

    $('#product_location').select2({ dropdownParent: $('#product_location').closest('.modal') });

    @if($is_woocommerce)
        $(document).on('click', '.toggle_woocomerce_sync', function(e) {
            e.preventDefault();
            var selected_rows = getSelectedRows();
            if (selected_rows.length > 0) {
                $('#woocommerce_sync_modal').modal('show');
                $("input#woocommerce_products_sync").val(selected_rows);
            } else {
                $('input#selected_products').val('');
                swal('@lang("lang_v1.no_row_selected")');
            }
        });

        $(document).on('submit', 'form#toggle_woocommerce_sync_form', function(e) {
            e.preventDefault();
            var url = $('form#toggle_woocommerce_sync_form').attr('action');
            var method = $('form#toggle_woocommerce_sync_form').attr('method');
            var data = $('form#toggle_woocommerce_sync_form').serialize();
            var ladda = Ladda.create(document.querySelector('.ladda-button'));
            ladda.start();
            $.ajax({
                method: method,
                dataType: "json",
                url: url,
                data: data,
                success: function(result) {
                    ladda.stop();
                    if (result.success) {
                        $("input#woocommerce_products_sync").val('');
                        $('#woocommerce_sync_modal').modal('hide');
                        toastr.success(result.msg);
                        product_table.ajax.reload();
                        product_sell_table.ajax.reload();
                        sell_return_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        });
    @endif
});

$(document).on('click', '.update_product_location', function(e) {
    e.preventDefault();
    var selected_rows = getSelectedRows();

    if (selected_rows.length > 0) {
        $('input#selected_products').val(selected_rows);
        var type = $(this).data('type');
        var modal = $('#edit_product_location_modal');
        if (type == 'add') {
            modal.find('.remove_from_location_title').addClass('hide');
            modal.find('.add_to_location_title').removeClass('hide');
        } else if (type == 'remove') {
            modal.find('.add_to_location_title').addClass('hide');
            modal.find('.remove_from_location_title').removeClass('hide');
        }

        modal.modal('show');
        modal.find('#product_location').select2({ dropdownParent: modal });
        modal.find('#product_location').val('').change();
        modal.find('#update_type').val(type);
        modal.find('#products_to_update_location').val(selected_rows);
    } else {
        $('input#selected_products').val('');
        swal('@lang("lang_v1.no_row_selected")');
    }
});
    </script>
@endsection