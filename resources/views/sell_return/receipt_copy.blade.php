<style>
    @media print {
        @page {
            margin: 2px;
        }

        body {
            margin: 2px;
            /* Adjust or remove as needed */
        }
    }
</style>
<div style="width: 100%; margin-top: 100px; font-size: 12px;">
    <div style="text-align: right;">
        @if (!empty($receipt_details->invoice_heading))
            <p style="font-weight: bold;">{!! $receipt_details->invoice_heading !!}</p>
        @endif

        <p>
            @if (!empty($receipt_details->invoice_no_prefix))
                {!! $receipt_details->invoice_no_prefix !!}
            @endif
            {{ $receipt_details->invoice_no }}
        </p>
    </div>

    @if (!empty($receipt_details->header_text))
        <div style="text-align: center;">
            {!! $receipt_details->header_text !!}
        </div>
    @endif

    <div style="margin: 10px 0;">
        @if (!empty($receipt_details->logo))
            <div style="text-align: center;">
                <img src="{{ $receipt_details->logo }}" style="max-width: 80%; height: auto;"><br>
            </div>
        @endif

        @if (!empty($receipt_details->display_name))
            <p style="text-align: center;">
                {{ $receipt_details->display_name }}<br>
                {!! $receipt_details->address !!}

                @if (!empty($receipt_details->contact))
                    <br>{{ $receipt_details->contact }}
                @endif

                @if (!empty($receipt_details->website))
                    <br>{{ $receipt_details->website }}
                @endif

                @if (!empty($receipt_details->tax_info1))
                    <br>{{ $receipt_details->tax_label1 }} {{ $receipt_details->tax_info1 }}
                @endif

                @if (!empty($receipt_details->tax_info2))
                    <br>{{ $receipt_details->tax_label2 }} {{ $receipt_details->tax_info2 }}
                @endif

                @if (!empty($receipt_details->location_custom_fields))
                    <br>{{ $receipt_details->location_custom_fields }}
                @endif
            </p>
        @endif
    </div>

    <div>
        @if (!empty($receipt_details->table_label) || !empty($receipt_details->table))
            <p>
                @if (!empty($receipt_details->table_label))
                    {!! $receipt_details->table_label !!}
                @endif
                {{ $receipt_details->table }}
            </p>
        @endif

        @if (!empty($receipt_details->waiter_label) || !empty($receipt_details->waiter))
            <p>
                @if (!empty($receipt_details->waiter_label))
                    {!! $receipt_details->waiter_label !!}
                @endif
                {{ $receipt_details->waiter }}
            </p>
        @endif
    </div>

    <div style="margin: 10px 0;">
        @if (!empty($receipt_details->payment))
            <table style="width: 100%; border-collapse: collapse;">
                @foreach ($receipt_details->payment as $payment)
                    <tr>
                        <td>{{ $payment->method }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>

    @if (!empty($receipt_details->total_due))
        <p style="text-align: right; font-weight: bold;">
            <span style="float: left;">{!! $receipt_details->total_due_label !!}</span>
            {{ $receipt_details->total_due }}
        </p>
    @endif

    @if (!empty($receipt_details->total_paid))
        <p style="text-align: right;">
            <span style="float: left;">{!! $receipt_details->total_paid_label !!}</span>
            {{ $receipt_details->total_paid }}
        </p>
    @endif

    @if (!empty($receipt_details->date_label))
        <p style="text-align: right;">
            <span style="float: left;">{{ $receipt_details->date_label }}</span>
            {{ $receipt_details->invoice_date }}
        </p>
    @endif

    <div>
        <b>{{ $receipt_details->customer_label ?? '' }}</b><br>
        @if (!empty($receipt_details->customer_info))
            {!! $receipt_details->customer_info !!}
        @endif
        @if (!empty($receipt_details->client_id_label))
            <br>{{ $receipt_details->client_id_label }} {{ $receipt_details->client_id }}
        @endif
        @if (!empty($receipt_details->customer_tax_label))
            <br>{{ $receipt_details->customer_tax_label }} {{ $receipt_details->customer_tax_number }}
        @endif
        @if (!empty($receipt_details->customer_custom_fields))
            <br>{!! $receipt_details->customer_custom_fields !!}
        @endif
    </div>

    <div style="text-align: center; margin: 10px 0;">
        {{-- <span>{{$receipt_details->payment}}</span> --}}
            {{-- @foreach ($receipt_details->payment as $item)
            <span>{{$item->method}}</span>
            @endforeach --}}
        <h4>REFUND PAID</h4>
    </div>

    <div>
        <table style="width: 100%; border-collapse: collapse; text-align: center;">
            <thead>
                <tr style="background-color: #357ca5; color: white;">
                    <td style="width: 5%;">No</td>
                    <td style="width: 45%;">{{ $receipt_details->table_product_label }}</td>
                    @if ($receipt_details->show_cat_code == 1)
                        <td style="width: 10%;">{{ $receipt_details->cat_code_label }}</td>
                    @endif
                    <td style="width: 15%;">{{ $receipt_details->table_qty_label }}</td>
                    <td style="width: 15%;">{{ $receipt_details->table_unit_price_label }}</td>
                    <td style="width: 20%;">{{ $receipt_details->table_subtotal_label }}</td>
                </tr>
            </thead>
            <tbody>
                @foreach ($receipt_details->lines as $line)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            {{ $line['name'] }} {{ $line['variation'] }}
                            @if (!empty($line['sub_sku']))
                                , {{ $line['sub_sku'] }}
                            @endif
                            @if (!empty($line['brand']))
                                , {{ $line['brand'] }}
                            @endif
                            @if (!empty($line['sell_line_note']))
                                ({{ $line['sell_line_note'] }})
                            @endif
                        </td>

                        @if ($receipt_details->show_cat_code == 1)
                            <td>
                                @if (!empty($line['cat_code']))
                                    {{ $line['cat_code'] }}
                                @endif
                            </td>
                        @endif

                        <td>{{ $line['quantity'] }} {{ $line['units'] }}</td>
                        <td>{{ $line['unit_price_exc_tax'] }}</td>
                        <td>{{ $line['line_total'] }}</td>
                    </tr>
                @endforeach

                @php
                    $lines = count($receipt_details->lines);
                @endphp

                @for ($i = $lines; $i < 7; $i++)
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                @endfor

            </tbody>
        </table>
    </div>

    <div style="margin: 10px 0;">
        <b>Authorized Signatory</b>
    </div>

    <div style="margin: 10px 0;">
        <table style="width: 100%; border-collapse: collapse;">
            <tbody>
                <tr>
                    <td style="width: 50%;">{!! $receipt_details->subtotal_label !!}</td>
                    <td style="text-align: right;">{{ $receipt_details->subtotal }}</td>
                </tr>

                @if (!empty($receipt_details->taxes))
                    @foreach ($receipt_details->taxes as $k => $v)
                        <tr>
                            <td>{{ $k }}</td>
                            <td style="text-align: right;">{{ $v }}</td>
                        </tr>
                    @endforeach
                @endif

                @if (!empty($receipt_details->discount))
                    <tr>
                        <td>{!! $receipt_details->discount_label !!}</td>
                        <td style="text-align: right;">(-) {{ $receipt_details->discount }}</td>
                    </tr>
                @endif

                @if (!empty($receipt_details->group_tax_details))
                    @foreach ($receipt_details->group_tax_details as $key => $value)
                        <tr>
                            <td>{!! $key !!}</td>
                            <td style="text-align: right;">(+) {{ $value }}</td>
                        </tr>
                    @endforeach
                @else
                    @if (!empty($receipt_details->tax))
                        <tr>
                            <td>{!! $receipt_details->tax_label !!}</td>
                            <td style="text-align: right;">(+) {{ $receipt_details->tax }}</td>
                        </tr>
                    @endif
                @endif

                <tr style="background-color: #357ca5; color: white;">
                    <th>{!! $receipt_details->total_label !!}</th>
                    <td style="text-align: right;">{{ $receipt_details->total }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @if (!empty($receipt_details->additional_notes))
        <div>
            {{ $receipt_details->additional_notes }}
        </div>
    @endif

    @if ($receipt_details->show_barcode)
        <div style="text-align: center;">
            <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2, 30, [39, 48, 54], true) }}">
        </div>
    @endif

    @if (!empty($receipt_details->footer_text))
        <div style="text-align: center;">
            {!! $receipt_details->footer_text !!}
        </div>
    @endif
</div>
