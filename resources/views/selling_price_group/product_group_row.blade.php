<!-- selling_price_group/product_group_row.blade.php -->
@foreach ($product->variations as $variation)
    <tr>
        @if ($product->type == 'variable')
            <td>{{ $variation->product_variation->name }} - {{ $variation->name }} ({{ $variation->sub_sku }})</td>
        @else
            <td>
                {{ $product->name }} ({{ $variation->sub_sku }}) 
                {!! Form::hidden('variation_id', $variation->id,['id'=>'variation_id'])!!}
            </td>
        @endif
        <td>
            <span class="display_currency" data-currency_symbol="true" id="base-price" data-base-price="{{ $variation->sell_price_inc_tax }}">
                {{ number_format($variation->sell_price_inc_tax, 2) }}
            </span>
        </td>
        @foreach ($price_groups as $price_group)
            <td style="width: 200px;">
                @php
                    $price_type = !empty($variation_prices[$variation->id][$price_group->id]['price_type'])
                        ? $variation_prices[$variation->id][$price_group->id]['price_type']
                        : 'fixed';
                    $name = 'group_prices[' . $variation->id . '][price_type]';

                    if ($price_type == 'percentage') {
                        $price_value = !empty($variation_prices[$variation->id][$price_group->id]['price'])
                            ? $variation_prices[$variation->id][$price_group->id]['price']
                            : 0;
                        $final_price = $variation->sell_price_inc_tax * (1 - ((100 - $price_value) / 100));
                    } else {
                        $price_value = !empty($variation_prices[$variation->id][$price_group->id]['price'])
                            ? $variation_prices[$variation->id][$price_group->id]['price']
                            : 0;
                        $final_price = $variation->sell_price_inc_tax - $price_value;
                    }
                @endphp
                @if ($price_type == 'percentage')
                {!! Form::text(
                    'group_prices[' . $variation->id . '][price]',
                    !empty($variation_prices[$variation->id][$price_group->id]['price'])
                        ? 100 - number_format($variation_prices[$variation->id][$price_group->id]['price'],2)
                        : 0,
                    ['class' => 'form-control input_number input-sm group-price-input', 'data-variation-id' => $variation->id, 'data-price-group-id' => $price_group->id],
                ) !!}
                @else
                {!! Form::text(
                    'group_prices[' . $variation->id . '][price]',
                    !empty($variation_prices[$variation->id][$price_group->id]['price'])
                        ? number_format($variation_prices[$variation->id][$price_group->id]['price'],2)
                        : 0,
                    ['class' => 'form-control input_number input-sm group-price-input', 'data-variation-id' => $variation->id, 'data-price-group-id' => $price_group->id],
                ) !!}  
                @endif
                @php
                    $price_type = !empty($variation_prices[$variation->id][$price_group->id]['price_type'])
                        ? $variation_prices[$variation->id][$price_group->id]['price_type']
                        : 'percentage';
                    $name = 'group_prices[' . $variation->id . '][price_type]';
                @endphp
                <select name={{ $name }} class="form-control group-price-type" data-variation-id="{{ $variation->id }}" data-price-group-id="{{ $price_group->id }}">
                    <option value="percentage" >@lang('lang_v1.percentage')</option>
                    <option value="fixed" >@lang('lang_v1.fixed')</option>
                </select>
            </td>
        @endforeach
        <td>
            <span class="final-price" data-currency_symbol="true">{{ number_format($final_price, 2) }}</span>
        </td>
        <td class="text-center">
            <i class="fa fa-trash remove_product_row cursor-pointer" aria-hidden="true"></i>
        </td>
    </tr>
@endforeach