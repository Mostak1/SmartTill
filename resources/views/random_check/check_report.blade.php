@extends('layouts.app')

@section('title', __('Check Report'))
@section('css')
<style>
    @media print{
        .print-font{
            font-size: 10px !important;
        }
    }
</style>
    
@endsection
@section('content')
    <section class="content">
        @component('components.widget')
            <!-- Back Button -->
            <div class="mb-3">
                <a href="{{ route('products.randomCheckIndex') }}" class="btn btn-secondary"
                    style="font-size: 30px; text-decoration: none;" class="back-button">
                    <i class="fas fa-arrow-left"></i> @lang('Check Report')
                </a>
            </div> <br>

            <form method="GET" action="{{ route('products.checkReport') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('start_date', __('Start Date') . ':') !!}
                            {!! Form::date('start_date',$startDate, ['class' => 'form-control']) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('end_date', __('End Date') . ':') !!}
                            {!! Form::date('end_date',$endDate, ['class' => 'form-control']) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <br>
                            <button type="submit" class="btn btn-primary">@lang('Generate Report')</button>
                            <button type="button" id="printReport" class="btn btn-secondary">@lang('Print Report')</button>
                        </div>
                    </div>
                </div>
            </form>

            <div id="reportContent">
                <p style="text-align: right">Generated at: {{ now()->format('d-m-Y, h:i A') }}</p>
                <div style="font-size: 24px; width:fit-content; margin:0 auto;">
                    Missing/Surplus Product Report
                </div>
                <p style="font-size: 16px; width:fit-content; margin:0 auto;">@lang('Start Date'): <span
                        id="reportStartDate">{{ $startDate }}</span>,
                    @lang('End Date'): <span id="reportEndDate">{{ $endDate }}</span></p>

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
                <br>
                <h3 style="text-align: center;">@lang('Net Result')</h3>
                <p style="text-align: center">
                    <b>@lang('Net Result'): ৳ {{ number_format($netResult, 2) }} ({{ $resultStatus }})</b>
                </p>
            </div>
        @endcomponent
    </section>
@endsection

@section('javascript')
    <script>
        $(document).ready(function() {
            // $('input[name="start_date"], input[name="end_date"]').daterangepicker({
            //     singleDatePicker: true,
            //     showDropdowns: true,
            //     locale: {
            //         format: 'YYYY-MM-DD'
            //     }
            // });

            $('#printReport').click(function() {
                var originalContents = $('body').html();
                var printContents = $('#reportContent').html();
                $('body').html(printContents);
                window.print();
                $('body').html(originalContents);
            });
        });
    </script>
@endsection