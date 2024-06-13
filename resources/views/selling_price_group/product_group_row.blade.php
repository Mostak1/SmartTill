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
            <td>
                {!! Form::text(
                    'group_prices[' . $variation->id . '][price]',
                    !empty($variation_prices[$variation->id][$price_group->id]['price'])
                        ? @num_format($variation_prices[$variation->id][$price_group->id]['price'])
                        : 0,
                    ['class' => 'form-control input_number input-sm group-price-input', 'data-variation-id' => $variation->id, 'data-price-group-id' => $price_group->id],
                ) !!}
                @php
                    $price_type = !empty($variation_prices[$variation->id][$price_group->id]['price_type'])
                        ? $variation_prices[$variation->id][$price_group->id]['price_type']
                        : 'fixed';
                    $name = 'group_prices[' . $variation->id . '][price_type]';
                @endphp
                <select name={{ $name }} class="form-control group-price-type" data-variation-id="{{ $variation->id }}" data-price-group-id="{{ $price_group->id }}">
                    <option value="fixed" @if ($price_type == 'fixed') selected @endif>@lang('lang_v1.fixed')</option>
                    <option value="percentage" @if ($price_type == 'percentage') selected @endif>@lang('lang_v1.percentage')</option>
                </select>
            </td>
        @endforeach
        <td>
            <span class="final-price" data-currency_symbol="true">{{ number_format($variation->sell_price_inc_tax, 2) }}</span>
        </td>
        <td class="text-center">
            <i class="fa fa-trash remove_product_row cursor-pointer" aria-hidden="true"></i>
        </td>
    </tr>
@endforeach