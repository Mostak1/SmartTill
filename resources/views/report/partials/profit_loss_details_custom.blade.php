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
        $line_discount = 0;
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
        foreach ($incomeByCategories as $item) {
            $line_discount += $item->selling_price - $item->selling_price_after_discount;
            $total_amount_with_discount += $item->selling_price;
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
                    'payment_status' => $item->payment_status,
                    'selling_price' => $item->selling_price,
                    'sale_line_total' => $item->selling_price_after_discount,
                ];
            }
            $total_amount += $item->final_amount;
            if ($item->payment_status == 'partial') {
                $partial_amount += $item->tp_amount;
                $partial_total += $item->final_amount;
            }
        }
        foreach ($transactions as $transaction) {
            $transactionTotal += $transaction['selling_price'];
            $transactionFinalTotal += $transaction['final_total'];
            $total_discount += $transaction['total_amount'] - $transaction['final_total'];
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
            @foreach ($incomeByCategories as $item)
                <tr>
                    <th>{{ $item->category ?? __('lang_v1.uncategorized') }} <br>
                        <small class="text-muted">Income With
                            discount:
                            {{ number_format($item->selling_price_after_discount, 2) }}
                        </small> <br>
                    </th>
                    <td>
                        <span class="display_currency" data-currency_symbol="true">{{ $item->selling_price }}</span>

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
                        data-currency_symbol="true">{{ $transactionTotal + $duePayment + $data['total_sell_shipping_charge'] }}</span>
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
                        data-currency_symbol="true">{{ $transactionTotal + $duePayment + $data['total_sell_shipping_charge'] - $total_discount - ($partial_total - $partial_amount) - $total_sell_return_inc_tax-$line_discount }}</span>
                </td>
            </tr>
        </table>
    @endcomponent
</div>
<div class="col-xs-6">
    @component('components.widget')
        <table class="table table-striped">
            <tr>
                <th>Regular Discount <br><small class="text-muted"></small></th>
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
                    Total Sale Return <br>
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
                <th>Total Due Remaining <br><small class="text-muted"></small></th>
                <td>
                    <span class="display_currency"
                        data-currency_symbol="true">{{ $partial_total - $partial_amount }}</span>
                </td>
            </tr>

        </table>
    @endcomponent
</div>
<br>
{{-- <div class="col-md-12">
    @component('components.widget')
        <h3 class="text-muted mb-0"> Transection sell line transactionTotal: {{ $transactionTotal }}
            total_amount:{{ $total_amount }} partial_amount: {{ $partial_amount }} partial_total:{{ $partial_total }}
        </h3>
        <div class="">
            <table class="table table-striped">
                @foreach ($totalbyTransaction as $item)
                    <tr>
                        <th> {{ $item->saleId }} -
                            <span class="display_currency" data-currency_symbol="true">

                                {{ $item->selling_price_after_discount }}
                            </span>
                        </th>
                        <td>
                            <span class="display_currency" data-currency_symbol="true">{{ $item->total_amount }}</span>
                        </td>
                        <td> <span class="display_currency"
                                data-currency_symbol="true">{{ $item->total_amount - $item->selling_price_after_discount }}</span>
                        </td>
                    </tr>
                @endforeach
        </div>
        <h3 class="text-muted mb-0">Transection </h3>
        <table class="table table-striped" border="1">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Selling Price</th>
                    <th>Sale Line Total After Discount</th>
                    <th>Total Amount</th>
                    <th>Final Total</th>
                    <th>payment_status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transactions as $transaction)
                    <tr>
                        <td>{{ $transaction['id'] }}</td>
                        <td>{{ number_format($transaction['selling_price'], 2) }}</td>
                        <td>{{ number_format($transaction['sale_line_total'], 2) }}</td>
                        <td>{{ number_format($transaction['total_amount'], 2) }}</td>
                        <td>{{ number_format($transaction['final_total'], 2) }}</td>
                        <td>{{ $transaction['payment_status'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endcomponent
</div>
<div class="col-md-12">
    @component('components.widget')
        <h3 class="text-muted mb-0"> Payments
        </h3>


        <table class="table table-striped" border="1">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Amount</th>
                    <th>Pay Date</th>
                    <th>Pay Method</th>
                    <th>Return Product</th>
                    <th>Payment Referance</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payments as $payment)
                    <tr>
                        <td>{{ $payment->transaction_id }}</td>
                        <td>{{ $payment->amount }}</td>
                        <td>{{ $payment->paid_on }}</td>
                        <td>{{ $payment->method }}</td>
                        <td>{{ $payment->is_return }}</td>
                        <td>{{ $payment->payment_ref_no }}</td>
                    <tr>
                        <td>{{ $payment->transaction_id }}</td>
                        <td>{{ $payment->amount }}</td>
                        <td>{{ $payment->paid_on }}</td>
                        <td>{{ $payment->method }}</td>
                        <td>{{ $payment->is_return }}</td>
                        <td>{{ $payment->payment_ref_no }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endcomponent
</div> --}}