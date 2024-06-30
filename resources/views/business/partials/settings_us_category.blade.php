<!--Purchase related settings -->
<div class="pos-tab-content">
    <div class="row">
        <div class="col-sm-12">
            <h4>Foreign Category List</h4>
        </div>
        <div class="col-xs-12">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>@lang('Category Name')</th>
                            <th>@lang('Short Code')</th>
                            <th>@lang('Description')</th>
                            <th>@lang('Is Foreign')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            @if ($category->is_us_product == 1)
                                <tr>
                                    <td>{{ $category->name }}
                                        {!! Form::hidden('categories[' . $category->id . '][name]', $category->name) !!}
                                    </td>
                                    <td>{{ $category->short_code }}</td>
                                    <td>{{ $category->description }}</td>
                                    <td>
                                        <div class="form-group">
                                            <div class="checkbox">
                                                <label>
                                                    {!! Form::checkbox('categories[' . $category->id . '][is_us_product]', 1, $category->is_us_product, ['class' => 'input-icheck']) !!}
                                                </label>
                                            </div>                                            
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <p>Foreign Category not available in the list</p>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-sm-12">
            <h4>Create a New Foreign Category</h4>
        </div>
        <div class="col-xs-12">
            <div class="form-group col-md-3">
                <label for="category_id">@lang('Category Name')</label>
                {!! Form::select('category_id', $categories->pluck('name', 'id'), null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'style' => 'width: 100%;']) !!}
            </div>
            <div class="form-group col-md-3">
                <label for="short_code">@lang('Short Code')</label>
                {!! Form::text('short_code', null, ['class' => 'form-control', 'placeholder' => __('Short code')]) !!}
            </div>
            <div class="form-group col-md-3">
                <label for="description">@lang('Description')</label>
                {!! Form::number('description', null, ['class' => 'form-control', 'placeholder' => __('Currency Rate')]) !!}
            </div>
            <div class="form-group col-md-3">
                <label for="is_us_product">@lang('Is Foreign')</label>
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('is_us_product', 1, false, ['class' => 'input-icheck', 'id' => 'is_foreign']) !!}
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>