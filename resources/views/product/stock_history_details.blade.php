@php
    $common_settings = session()->get('business.common_settings');
@endphp
<div class="row">
    <div class="col-md-12">
        <h4>{{ $stock_details['variation'] }}</h4>
    </div>
    <div class="col-md-4 col-xs-4">
        <strong>@lang('lang_v1.quantities_in')</strong>
        <table class="table table-condensed">
            <tr>
                <th>@lang('report.total_purchase')</th>
                <td>
                    <span class="display_currency" data-is_quantity="true">{{ $stock_details['total_purchase'] }}</span>
                    {{ $stock_details['unit'] }}
                </td>
            </tr>
            <tr>
                <th>@lang('report.total_stock_adjustment')(Surplus)</th>
                <td>
                    <span class="display_currency"
                        data-is_quantity="true">{{$stock_details['total_adjusted_surplus'] }}</span>
                    {{ $stock_details['unit'] }}
                </td>
            </tr>
            <tr>
                <th>@lang('lang_v1.opening_stock')</th>
                <td>
                    <span class="display_currency"
                        data-is_quantity="true">{{ $stock_details['total_opening_stock'] }}</span>
                    {{ $stock_details['unit'] }}
                </td>
            </tr>
            <tr>
                <th>@lang('lang_v1.total_sell_return')</th>
                <td>
                    <span class="display_currency"
                        data-is_quantity="true">{{ $stock_details['total_sell_return'] }}</span>
                    {{ $stock_details['unit'] }}
                </td>
            </tr>
            <tr>
                <th>@lang('lang_v1.stock_transfers') (@lang('lang_v1.in'))</th>
                <td>
                    <span class="display_currency"
                        data-is_quantity="true">{{ $stock_details['total_purchase_transfer'] }}</span>
                    {{ $stock_details['unit'] }}
                </td>
            </tr>
        </table>
    </div>
    <div class="col-md-4 col-xs-4">
        <strong>@lang('lang_v1.quantities_out')</strong>
        <table class="table table-condensed">
            <tr>
                <th>@lang('lang_v1.total_sold')</th>
                <td>
                    <span class="display_currency" data-is_quantity="true">{{ $stock_details['total_sold'] }}</span>
                    {{ $stock_details['unit'] }}
                </td>
            </tr>
            <tr>
                <th>@lang('report.total_stock_adjustment')(Damage)</th>
                <td>
                    <span class="display_currency"
                        data-is_quantity="true">{{ $stock_details['total_adjusted'] }}</span>
                    {{ $stock_details['unit'] }}
                </td>
            </tr>
            <tr>
                <th>@lang('lang_v1.total_purchase_return')</th>
                <td>
                    <span class="display_currency"
                        data-is_quantity="true">{{ $stock_details['total_purchase_return'] }}</span>
                    {{ $stock_details['unit'] }}
                </td>
            </tr>

            <tr>
                <th>@lang('lang_v1.stock_transfers') (@lang('lang_v1.out'))</th>
                <td>
                    <span class="display_currency"
                        data-is_quantity="true">{{ $stock_details['total_sell_transfer'] }}</span>
                    {{ $stock_details['unit'] }}
                </td>
            </tr>
        </table>
    </div>

    <div class="col-md-4 col-xs-4">
        <strong>Current Stock</strong>
        <table class="table table-condensed">
            @php
            $total_quantity = 0;
                foreach ($product_locations as $item) {
                    $total_quantity+=$item->stock;
                }
            @endphp
           
            @foreach ($product_locations as $item)
                
            <tr>
                <th>{{$item->location_name}}</th>
                <td>
                    <span class="display_currency" data-is_quantity="true">{{ $item->stock }}</span>
                    {{ $stock_details['unit'] }}
                </td>
            </tr>
            @endforeach
            <tr>
                <th>Total Stock</th>
                <td>
                    <span class="display_currency" data-is_quantity="true">{{ $total_quantity }}</span>
                    {{ $stock_details['unit'] }}
                </td>
            </tr>
        </table>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <hr>
        <table class="table table-slim" id="stock_history_table">
            <thead>
                <tr>
                    <th>@lang('lang_v1.type')</th>
                    <th>@lang('lang_v1.quantity_change')</th>
                    @if (!empty($common_settings['enable_secondary_unit']))
                        <th>@lang('lang_v1.quantity_change') (@lang('lang_v1.secondary_unit'))</th>
                    @endif
                    <th>@lang('lang_v1.new_quantity')</th>
                    @if (!empty($common_settings['enable_secondary_unit']))
                        <th>@lang('lang_v1.new_quantity') (@lang('lang_v1.secondary_unit'))</th>
                    @endif
                    <th>@lang('lang_v1.date')</th>
                    <th>@lang('purchase.ref_no')</th>
                    <th>@lang('lang_v1.customer_supplier_info')</th>
                    <th>Created By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stock_history as $history)
                    <tr>
                        <td>{{ $history['type_label'] }}</td>
                        @if ($history['quantity_change'] > 0)
                            <td class="text-success"> +<span class="display_currency"
                                    data-is_quantity="true">{{ $history['quantity_change'] }}</span>
                            </td>
                        @else
                            <td class="text-danger"><span class="display_currency text-danger"
                                    data-is_quantity="true">{{ $history['quantity_change'] }}</span>
                            </td>
                        @endif

                        @if (!empty($common_settings['enable_secondary_unit']))
                            @if ($history['quantity_change'] > 0)
                                <td class="text-success">
                                    @if (!empty($history['purchase_secondary_unit_quantity']))
                                        +<span class="display_currency"
                                            data-is_quantity="true">{{ $history['purchase_secondary_unit_quantity'] }}</span>
                                        {{ $stock_details['second_unit'] }}
                                    @endif
                                </td>
                            @else
                                <td class="text-danger">
                                    @if (!empty($history['sell_secondary_unit_quantity']))
                                        -<span class="display_currency"
                                            data-is_quantity="true">{{ $history['sell_secondary_unit_quantity'] }}</span>
                                        {{ $stock_details['second_unit'] }}
                                    @endif
                                </td>
                            @endif
                        @endif
                        <td>
                            <span class="display_currency" data-is_quantity="true">{{ $history['stock'] }}</span>
                        </td>
                        @if (!empty($common_settings['enable_secondary_unit']))
                            <td>
                                @if (!empty($stock_details['second_unit']))
                                    <span class="display_currency"
                                        data-is_quantity="true">{{ $history['stock_in_second_unit'] }}</span>
                                    {{ $stock_details['second_unit'] }}
                                @endif
                            </td>
                        @endif
                        <td>{{ @format_datetime($history['date']) }}</td>
                        <td>
                            @if ($history['type_label'] == 'Sell')
                                <a href="#"
                                    data-href="{{ action([\App\Http\Controllers\SellController::class, 'show'], $history['sele_id']) }}"
                                    class="btn-modal" data-container=".view_modal">{{ $history['ref_no'] }}</a>
                            @elseif ($history['type_label'] == 'Purchase' || $history['type_label'] == 'Surplus')
                                <a href="#"
                                    data-href="{{ action([\App\Http\Controllers\PurchaseController::class, 'show'], $history['sele_id']) }}"
                                    class="btn-modal" data-container=".view_modal">{{ $history['ref_no'] }}</a>
                            @elseif ($history['type_label'] == 'Manufactured')
                                <a href="#"
                                    data-href="{{ action([\Modules\Manufacturing\Http\Controllers\ProductionController::class, 'show'], $history['sele_id']) }}"
                                    class="btn-modal" data-container=".view_modal"
                                    data-target="#recipe_modal">{{ $history['ref_no'] }}</a>
                            @elseif ($history['type_label'] == 'Ingredient')
                                <a href="#"
                                    data-href="{{ action([\Modules\Manufacturing\Http\Controllers\ProductionController::class, 'show'], $history['sele_id']) }}"
                                    class="btn-modal" data-container=".view_modal"
                                    data-target="#recipe_modal">{{ $history['ref_no']??'Manufactured' }}</a>
                            @elseif ($history['type_label'] == 'Stock Adjustment')
                                <a href="#"
                                    data-href="{{ action([\App\Http\Controllers\StockAdjustmentController::class, 'show'], $history['sele_id']) }}"
                                    class="btn-modal" data-container=".view_modal"
                                    data-target="#recipe_modal">{{ $history['ref_no'] }}</a>
                            @elseif ($history['type_label'] == 'Sell Return')
                                <a href="#" class="btn-modal" data-container=".view_modal"
                                    data-href="{{ action('\App\Http\Controllers\SellReturnController@show', $history['sele_id']) }}">{{ $history['ref_no'] }}</a>
                            @elseif ($history['type_label'] == 'Stock Transfers (Out)' || $history['type_label'] == 'Stock Transfers (In)')
                                <a href="#"
                                    data-href="{{ action([\App\Http\Controllers\StockTransferController::class, 'show'], $history['sele_id']) }}"
                                    class="btn-modal" data-container=".view_modal"
                                    data-target="#recipe_modal">{{ $history['ref_no'] }}</a>
                            @else
                                {{ $history['ref_no'] }}
                            @endif
                            @if (!empty($history['additional_notes']))
                                @if (!empty($history['ref_no']))
                                    <br>
                                @endif
                                {{ $history['additional_notes'] }}
                            @endif
                        </td>
                        <td>
                            {{ $history['contact_name'] ?? '--' }}
                            @if (!empty($history['supplier_business_name']))
                                - {{ $history['supplier_business_name'] }}
                            @endif
                        </td>
                        <td>
                            @if (!empty($history['created_by']))
                                {{ $history['created_by'] }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">
                            @lang('lang_v1.no_stock_history_found')
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
