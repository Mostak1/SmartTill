@extends('layouts.app')
@section('title', __('lang_v1.add_purchase_requisition'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('lang_v1.add_purchase_requisition')</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        {!! Form::open([
            'url' => action([\App\Http\Controllers\PurchaseRequisitionController::class, 'store']),
            'method' => 'post',
            'id' => 'add_purchase_requisition_form',
        ]) !!}
        @component('components.widget', ['class' => 'box-solid'])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-map-marker"></i>
                        </span>
                        {!! Form::select('location_id', $business_locations, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('messages.please_select'),
                            'required',
                        ]) !!}
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('category_id[]', __('product.category') . ':') !!}
                    {!! Form::select('category_id[]', $categories, null, [
                        'class' => 'form-control select2',
                        'style' => 'width:100%',
                        'id' => 'psr_filter_category_id',
                        'multiple' => 'multiple',
                    ]) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('brand_id[]', __('product.brand') . ':') !!}
                    {!! Form::select('brand_id[]', $brands, null, [
                        'class' => 'form-control select2',
                        'style' => 'width:100%',
                        'id' => 'psr_filter_brand_id',
                        'multiple' => 'multiple',
                    ]) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('product_sr_date_filter', __('report.date_range') . ':') !!}
                    {!! Form::text('date_range', null, [
                        'placeholder' => __('lang_v1.select_a_date_range'),
                        'class' => 'form-control',
                        'id' => 'product_sr_date_filter',
                        'readonly',
                    ]) !!}
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12 text-right">
                    <br>
                    <button type="button" class="btn bg-yellow" id="show_pr_products"><i class="fas fa-search"></i>
                        @lang('lang_v1.show_products')</button>
                </div>
            </div>
        @endcomponent

        @component('components.widget', ['class' => 'box-solid'])
            <div class="tab-pane" id="psr_grouped_single_tab">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="product_sell_grouped_single_report_table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>@lang('sale.product')</th>
                                <th>@lang('product.sku')</th>
                                <th>Category</th>
                                <th>Brand</th>
                                <th>@lang('report.current_stock')</th>
                                <th>@lang('report.total_unit_sold')</th>
                                <th>@lang('sale.total')</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                <td colspan="5"><strong>@lang('sale.total'):</strong></td>
                                <td id="footer_total_grouped_sold"></td>
                                <td><span class="display_currency" id="footer_grouped_subtotal" data-currency_symbol="true"></span></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endcomponent

        <div class="row">
            <div class="col-sm-12 text-center">
                <button type="button" class="btn btn-primary btn-flat btn-lg" id="submit_pr_form">@lang('messages.save')</button>
            </div>
        </div>

        {!! Form::close() !!}
    </section>
@endsection

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            $('#show_pr_products').click(function() {
                product_sell_grouped_report_single.clear().draw();  // Clear the previous data
                product_sell_grouped_report_single.ajax.reload();
            });

            var product_sell_grouped_report_single = $('table#product_sell_grouped_single_report_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [
                    [1, 'desc']
                ],
                ajax: {
                    url: "{{ route('get-requisition-products') }}",
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

                            start = moment(start + " " + start_time, "YYYY-MM-DD" + " " +
                                moment_time_format).format('YYYY-MM-DD HH:mm');
                            end = moment(end + " " + end_time, "YYYY-MM-DD" + " " +
                                moment_time_format).format('YYYY-MM-DD HH:mm');
                        }
                        d.start_date = start;
                        d.end_date = end;

                        d.variation_id = $('#variation_id').val();
                        d.customer_id = $('select#customer_id').val();
                        d.location_id = $('select#location_id').val();
                        d.category_id = $('select#psr_filter_category_id').val();
                        d.brand_id = $('select#psr_filter_brand_id').val();
                        d.single = 1;
                        d.customer_group_id = $('#psr_customer_group_id').val();
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
                    $('#footer_grouped_subtotal').text(
                        sum_table_col($('#product_sell_grouped_single_report_table'), 'row_subtotal')
                    );
                    $('#footer_total_grouped_sold').html(
                        __sum_stock($('#product_sell_grouped_single_report_table'), 'sell_qty')
                    );
                    __currency_convert_recursively($('#product_sell_grouped_single_report_table'));
                },
            });
        });
    </script>
@endsection