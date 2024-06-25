@extends('layouts.app')
@section('title', 'Selling Price Group')
@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <a href="{{action([\App\Http\Controllers\SellingPriceGroupController::class, 'index'])}}"><h3><b style="font-size: 30px;">&#8592;  </b>Selling Price Group: {{ $sellingPriceGroup->name }}</h3></a>
    </section>
    <!-- Main content -->
    <section class="content">
        {!! Form::open([
            'url' => action([\App\Http\Controllers\ProductController::class, 'saveSellingPricesMany']),
            'method' => 'post',
            'id' => 'stock_adjustment_form',
        ]) !!}
        {{-- <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">{{ __('stock_adjustment.search_products') }}</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('location_id', __('purchase.business_location') . ':*') !!}
                            {!! Form::select('location_id', $business_locations, null, [
                                'class' => 'form-control select2',
                                'placeholder' => __('messages.please_select'),
                                'required',
                            ]) !!}
                            {!! Form::hidden('selling_price_group_id', $sellingPriceGroup->id,['id'=>'selling_price_group_id'])!!}
                        </div>
                    </div>
                    <div class="col-sm-8 col-sm-offset-2">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-search"></i>
                                </span>
                                {!! Form::text('search_product', null, [
                                    'class' => 'form-control',
                                    'id' => 'search_product_for_srock_adjustment',
                                    'placeholder' => __('stock_adjustment.search_product'),
                                    'disabled',
                                ]) !!}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-10 col-sm-offset-1">
                        <input type="hidden" id="product_row_index" value="0">
                        <input type="hidden" id="total_amount" name="final_total" value="0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-condensed text-center"
                                id="stock_adjustment_product_table">
                                <thead>
                                    <tr>
                                        <th>Product Name</th>
                                        <th>@lang('lang_v1.default_selling_price_inc_tax')</th>
                                        <th>{{ $sellingPriceGroup->name }}
                                            @show_tooltip(('lang_v1.price_group_price_type_tooltip'))</th>
                                        <th style="width: 300px" >Final Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12 text-center">
                        <button type="submit" class="btn btn-primary btn-big">@lang('messages.submit')</button>
                    </div>
                </div>
            </div>
        </div> <!--box end--> --}}
        {!! Form::close() !!}
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">@lang('lang_v1.selling_price_group') Product List</h3>
                 <a href="{{action('App\Http\Controllers\SellingPriceGroupController@updatePriceGroup',$sellingPriceGroup->id)}}" class="btn btn-xs btn-primary pull-right"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</a>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table id="selling_price_group_products_table"
                        class="table table-condensed table-bordered table-th-green text-center table-striped">
                        <thead>
                            <tr>
                                <th>@lang('sale.product')</th>
                                <th>@lang('product.sku')</th>
                                <th>Default Price</th>
                                <th>Discount</th>
                                <th>{{ $sellingPriceGroup->name }} Price</th>
                                <th>Profit Percentage</th>
                                <th><i class="fa fa-trash"></i></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </section>
@stop
@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            $('#selling_price_group_products_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ action('App\Http\Controllers\SellingPriceGroupController@show', [$sellingPriceGroup->id]) }}',
                columns: [{
                        data: 'product',
                        name: 'product'
                    },
                    {
                        data: 'sku',
                        name: 'sku'
                    },
                    {
                        data: 'selling_price',
                        name: 'selling_price'
                    },
                    {
                        data: 'price_group',
                        name: 'price_group'
                    },
                    {
                        data: 'price_group_price',
                        name: 'price_group_price'
                    },
                    {
                        data: 'profit_per',
                        name: 'profit_per'
                    },
                    {
                        data: 'Remove',
                        name: 'Remove'
                    }
                ]
            });
            $(document).on('click', 'a.remove_group_item', function(e) {
                e.preventDefault();
                var url = $(this).attr('href');
                
                swal({
                    title: LANG.sure,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(willDelete => {
                    if (willDelete) {
                        $.ajax({
                            method: 'DELETE',
                            url: url,
                            dataType: 'json',
                            success: function(result) {
                                if (result.success == true) {
                                    toastr.success(result.msg);
                                    // Reload the datatable
                                    $('#selling_price_group_products_table').DataTable().ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            },
                            error: function(xhr) {
                                toastr.error(xhr.responseText);
                            }
                        });
                    }
                });
            });
            // Enable product search when location is selected
            $('select#location_id').change(function() {
                if ($(this).val()) {
                    $('#search_product_for_srock_adjustment').removeAttr('disabled');
                } else {
                    $('#search_product_for_srock_adjustment').attr('disabled', 'disabled');
                }
                $('table#stock_adjustment_product_table tbody').html('');
                $('#product_row_index').val(0);
            });

            // Product search autocomplete
            if ($('#search_product_for_srock_adjustment').length > 0) {
                $('#search_product_for_srock_adjustment').autocomplete({
                    source: function(request, response) {
                        $.getJSON('/products/list', {
                            location_id: $('#location_id').val(),
                            term: request.term
                        }, response);
                    },
                    minLength: 2,
                    select: function(event, ui) {
                        if (ui.item.qty_available > 0) {
                            $(this).val(null);
                            stock_adjustment_product_row(ui.item.variation_id);
                        } else {
                            // alert(LANG.out_of_stock);
                            $(this).val(null);
                            stock_adjustment_product_row(ui.item.variation_id);
                        }
                    }
                }).autocomplete('instance')._renderItem = function(ul, item) {
                    if (item.qty_available <= 0) {
                        var string = '<div>' + item.name + ' (' + item.sub_sku + ') ';
                        if (item.brand_id) {
                            string += '<br>Brand: ' + item.brand.name + '</div>';
                        }
                        return $('<li>').append(string).appendTo(ul);
                    } else {
                        var string = '<div>' + item.name + ' (' + item.sub_sku + ') ';
                        if (item.brand_id) {
                            string += '<br>Brand: ' + item.brand.name + '</div>';
                        }
                        return $('<li>').append(string).appendTo(ul);
                    }
                };
            }

            // Adding product row
            function stock_adjustment_product_row(variation_id) {
                var row_index = parseInt($('#product_row_index').val());
                var location_id = $('select#location_id').val();
                var price_group_id = $('#selling_price_group_id').val();
                $.ajax({
                    method: 'POST',
                    url: '/get-product-group-row',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        row_index: row_index,
                        variation_id: variation_id,
                        location_id: location_id,
                        price_group_id: price_group_id,
                    },
                    dataType: 'html',
                    success: function(result) {
                        $('table#stock_adjustment_product_table tbody').append(result);
                        update_table_total();
                        $('#product_row_index').val(row_index + 1);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error: ' + error);
                    }
                });
            }
            // Attach change event to quantity and unit price inputs
            $(document).on('change', 'input.product_quantity, input.product_unit_price', function() {
                update_table_row($(this).closest('tr'));
            });

            // Remove product row
            $(document).on('click', '.remove_product_row', function() {
                swal({
                    title: LANG.sure,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        $(this).closest('tr').remove();
                        update_table_total();
                    }
                });
            });

        
            function updateFinalPrice(context) {
                // Retrieve the base price from the data attribute within the context
                var basePrice = parseFloat($(context).find('#base-price').data('base-price'));
                var finalPrice = basePrice;

                $(context).find('.group-price-input').each(function() {
                    var variationId = $(this).data('variation-id');
                    var priceGroupId = $(this).data('price-group-id');
                    var priceType = $(context).find('select[data-variation-id="' + variationId + '"][data-price-group-id="' + priceGroupId + '"]').val();
                    var priceValue = parseFloat($(this).val());

                    // Handle empty or invalid input values
                    if (isNaN(priceValue) || priceValue === null) {
                        priceValue = 0;
                    }

                    if (priceType === 'fixed') {
                        finalPrice = basePrice - priceValue;
                    } else if (priceType === 'percentage') {
                        finalPrice = basePrice - (basePrice * priceValue / 100);
                    }
                });

                $(context).find('.final-price').text(finalPrice.toFixed(2));
            }

            $(document).on('input', '.group-price-input', function() {
                var context = $(this).closest('tr'); // Get the row context
                updateFinalPrice(context);
            });

            $(document).on('change', '.group-price-type', function() {
                var context = $(this).closest('tr'); // Get the row context
                updateFinalPrice(context);
            });
        });

    </script>
@endsection