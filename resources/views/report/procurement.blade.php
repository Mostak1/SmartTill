@extends('layouts.app')
@section('title', 'Procurement Demand Report')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>Procurement Demand Report</h1>
</section>

<!-- Main content -->
<section class="content no-print">
    @component('components.filters', ['title' => __('report.filters')])
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('category', __('product.category') . ':') !!}
                {!! Form::select('category', $categories, null, ['class' => 'form-control select2', 'placeholder' => __('messages.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('brand', __('product.brand') . ':') !!}
                {!! Form::select('brand', $brands, null, ['class' => 'form-control select2', 'placeholder' => __('messages.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('days', __('report.days') . ':') !!}
                {!! Form::select('days', [1 => '1 day', 3 => '3 days', 7 => '7 days', 14 => '14 days', 30 => '30 days'], null, ['class' => 'form-control select2']); !!}
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary'])
        <div class="table-responsive">
            <table class="table table-bordered table-striped ajax_view" id="procurement_report_table">
                <thead>
                    <tr>
                        <th>@lang('product.name')</th>
                        <th>@lang('product.category')</th>
                        <th>@lang('product.brand')</th>
                        <th>@lang('report.predicted_demand')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var procurement_report_table = $('#procurement_report_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('procurement.report.data') }}",
                data: function(d) {
                    d.category = $('#category').val();
                    d.brand = $('#brand').val();
                    d.days = $('#days').val();
                }
            },
            columns: [
                { data: 'product_name', name: 'product_name' },
                { data: 'category_name', name: 'category_name' },
                { data: 'brand_name', name: 'brand_name' },
                { data: 'predicted_demand', name: 'predicted_demand' }
            ],
            drawCallback: function(settings) {
                __currency_convert_recursively($('#procurement_report_table'));
            }
        });

        $('#category, #brand, #days').change(function() {
            procurement_report_table.ajax.reload();
        });
    });
</script>
@endsection
