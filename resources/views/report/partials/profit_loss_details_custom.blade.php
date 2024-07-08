@php
    use Carbon\Carbon;
    $start_date = Carbon::parse($start_date);
    $end_date = Carbon::parse($end_date);
    $currentDateTime = date('Y-m-d 00:00:00');
@endphp
<h3 class="text-center">Report Show From {{ $start_date->format('d-F-Y') }} TO {{ $end_date->format('d-F-Y') }}</h3>
{{-- <h3 class="text-center">Report Show From {{$start_date}} TO {{$end_date}}</h3> --}}

<div class="col-xs-6">
    @php
        $total_amount = 0;
        $total_amount_with_discount = 0;
        $cash = 0;
        $card = 0;
        $bkash = 0;
        $pay_cash = 0;
        $pay_card = 0;
        $pay_bkash = 0;
        $re_cash = 0;
        $re_card = 0;
        $re_bkash = 0;
        $transaction = [];
        $transactionTotal = 0;
        $partial_amount = 0;
        $partial_total = 0;
        $transactionFinalTotal = 0;
        $total_discount = 0;
        
        $totalWithDiscount = 0;
        $totalWithoutdiscount=0;
        $totalWithoutdiscountWithSellReturn = 0;
        $line_discount = 0;
        foreach ($productByCategory as $item) {
            $line_discount += $item['with_discount_subtotal'] - $item['subtotal'];
            $totalWithDiscount += $item['with_discount_subtotal'];
            $totalWithoutdiscount += $item['subtotal'];
            $totalWithoutdiscountWithSellReturn += $item['with_sellreturn_subtotal'];
        }
        $payMethod = [];
        foreach ($totalByMethod as $item) {
            if (!isset($payMethod[$item->tp_id])) {
                $payMethod[$item->tp_id] = [
                    'id' => $item->tp_id,
                    'method' => $item->method,
                    'tp_amount' => $item->tp_amount,
                ];
            }
        }

        foreach ($payMethod as $item) {
            if ($item['method'] == 'cash') {
                $cash += $item['tp_amount'];
            } elseif ($item['method'] == 'card') {
                $card += $item['tp_amount'];
            } elseif ($item['method'] == 'custom_pay_1') {
                $bkash += $item['tp_amount'];
            }
        }

        foreach ($totalbyTransaction as $item) {
            if (isset($transactions[$item->saleId])) {
                $transactions[$item->saleId]['sale_line_total'] += $item->selling_price_after_discount;
                $transactions[$item->saleId]['selling_price'] += $item->selling_price;
            } else {
                $transactions[$item->saleId] = [
                    'id' => $item->saleId,
                    'total_amount' => $item->saleTotalBeforeTax,
                    'final_total' => $item->trans_final_amount,
                    'shipping_charges' => $item->shipping_charges,
                    'payment_status' => $item->payment_status,
                    'selling_price' => $item->selling_price,
                    'sale_line_total' => $item->selling_price_after_discount,
                ];
            }
            $total_amount += $item->final_amount;
            if ($item->payment_status == 'partial' || $item->payment_status == 'due') {
                $partial_amount += $item->tp_amount;
                $partial_total += $item->final_amount;
            }
        }
        foreach ($transactions as $transaction) {
            $transactionTotal += $transaction['selling_price'];
            $transactionFinalTotal += $transaction['final_total'];
            $total_discount += $transaction['total_amount']+$transaction['shipping_charges'] - $transaction['final_total'];
        }
        foreach ($payments as $item) {
            if ($item->method == 'cash') {
                $pay_cash += $item->amount;
            }
            if ($item->method == 'card') {
                $pay_card += $item->amount;
            } elseif ($item->method == 'custom_pay_1') {
                $pay_bkash += $item->amount;
            }
        }
        foreach ($payReturn as $item) {
            if ($item->method == 'cash') {
                $re_cash += $item->amount;
            }
            if ($item->method == 'card') {
                $re_card += $item->amount;
            } elseif ($item->method == 'custom_pay_1') {
                $re_bkash += $item->amount;
            }
        }
        $saleLinePayment = $cash + $card + $bkash;
        $transactionPayment = $pay_cash + $pay_card + $pay_bkash;
        $duePayment = $transactionPayment;

    @endphp

    @component('components.widget')
        {{-- @foreach ($totalByMethod as $item)
            <ul>
                <li>Id:{{ $item->tp_id . ' Amount:' . $item->tp_amount . ' M:-' . $item->method . ' SP- ' . $item->selling_price }}
                </li>
            </ul>
        @endforeach
        New --------------
        @foreach ($payMethod as $item)
            <ul>
                <li>Id:{{ $item['id'] . ' Amount:' . $item['tp_amount'] . ' M:-' . $item['method'] }}</li>
            </ul>
        @endforeach --}}
        <table class="table">
            {{-- <ul>
                @foreach ($productByCategory as $item)
                    <li>
                        <span>{{ $item['category_name'] }}</span>
                        <ol>
                            <li>Total:- <span>{{ $item['subtotal'] }} </span></li>
                            <li>With-sell-return:- <span>{{ $item['with_sellreturn_subtotal'] }}</span></li>
                            <li> With discount:- <span>{{ $item['with_discount_subtotal'] }}</span> <br></li>
                        </ol>
                    </li>
                @endforeach
                <h4>{{$totalWithoutdiscountWithSellReturn}} --- {{$totalWithDiscount}}</h4>
            </ul> --}}
            @foreach ($productByCategory as $item)
                <tr>
                    <th><a href="#" class="sell_details_link" >{{ $item['category_name'] ?? __('lang_v1.uncategorized') }}</a>
                        <br>
                        <small class="text-muted">Income With
                            discount:
                            {{ $item['subtotal'] }}
                            id: {{ $item['id'] }}
                            <input type="text" class="category_id" value="{{$item['id']}}">
                            
                        </small> <br>
                    </th>
                    <td>
                        <span class="display_currency"
                            data-currency_symbol="true">{{ $item['with_discount_subtotal'] }}</span>

                    </td>
                </tr>
            @endforeach
            <tr>
                <th>Shipping Income:</th>
                <td>
                    <span class="display_currency"
                        data-currency_symbol="true">{{ $data['total_sell_shipping_charge'] }}</span>
                </td>
            </tr>
            <tr>
                <th>Due Payment Income:
                </th>
                <td>
                    <span class="display_currency" data-currency_symbol="true">{{ $duePayment }}</span>
                </td>
            </tr>
            <tr>
                <th>Total amount:
                </th>
                <td>
                    <span class="display_currency"
                        data-currency_symbol="true">{{ $totalWithDiscount + $duePayment + $data['total_sell_shipping_charge'] }}</span>
                </td>
            </tr>
            <tr>
                <th>Final Total:<br>
                    <small class="text-muted">Cash: {{ $cash + $pay_cash - $re_cash }}</small>
                    <br>
                    <small class="text-muted">Bkash: {{ $bkash + $pay_bkash - $re_bkash }}</small> <br>
                    <small class="text-muted">Card: {{ $card + $pay_card - $re_card }}</small>
                </th>
                <td>
                    <span class="display_currency"
                        data-currency_symbol="true">{{ $totalWithDiscount + $duePayment + $data['total_sell_shipping_charge'] - $total_discount - ($partial_total - $partial_amount) - $total_sell_return_inc_tax - $line_discount }}</span>
                </td>
            </tr>
        </table>
    @endcomponent
</div>
<div class="col-xs-6">
    @component('components.widget')
        <table class="table table-striped">
            <tr>
                <th>Campaign Discount <br><small class="text-muted"></small></th>
                <td>
                    <span class="display_currency" data-currency_symbol="true">{{ $line_discount }}</span>
                </td>
            </tr>
            <tr>
                <th>Special Dicount <br><small class="text-muted"></small></th>
                <td>
                    <span class="display_currency" data-currency_symbol="true">{{ $total_discount }}</span>

                </td>
            </tr>
            <tr>
                <th>
                    Sale Return <br>
                    <small class="text-muted">
                        Cash = {{ $re_cash }} <br>
                        Bkash = {{ $re_bkash }}<br>
                        Card = {{ $re_card }}
                    </small>
                </th>

                <td>
                    <span class="display_currency" data-currency_symbol="true">{{ $total_sell_return_inc_tax }}</span>
                </td>
            </tr>

            <tr>
                <th>Due Remaining <br><small class="text-muted"></small></th>
                <td>
                    <span class="display_currency"
                        data-currency_symbol="true">{{ $partial_total - $partial_amount }}</span>
                </td>
            </tr>
            <tr>
                <th>Total <br><small class="text-muted"></small></th>
                <td>
                    <span class="display_currency"
                        data-currency_symbol="true">{{ $partial_total - $partial_amount+$total_sell_return_inc_tax+$total_discount+$line_discount }}</span>
                </td>
            </tr>

        </table>
    @endcomponent
</div>
<br>

