@extends('layouts.app')
@section('title', __('Sell Details'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('Sell Details')
            <small>@lang('Reports')</small>
        </h1>
    </section>

    <!-- Main content -->
    <section class="content">
        @can('product.view')
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <div class="row">
                            <div class="col-md-8 text-left">
                                <p><strong>Location:</strong> {{ $location->name ?? '' }}</p>
                                <p><strong>Category:</strong>
                                    @foreach ($categories as $category)
                                        {{ $category->name }}{{ !$loop->last ? ',' : '' }}
                                    @endforeach
                                </p>
                            </div>
                            <div class="col-md-4 text-right">
                                <p><strong>Date Range:</strong> {{ $dateRange }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Custom Tabs -->
                    <div class="nav-tabs-custom">
                        <div class="tab-content">
                            <div class="tab-pane active" id="product_sell_tab">
                                <table style="width: 100%" class="table table-bordered table-striped ajax_view hide-footer"
                                    id="product_sell_table">
                                    <thead>
                                        <tr>
                                            <th>@lang('sale.product')</th>
                                            <th>Customer</th>
                                            <th>@lang('product.sku')</th>
                                            <th>Category</th>
                                            <th>Brand</th>
                                            <th>Date</th>
                                            <th>@lang('report.current_stock')</th>
                                            <th>@lang('report.total_unit_sold')</th>
                                            <th>@lang('sale.total')</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr class="bg-gray font-17 footer-total text-center">
                                            <td colspan="7"><strong>@lang('sale.total'):</strong></td>
                                            <td id="footer_total_today_sold"></td>
                                            <td><span class="display_currency" id="footer_today_subtotal"
                                                    data-currency_symbol="true"></span></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
    </section>
    <!-- /.content -->
@endsection

@section('javascript')
    <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            // Get URL parameters
            var urlParams = new URLSearchParams(window.location.search);
            var start_date = urlParams.get('start_date');
            var end_date = urlParams.get('end_date');
            var locationId = urlParams.get('location_id');
            var categoryIds = urlParams.get('category_id').split(',');

            // Initialize DataTable for product sell
            var product_sell_table = $('#product_sell_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [
                    [0, 'asc']
                ],
                ajax: {
                    url: '/reports/product-sell-report-with-sellreturn',
                    data: function(d) {
                        d.start_date = start_date;
                        d.end_date = end_date;
                        d.location_id = locationId;
                        d.category_id = categoryIds;
                    },
                },
                columns: [{
                        data: 'product_name',
                        name: 'p.name'
                    },
                    {
                        data: 'contact_name',
                        name: 'contact_name'
                    },
                    {
                        data: 'sub_sku',
                        name: 'v.sub_sku'
                    },
                    {
                        data: 'category_name',
                        name: 'cat.name'
                    },
                    {
                        data: 'brand_name',
                        name: 'b.name'
                    },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date'
                    },
                    {
                        data: 'current_stock',
                        name: 'current_stock',
                        orderable: false
                    },
                    {
                        data: 'total_qty_sold',
                        name: 'total_qty_sold',
                    },
                    {
                        data: 'subtotal',
                        name: 'subtotal',
                    },
                ],
                fnDrawCallback: function(oSettings) {
                    let api = this.api();

                    // Calculate the total quantity sold
                    let totalQtySold = api.column(7, {
                        page: 'current'
                    }).data().reduce(function(a, b) {
                        let numericValueB = parseFloat(b.replace(/[^\d.]/g, ''));
                        return parseFloat(a) + numericValueB;
                    }, 0);

                    // Calculate the total sold subtotal
                    let totalSubtotal = api.column(8, {
                        page: 'current'
                    }).data().reduce(function(a, b) {
                        let numericValueB = parseFloat(b.replace(/[^\d.]/g, ''));
                        return parseFloat(a) + numericValueB;
                    }, 0);

                    // Update the footer with the totals
                    $('#footer_today_subtotal').text(totalSubtotal.toFixed(2));

                    __currency_convert_recursively($('#product_sell_table'));
                },
            });
        });
    </script>
@endsection
