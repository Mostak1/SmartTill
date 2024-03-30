<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action([\App\Http\Controllers\SubCategoryController::class, 'update'], [$subcategory->id]), 'method' => 'PUT', 'id' => $quick_add ? 'quick_add_brand_form' : 'brand_add_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="mclose close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'subcategory.add_subcategory' )</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('name', __( 'subcategory.subcategory_name' ) . ':*') !!}
          {!! Form::text('name', $subcategory->name, ['class' => 'form-control', 'required', 'placeholder' => __( 'subcategory.subcategory_name' ) ]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('category_id',__('subcategory.category') . ':*') !!}
        {!! Form::select('category_id', $categories, $subcategory->category_id, ['class' => 'form-control', 'required', 'placeholder' =>__('subcategory.category')]) !!}

      </div>
      {{-- <div class="form-group">
        {!! Form::label('description', __( 'subcategory.short_description' ) . ':') !!}
          {!! Form::text('description', null, ['class' => 'form-control','placeholder' => __( 'subcategory.short_description' )]); !!}
      </div> --}}

        @if($is_repair_installed)
          <div class="form-group">
             <label>
                {!!Form::checkbox('use_for_repair', 1, false, ['class' => 'input-icheck']) !!}
                {{ __( 'repair::lang.use_for_repair' )}}
            </label>
            @show_tooltip(__('repair::lang.use_for_repair_help_text'))
          </div>
        @endif

    </div>

    <div class="modal-footer">
      <button type="submit" class="btn save btn-primary">@lang( 'messages.update' )</button>
      <button type="button" class="mclose btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->