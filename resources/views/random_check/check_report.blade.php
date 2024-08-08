@extends('layouts.app')

@section('title', __('Check Report'))

@section('css')
<style>
    @media print {
        .print-font {
            font-size: 10px !important;
        }
        .print-exclude {
            display: none !important;
        }
    }

    .swal2-custom {
        width: 500px !important;
        height: 350px !important;
        font-size: 16px !important;
    }
    .swal2-custom .swal2-title {
        font-size: 20px !important;
    }
    .swal2-custom .swal2-content {
        font-size: 14px !important;
    }
    .swal2-custom .swal2-actions .swal2-styled {
        font-size: 14px !important;
    }
</style>
@endsection

@section('content')
<section class="content">
    @component('components.widget')
        <!-- Back Button -->
        <div class="mb-3">
            <a href="{{ route('random.randomCheckIndex') }}" class="btn btn-secondary" style="font-size: 30px; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> @lang('Check Report')
            </a>
        </div> <br>

        <form method="GET" action="{{ route('random.generateReport') }}">
            <div class="row" style="align-items: center;">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('business_location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('business_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'business_location_id']) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('check_report_date_range', __('report.date_range') . ':', ['style' => 'margin-right: 10px;']) !!}
                        {!! Form::text('check_report_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly', 'id' => 'check_report_date_range']) !!}
                    </div>
                </div>
                <div class="col-md-3" style="margin-top: 25px">
                    <button type="button" id="generate_report" class="btn btn-primary" style="margin-right: 10px;">@lang('Generate Report')</button>
                    <button type="button" id="printReport" class="btn btn-secondary">@lang('Print Report')</button>
                </div>
            </div>
        </form>

        <div id="reportContent">
            <!-- Report content will be injected here -->
        </div>
    @endcomponent
</section>
@endsection

@section('javascript')
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            maxDate: moment()
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
            var business_location_id = $('#business_location_id').val();
            $.ajax({
                url: '{{ route("random.generateReport") }}',
                type: 'GET',
                data: {
                    start_date: start,
                    end_date: end,
                    location_id: business_location_id
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

        $(document).on('click', '#finalize-button', function(e) {
            e.preventDefault(); // Prevent the default form submission

            Swal.fire({
                title: 'Are you sure?',
                text: "Once finalized, this report cannot be edited or deleted. Do you want to proceed?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, finalize it!',
                customClass: {
                    popup: 'swal2-custom'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('#finalize-report-form');
                    $.ajax({
                        url: form.attr('action'),
                        type: form.attr('method'),
                        data: form.serialize(),
                        
                        success: function(response) {
                            if (response.success) {
                                toastr.success('Report finalized successfully!');
                                setTimeout(function() {
                                window.location.href = "{{ route('random.randomCheckIndex') }}";
                            }, 500);
                            } else {
                                toastr.error('Failed to finalize report.');
                            }
                        },
                        error: function(xhr) {
                            var errorMessage = xhr.responseJSON ? xhr.responseJSON.message : 'An error occurred';
                            toastr.error(errorMessage);
                        }
                    });
                }
            });
        });
    });
</script>
@endsection
