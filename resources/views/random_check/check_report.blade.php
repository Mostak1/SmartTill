{{-- random_check/check_report.blade.php --}}
@extends('layouts.app')

@section('title', __('Check Report'))
@section('css')
<style>
    @media print{
        .print-font{
            font-size: 10px !important;
        }
        .print-exclude {
            display: none !important;
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

        <form method="GET" action="{{ route('products.generateReport') }}">
            <div class="row" style="align-items: center;">
                <div class="col-md-3"></div>
                <div class="col-md-6">
                    <div class="form-group" style="display: flex; align-items: center;">
                        {!! Form::label('check_report_date_range', __('report.date_range') . ':', ['style' => 'margin-right: 10px;']) !!}
                        {!! Form::text('check_report_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly', 'style' => 'flex: 1; margin-right: 10px;']) !!}
                        <button type="button" id="generate_report" class="btn btn-primary" style="margin-right: 10px;">@lang('Generate Report')</button>
                        <button type="button" id="printReport" class="btn btn-secondary">@lang('Print Report')</button>
                    </div>
                </div>
                <div class="col-md-3"></div>
            </div>
        </form>        

        <div id="reportContent">
            <!-- Initial content if needed -->
        </div>
    @endcomponent
</section>
@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        
        var dateRangeSettings = {
            ranges: ranges,
            startDate: moment().startOf('month'),
            endDate: moment().endOf('month'),
            locale: {
                cancelLabel: LANG.clear,
                applyLabel: LANG.apply,
                customRangeLabel: LANG.custom_range,
                format: moment_date_format,
                toLabel: '~',
            },
            maxDate: moment()  // This ensures that the end date cannot be set beyond today
        };

        $('#check_report_date_range').daterangepicker(
            dateRangeSettings,
            function (start, end) {
                $('#check_report_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            }
        );
        $('#check_report_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#check_report_date_range').val('');
        });

        $('#generate_report').click(function() {
            var start = $('#check_report_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
            var end = $('#check_report_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
            $.ajax({
                url: '{{ route("products.generateReport") }}',
                type: 'GET',
                data: {
                    start_date: start,
                    end_date: end
                },
                success: function(response) {
                    $('#reportContent').html(response.content);
                }
            });
        });

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