<!-- resources/views/purchase_requisition/order.blade.php -->
@extends('layouts.app')

@section('title', __('Purchase Requisition Order'))

@section('css')
    <style>
        @media print {
            .print-font {
                font-size: 10px !important;
            }

            .print-exclude {
                display: none !important;
            }
        }

        .table th,
        .table td {
            vertical-align: middle !important;
        }

        .swal2-custom {
            width: 500px !important;
            height: 350px !important;
            font-size: 16px !important;
        }

        .swal2-custom .swal2-title {
            font-size: 20px !important;
        }

        .swal2-custom .swal2-content {
            font-size: 14px !important;
        }

        .swal2-custom .swal2-actions .swal2-styled {
            font-size: 14px !important;
        }
    </style>
@endsection

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('lang_v1.add_purchase_requisition')</h1>
    </section>

    <section class="content">
        @component('components.widget', [
            'title' => new \Illuminate\Support\HtmlString(
                'Generate Requisition Suggestions<i class="fa fa-info-circle text-info hover-q no-print" aria-hidden="true" data-container="body" data-toggle="popover" data-placement="auto bottom" data-content="helps optimize inventory management by calculating suggested orders based on recent sales trends. This feature provides actionable insights to streamline the procurement process and ensure adequate stock levels." data-html="true" data-trigger="hover" data-original-title="" title=""></i>'),
        ])
            <form id="orderForm" method="GET" action="{{ route('purchase_requisition.order') }}">
                <div class="row" style="align-items: center;">
                    <div class="col-md-2">
                        <div class="form-group">
                            {!! Form::label('business_location_id', __('purchase.business_location') . ':') !!}
                            {!! Form::select('business_location_id', $business_locations, null, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'id' => 'business_location_id',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('category_id[]', __('product.category') . ':') !!}
                            {!! Form::select('category_id[]', $categories, null, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'id' => 'category_id',
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
                                'id' => 'brand_id',
                                'multiple' => 'multiple',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            {!! Form::label('order_date_range', __('report.date_range') . ':', ['style' => 'margin-right: 10px;']) !!}
                            {!! Form::text('order_date_range', null, [
                                'placeholder' => __('lang_v1.select_a_date_range'),
                                'class' => 'form-control',
                                'readonly',
                                'id' => 'order_date_range',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <button type="button" id="generate_order" class="btn btn-primary" style="margin-top: 25px;">
                                @lang('Order')
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        @endcomponent

        @component('components.widget', ['title' => 'Add Products'])
            <div class="row">
                <div class="col-sm-8 col-sm-offset-2">
                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-search"></i>
                            </span>
                            {!! Form::text('search_product', null, [
                                'class' => 'form-control',
                                'id' => 'search_product_for_order',
                                'placeholder' => __('Search Product'),
                            ]) !!}
                        </div>
                    </div>
                </div>
            </div>
        @endcomponent

        @component('components.widget', ['title' => 'Products List'])
            <table class="table table-bordered table-th-green table-striped" id="order_product_table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>SKU</th>
                        <th>Brand</th>
                        <th>Category</th>
                        <th>Current Stock</th>
                        <th>Units Sold @show_tooltip('Total Units Sold within Last 30 Days')</th>
                        <th>Suggested Order</th>
                        <th>Suggested Supplier</th>
                        <th><i class="fa fa-trash"></i></th>
                    </tr>
                </thead>
                <tbody id="orderContent">
                    {{-- Order content will be loaded here via AJAX --}}
                </tbody>
            </table>

            <button type="button" id="finalize-button" class="btn btn-primary print-exclude btn-finalize"
                style="display: block; width: 160px; height: 50px; margin: 0 auto; margin-top:30px; font-size: 18px;">@lang('Finalize Order')</button>
        @endcomponent
    </section>
@endsection

@section('javascript')
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/purchase.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
    <script>
        $(document).ready(function() {
            var dateRangeSettings = {
                ranges: {
                    'Next 30 Days': [moment(), moment().add(30, 'days')],
                    'Next 7 Days': [moment(), moment().add(7, 'days')],
                    'Next 15 Days': [moment(), moment().add(15, 'days')],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Next Month': [moment().add(1, 'months').startOf('month'), moment().add(1, 'months').endOf(
                        'month')]
                },
                startDate: moment(),
                endDate: moment().add(30, 'days'),
                locale: {
                    cancelLabel: LANG.clear,
                    applyLabel: LANG.apply,
                    customRangeLabel: LANG.custom_range,
                    format: moment_date_format,
                    toLabel: '-',
                },
                minDate: moment().startOf('day'),
                maxDate: moment().add(1, 'year')
            };

            $('#order_date_range').daterangepicker(dateRangeSettings, function(start, end) {
                $('#order_date_range').val(start.format(moment_date_format) + ' - ' + end.format(
                    moment_date_format));
            });

            $('#order_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#order_date_range').val('');
            });

            $('#generate_order').click(function(e) {
                e.preventDefault();

                // Check if there are any products in the order table
                if ($('#order_product_table tbody tr').length > 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'This will erase all items currently added.',
                        text: 'Are you sure?',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, proceed',
                        cancelButtonText: 'Cancel',
                        customClass: {
                            popup: 'swal2-custom'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            generateOrder();
                        }
                    });
                } else {
                    generateOrder();
                }
            });

            function generateOrder() {
                var start = $('#order_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                var end = $('#order_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                var business_location_id = $('#business_location_id').val();
                var category_ids = $('#category_id').val();
                var brand_ids = $('#brand_id').val();

                $.ajax({
                    url: '{{ route('purchase_requisition.getProductRequisitionDetails') }}',
                    type: 'GET',
                    data: {
                        start_date: start,
                        end_date: end,
                        business_location_id: business_location_id,
                        category_id: category_ids,
                        brand_id: brand_ids
                    },
                    success: function(response) {
                        $('#orderContent').html(response.content);
                    },
                    error: function(xhr) {
                        console.error('Error occurred:', xhr.responseText);
                    }
                });
            }

            $(document).on('click', '.btn-remove-row', function() {
                $(this).closest('tr').remove();
            });

            // Product search autocomplete
            $('#search_product_for_order').autocomplete({
                source: function(request, response) {
                    $.getJSON('/products/list', {
                        term: request.term
                    }, response);
                },
                minLength: 2,
                select: function(event, ui) {
                    if (ui.item.qty_available > 0) {
                        $(this).val(null);
                        addProductToOrder(ui.item);
                    } else {
                        $(this).val(null);
                        addProductToOrder(ui.item);
                    }
                }
            }).autocomplete('instance')._renderItem = function(ul, item) {
                var string = '<div>' + item.name + ' (' + item.sub_sku + ') ';
                if (item.brand_id) {
                    string += '<br>Brand: ' + item.brand.name + '</div>';
                }
                return $('<li>').append(string).appendTo(ul);
            };

            function addProductToOrder(product) {
                var isProductAlreadyAdded = false;
                $('#order_product_table tbody tr').each(function() {
                    var existingSKU = $(this).find('td:nth-child(2)').text().trim();
                    if (existingSKU === product.sub_sku) {
                        isProductAlreadyAdded = true;
                        return false;
                    }
                });

                if (isProductAlreadyAdded) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Product already added!',
                        text: 'The selected product is already in the products List.',
                        customClass: {
                            popup: 'swal2-custom'
                        }
                    });
                } else {
                    $.ajax({
                        url: '{{ route('purchase_requisition.getProductEntryRow') }}',
                        type: 'GET',
                        data: {
                            term: product.name,
                            location_id: $('#business_location_id').val()
                        },
                        success: function(response) {
                            $('#orderContent').append(response.content);
                        },
                        error: function(xhr) {
                            console.error('Error occurred:', xhr.responseText);
                        }
                    });
                }
            }


            $(document).on('click', '#finalize-button', function() {
                if ($('#order_product_table tbody tr').length > 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Finalize your products list.',
                        text: 'Are you sure?',
                        showCancelButton: true,
                        confirmButtonText: 'OK',
                        cancelButtonText: 'Cancel',
                        customClass: {
                            popup: 'swal2-custom'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Collect data
                            var purchases = [];
                            $('#order_product_table tbody tr').each(function() {
                                var row = $(this);
                                var purchase = {
                                    product_id: row.find('input[name="product_id"]').val(),
                                    variation_id: row.find('input[name="variation_id"]').val(),
                                    quantity: row.find('input[name="quantity"]').val(),
                                    supplier_id: row.find('select[name="supplier_id"]').val()
                                };
                                purchases.push(purchase);
                            });

                            var data = {
                                location_id: $('#business_location_id').val(),
                                delivery_date: $('#delivery_date').val(),
                                purchases: purchases
                            };

                            // Send data to store method
                            $.ajax({
                                url: '{{ route('purchase-requisition.store') }}', // Using the store route from the resource controller
                                type: 'POST',
                                data: data,
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                                        'content')
                                },
                                success: function(response) {
                                    if (response.success) {
                                        toastr.success('Success! ' + response.msg);
                                        window.location.href = response.url;
                                    } else {
                                        toastr.error('Error! ' + response.msg);
                                    }
                                },
                                error: function(xhr) {
                                    console.error('Error occurred:', xhr.responseText);
                                }
                            });
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No products to finalize!',
                        text: 'Please add products to the list before finalizing.',
                        customClass: {
                            popup: 'swal2-custom'
                        }
                    });
                }
            });


        });
    </script>
@endsection
