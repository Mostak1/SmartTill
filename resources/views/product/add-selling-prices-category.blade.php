@extends('layouts.app')
@section('title', __('lang_v1.add_selling_price_group_prices'))

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('lang_v1.add_selling_price_group_prices')</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        {!! Form::open([
            'url' => action([\App\Http\Controllers\ProductController::class, 'saveSellingPricesCategory']),
            'method' => 'post',
            'id' => 'selling_price_form',
        ]) !!}
        <div class="row">
            <div class="col-xs-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">@lang('sale.product')</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="table-responsive">
                                    <table class="table table-condensed table-bordered table-th-green text-center table-striped">
                                        <thead>
                                            <tr>
                                                <th>@lang('category.category')</th>
                                                @foreach ($price_groups as $price_group)
                                                    <th>{{ $price_group->name }}
                                                        @show_tooltip(__('lang_v1.price_group_price_type_tooltip'))
                                                    </th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    {!! Form::select('category_id', $categories, request('category_id'), [
                                                        'class' => 'form-control select2 input-sm',
                                                        'id' => 'category_id',
                                                    ]) !!}
                                                </td>
                                                @foreach ($price_groups as $price_group)
                                                    <td>
                                                        {!! Form::text('group_prices[' . $price_group->id . '][price]', null, [
                                                            'class' => 'form-control input_number input-sm',
                                                        ]) !!}
                                                        <select name="group_prices[{{ $price_group->id }}][price_type]" class="form-control">
                                                            <option value="fixed">@lang('lang_v1.fixed')</option>
                                                            <option value="percentage">@lang('lang_v1.percentage')</option>
                                                        </select>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                {!! Form::hidden('submit_type', 'save', ['id' => 'submit_type']) !!}
                <div class="text-center">
                    <div class="btn-group">
                        <button id="opening_stock_button" type="submit" value="submit_n_add_opening_stock"
                            class="btn bg-purple submit_form btn-big hide">@lang('lang_v1.save_n_add_opening_stock')</button>
                        <button type="submit" hidden value="save_n_add_another"
                            class="btn bg-maroon submit_form btn-big hide">@lang('lang_v1.save_n_add_another')</button>
                        <button type="submit" value="submit"
                            class="btn btn-primary submit_form btn-big">@lang('messages.save')</button>
                    </div>
                </div>
            </div>
        </div>
        {!! Form::close() !!}
    </section>
@stop

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            $('button.submit_form').click(function(e) {
                e.preventDefault();
                $('input#submit_type').val($(this).attr('value'));

                if ($("form#selling_price_form").valid()) {
                    $("form#selling_price_form").submit();
                }
            });
            // $('#category_id').change(function() {
            //     this.form.submit();
            // });
        });
    </script>
@endsection
