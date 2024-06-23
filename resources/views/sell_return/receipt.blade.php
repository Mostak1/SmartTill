<style>
    @media print {
        @page {
            margin: 0 2px;
        }

        body {
            margin: 0 2px;
            /* Adjust or remove as needed */
        }
    }

    .watermark-container {
        position: relative;
        width: 100%;
        height: fit-content;
        /* Full viewport height */
        overflow: hidden;
    }

    .watermark {
        position: absolute;
        bottom: 60%;
        left: 20%;
        font-size: 50px;
        color: rgba(80, 74, 74, 0.245) !important;
        /* Adjust the opacity as needed */
        transform: rotate(-45deg);
        transform-origin: bottom right;
        white-space: nowrap;
        z-index: 10;
        /* Prevent text from wrapping */
    }

    .border-bottom-dotted {
        border-bottom: 1px dotted darkgray;
    }

    .border-top {
        border-top: 1px solid #242424;
    }

    .border-bottom {
        border-bottom: 1px solid #242424;
    }

    .flex-box {
        display: flex;
        width: 100%;
    }

    .flex-box p {
        width: 50%;
        margin-bottom: 0px;
        white-space: nowrap;
    }

    .sub-headings {
        font-size: 15px !important;
        font-weight: 700 !important;
    }
</style>
<div style="width: 100%; margin-top:0px; font-size: 12px;">

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
    <div style="margin: 5px auto; width:fit-content; font-size:15px;">
        <div class="">
            <b>Return With</b>
            @if ($receipt_details->sell_method == 'custom_pay_1')
                <b>BKASH</b>
            @elseif($receipt_details->sell_method == 'card')
                <b>CARD</b>
            @elseif($receipt_details->sell_method == 'cash')
                <b>CASH</b>
            @endif
        </div>
    </div>
    <div class="border-top textbox-info">
        <p class="f-left"><strong>{!! $receipt_details->invoice_no_prefix !!} Invoice No:{{ $receipt_details->invoice_no }}</strong> </p>
        <p class="f-right">
            <strong>{{ $receipt_details->customer_label ?? '' }}</strong>
        </p>
    </div>
    <div class="textbox-info">
        <p class="f-left"><strong>{!! $receipt_details->date_label !!} : {{ $receipt_details->invoice_date }}</strong> <br>
            <strong>Counter:</strong> {{ Auth::user()->username }}
        </p>

        <p style="text-align: right" class="f-right">
            @if (!empty($receipt_details->customer_info))
                {!! $receipt_details->customer_info !!}
            @endif
        </p>
    </div>


    @if (!empty($receipt_details->sales_person_label))
        <div class="textbox-info">
            <p class="f-left"><strong>{{ $receipt_details->sales_person_label }}</strong></p>

            <p class="f-right">{{ $receipt_details->sales_person }}</p>
        </div>
    @endif
    <!-- Waiter info -->
    @if (!empty($receipt_details->service_staff_label) || !empty($receipt_details->service_staff))
        <div class="textbox-info">
            <p class="f-left"><strong>
                    {!! $receipt_details->service_staff_label !!}
                </strong></p>
            <p class="f-right">
                {{ $receipt_details->service_staff }}
            </p>
        </div>
    @endif

    @if (!empty($receipt_details->table_label) || !empty($receipt_details->table))
        <div class="textbox-info">
            <p class="f-left"><strong>
                    @if (!empty($receipt_details->table_label))
                        <b>{!! $receipt_details->table_label !!}</b>
                    @endif
                </strong></p>
            <p class="f-right">
                {{ $receipt_details->table }}
            </p>
        </div>
    @endif

    @if (!empty($receipt_details->sell_custom_field_1_value))
        <div class="textbox-info">
            <p class="f-left"><strong>{!! $receipt_details->sell_custom_field_1_label !!}</strong></p>
            <p class="f-right">
                {{ $receipt_details->sell_custom_field_1_value }}
            </p>
        </div>
    @endif
    @if (!empty($receipt_details->sell_custom_field_2_value))
        <div class="textbox-info">
            <p class="f-left"><strong>{!! $receipt_details->sell_custom_field_2_label !!}</strong></p>
            <p class="f-right">
                {{ $receipt_details->sell_custom_field_2_value }}
            </p>
        </div>
    @endif
    @if (!empty($receipt_details->sell_custom_field_3_value))
        <div class="textbox-info">
            <p class="f-left"><strong>{!! $receipt_details->sell_custom_field_3_label !!}</strong></p>
            <p class="f-right">
                {{ $receipt_details->sell_custom_field_3_value }}
            </p>
        </div>
    @endif
    @if (!empty($receipt_details->sell_custom_field_4_value))
        <div class="textbox-info">
            <p class="f-left"><strong>{!! $receipt_details->sell_custom_field_4_label !!}</strong></p>
            <p class="f-right">
                {{ $receipt_details->sell_custom_field_4_value }}
            </p>
        </div>
    @endif
    <div class="border-bottom">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <td style="width: 5% !important">
                        #</td>

                    @php
                        $p_width = 35;
                    @endphp
                    @if ($receipt_details->show_cat_code != 1)
                        @php
                            $p_width = 45;
                        @endphp
                    @endif
                    <td style="text-align: center; width: {{ $p_width }}% !important">
                        {{ $receipt_details->table_product_label }}
                    </td>

                    @if ($receipt_details->show_cat_code == 1)
                        <td style=" width: 10% !important">
                            {{ $receipt_details->cat_code_label }}</td>
                    @endif

                    <td style=" width:
                        15% !important">
                        {{ $receipt_details->table_qty_label }}
                    </td>
                    <td style=" width: 15% !important">
                        {{ $receipt_details->table_unit_price_label }}
                    </td>
                    <td style="width: 20% !important">
                        {{ $receipt_details->table_subtotal_label }}
                    </td>
                </tr>
            </thead>
            <tbody>
                @foreach ($receipt_details->lines as $line)
                    <tr>
                        <td class="text-center">
                            {{ $loop->iteration }}
                        </td>
                        <td>
                            {{ $line['name'] }} {{ $line['variation'] }}
                            @if (!empty($line['sub_sku']))
                                , {{ $line['sub_sku'] }}
                                @endif @if (!empty($line['brand']))
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

                        <td class="">
                            {{ number_format($line['quantity']) }}
                        </td>
                        <td class="">
                            {{ number_format($line['unit_price_exc_tax']) }}
                        </td>
                        <td class="">
                            {{ number_format($line['line_total']) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div style="width: 100%; max-width: 100%;">
        <div class="flex-box">
            <p class="width-70 left text-right sub-headings">
                {!! $receipt_details->subtotal_label !!}
            </p>
            <p class="width-30 text-right sub-headings">
                ৳ {{ number_format($receipt_details->subtotal) }}
            </p>
        </div>
        <!-- Discount -->
        @if (!empty($receipt_details->discount))
            <div class="flex-box">
                <p class="width-70 text-right">
                    {!! $receipt_details->discount_label !!}
                </p>

                <p class="width-30 text-right">
                    (-) {{ number_format($receipt_details->discount) }}
                </p>
            </div>
        @endif
        <div class="flex-box">
            <p class="width-70 text-right sub-headings">
                {!! $receipt_details->total_label !!}
            </p>
            <p class="width-30 text-right sub-headings">
                ৳ {{ number_format($receipt_details->total) }}
            </p>
        </div>
    </div>

    @if (!empty($receipt_details->additional_notes))
        <div>
            {{ $receipt_details->additional_notes }}
        </div>
    @endif

    @if ($receipt_details->show_barcode)
        <div style="margin:0 auto; width:90%;">
            <img
                src="data:image/png;base64,{{ DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2, 30, [39, 48, 54], true) }}">
        </div>
    @endif

    @if (!empty($receipt_details->footer_text))
        <div style="text-align: center;">
            {!! $receipt_details->footer_text !!}
        </div>
    @endif
</div>