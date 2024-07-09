@extends('layouts.app')
@section('title', __('Return Products'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('Return Products')
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
                                {{-- <p><strong>Location:</strong> {{ $location->name }}</p> --}}
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
                            <div class="tab-pane active" id="product_return_tab">
                                <table style="width: 100%" class="table table-bordered table-striped ajax_view"
                                    id="sell_return_table">
                                    <thead>
                                        <tr>
                                            <th>@lang('sale.product')</th>
                                            <th>@lang('product.sku')</th>
                                            <th>Category</th>
                                            <th>Brand</th>
                                            <th>Date</th>
                                            <th>@lang('lang_v1.parent_sale')</th>
                                            <th>@lang('purchase.payment_status')</th>
                                            <th>Current stock</th>
                                            <th>Return Qty</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr class="bg-gray font-17 text-center footer-total">
                                            <td colspan="6"><strong>@lang('sale.total'):</strong></td>
                                            <td id="footer_payment_status_count_sr"></td>
                                            <td colspan="2"></td>
                                            <td><span class="display_currency" id="footer_sell_return_total"
                                                    data-currency_symbol ="true"></span></td>
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

            // DataTable initialization for sell return
            var sell_return_table = $('#sell_return_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [
                    [0, 'asc']
                ],
                ajax: {
                    url: "/today-sell-return",
                    data: function(d) {
                            d.start_date = start_date;
                            d.end_date = end_date;
                        d.location_id = locationId;
                    },
                },
                columnDefs: [{
                    targets: [6, 7],
                    orderable: false,
                    searchable: false
                }],
                columns: [{
                        data: 'product',
                        name: 'product'
                    },
                    {
                        data: 'sku',
                        name: 'sku'
                    },
                    {
                        data: 'category',
                        name: 'category'
                    },
                    {
                        data: 'brand',
                        name: 'brand'
                    },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date'
                    },
                    {
                        data: 'parent_sale',
                        name: 'T1.invoice_no'
                    },
                    {
                        data: 'payment_status',
                        name: 'payment_status'
                    },
                    {
                        data: 'current_stock',
                        name: 'current_stock'
                    },
                    {
                        data: 'total_return_qty',
                        name: 'total_return_qty'
                    },
                    {
                        data: 'final_total',
                        name: 'final_total'
                    }
                ],
                fnDrawCallback: function(oSettings) {
                    var total_sell = sum_table_col($('#sell_return_table'), 'final_total');
                    $('#footer_sell_return_total').text(total_sell);

                    $('#footer_payment_status_count_sr').html(__sum_status_html($('#sell_return_table'),
                        'payment-status-label'));

                    var total_due = sum_table_col($('#sell_return_table'), 'payment_due');
                    $('#footer_total_due_sr').text(total_due);

                    __currency_convert_recursively($('#sell_return_table'));
                },
                createdRow: function(row, data, dataIndex) {
                    $(row).find('td:eq(2)').attr('class', 'clickable_td');
                }
            });

        });
    </script>
@endsection
