<p style="text-align: right">Generated at: {{ now()->format('d-m-Y, h:i A') }}</p>
<div style="font-size: 24px; width:fit-content; margin:0 auto;">
    Missing/Surplus Product Report
</div>
<p style="font-size: 16px; width:fit-content; margin:0 auto;">@lang('Start Date'): <span id="reportStartDate">{{ $startDate }}</span>, @lang('End Date'): <span id="reportEndDate">{{ $endDate }}</span></p>
<p style="font-size: 14px; width:fit-content; margin:0 auto;"><strong>Location: </strong>{{ $location->name }}</p>

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

<form id="finalize-report-form" method="POST" action="{{ route('random.finalizeReport') }}">
    @csrf
    {!! Form::hidden('location_id', $location->id, ['class' => 'form-control', 'placeholder' => 'Location ID', 'readonly']) !!}
    {!! Form::hidden('start_date', $startDate, ['class' => 'form-control', 'placeholder' => 'Start Date', 'readonly']) !!}
    {!! Form::hidden('end_date', $endDate, ['class' => 'form-control', 'placeholder' => 'End Date', 'readonly']) !!}
    {!! Form::hidden('net_result', $netResult, ['class' => 'form-control', 'placeholder' => 'Net Result', 'readonly']) !!}

    <div class="row">
        <div class="col-md-3"></div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::textarea('comments', null, ['class' => 'form-control no-print', 'style' => 'display: block; margin: 0 auto; margin-top:30px;', 'rows' => 3, 'placeholder' => 'Comments...']) !!}
            </div>
        </div>
        <div class="col-md-3"></div>
    </div>

    <button type="button" id="finalize-button" class="btn btn-primary print-exclude btn-finalize" style="display: block; width: 160px; height: 50px; margin: 0 auto; margin-top:30px; font-size: 18px;">@lang('Finalize Report')</button>
</form>
