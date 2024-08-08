<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action([\App\Http\Controllers\CheckController::class, 'generateRandom']), 'method' => 'post', 'id' => 'random_check_form' ]) !!}
        {!! csrf_field() !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">Product Random Check</h4>
        </div>

        <div class="modal-body">
            <!-- Location Selector -->
            <div class="form-group row">
                <div class="col-md-6">
                {!! Form::label('random_check_filter_location_id',  __('purchase.business_location') . ':') !!}
                {!! Form::select('random_check_filter_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%' ]); !!}
                </div>
            </div>

            <!-- Category and Number of Products Inputs -->
            <div class="form-group row" style="border-bottom: 2px solid rgb(122, 120, 131);">
                <div class="col-md-6"><h4>Category</h4></div>
                <div class="col-md-6"><h4>Number of products</h4></div>
            </div>
            @foreach($parent_categories as $category_id => $category_name)
                <div class="form-group row">
                    <div class="col-md-6">
                        <input type="hidden" name="categories[{{ $loop->index }}][category_id]" value="{{ $category_id }}">
                        <label>{{ $category_name }}</label>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group input-number">
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default btn-flat quantity-down-int" data-index="{{ $loop->index }}">
                                    <i class="fa fa-minus text-danger"></i>
                                </button>
                            </span>
                            {!! Form::number('categories['.$loop->index.'][number_of_products]', 3, ['class' => 'form-control input_number', 'min' => 1]) !!}
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default btn-flat quantity-up-int" data-index="{{ $loop->index }}">
                                    <i class="fa fa-plus text-success"></i>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="modal-footer">
            <button type="submit" id="submitForm" class="btn btn-primary">@lang('messages.submit')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
        {!! Form::close() !!}
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->



<script>
    $(document).ready(function() {

        // Quantity adjustment buttons
        $('.quantity-down-int').click(function() {
            const index = $(this).data('index');
            let input = $(`input[name="categories[${index}][number_of_products]"]`);
            let value = parseInt(input.val());
            if (value > 1) {
                input.val(value - 1);
            } else {
                input.val(1);
            }
        });

        $('.quantity-up-int').click(function() {
            const index = $(this).data('index');
            let input = $(`input[name="categories[${index}][number_of_products]"]`);
            let value = parseInt(input.val());
            input.val(value + 1);
        });

    });
</script>
