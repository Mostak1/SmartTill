{{-- random_check/check_report_content.blade.php --}}
<p style="text-align: right">Generated at: {{ now()->format('d-m-Y, h:i A') }}</p>
<div style="font-size: 24px; width:fit-content; margin:0 auto;">
    Missing/Surplus Product Report
</div>
<p style="font-size: 16px; width:fit-content; margin:0 auto;">@lang('Start Date'): <span id="reportStartDate">{{ $startDate }}</span>, @lang('End Date'): <span id="reportEndDate">{{ $endDate }}</span></p>

<h3>@lang('Missing Items')</h3>
<table class="table table-bordered print-font">
    <thead>
        <tr>
            <th>Category</th>
            <th>Product Name</th>
            <th>SKU</th>
            <th>Brand</th>
            <th>Missing Qty</th>
            <th>Subtotal (Sell Price)</th>
            <th>Comment</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($missingItems as $item)
            <tr>
                <td>{{ $item->category_name }}</td>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->sku }}</td>
                <td>{{ $item->brand_name }}</td>
                <td>{{ abs($item->physical_count) }}</td>
                <td>৳ {{ number_format(abs($item->physical_count) * $item->sell_price_inc_tax, 2) }}</td>
                <td>{{ $item->comment }}</td>
                <td>{{ $item->created_at->format('d M Y') }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" class="text-right"><strong>Total:</strong></td>
            <td><b>৳ {{ number_format($totalMissingSellPrice, 2) }}</b></td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>

<h3>@lang('Surplus Items')</h3>
<table class="table table-bordered print-font">
    <thead>
        <tr>
            <th>Category</th>
            <th>Product Name</th>
            <th>SKU</th>
            <th>Brand</th>
            <th>Surplus Qty</th>
            <th>Subtotal (Sell Price)</th>
            <th>Comment</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($surplusItems as $item)
            <tr>
                <td>{{ $item->category_name }}</td>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->sku }}</td>
                <td>{{ $item->brand_name }}</td>
                <td>{{ number_format($item->physical_count) }}</td>
                <td>৳ {{ number_format($item->physical_count * $item->sell_price_inc_tax, 2) }}</td>
                <td>{{ $item->comment }}</td>
                <td>{{ $item->created_at->format('d M Y') }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" class="text-right"><strong>Total:</strong></td>
            <td><b>৳ {{ number_format($totalSurplusSellPrice, 2) }}</b></td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>

<h3 class="text-center">@lang('Net Result'): ৳ {{ number_format(abs($netResult), 2) }} <span class="{{ $resultStatus == 'Loss' ? 'text-danger' : 'text-success' }}">({{ $resultStatus }})</span></h3>

{!! Form::submit('Finalize', [
            'class' => 'btn btn-primary print-exclude btn-finalize',
            'id' => 'finalize-button',
            'style' => 'display: block; width: 160px; height: 50px; margin: 0 auto; margin-top:30px; font-size: 18px;',
        ]) !!}