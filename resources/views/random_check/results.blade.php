@extends('layouts.app')

@section('content')
<section class="content">
        @component('components.widget')
        <div style="display: flex; align-items: center; margin-bottom: 1rem;">
            <h3 style="margin-right: 1rem;">Random Check Results  <small><strong>Location: </strong>{{$location->name}}</small></h3>
            <input type="hidden" name="location_id" value="{{ $location->id }}">
            <button id="refresh-btn" style="margin-left: auto;" class="btn btn-primary">Refresh</button>
        </div>
        <div id="random-products" class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Product Name</th>
                        <th>SKU</th>
                        <th>Brand Name</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories_products as $category_id => $products)
                        @foreach($products as $product)
                            <tr>
                                <td>{{ $product->category_name }}</td>
                                <td>{{ $product->product }}</td>
                                <td>{{ $product->sku }}</td>
                                <td>{{ $product->brand }}</td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="4">No products found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {!! Form::open(['url' => action([\App\Http\Controllers\CheckController::class, 'checkConfirm']), 'method' => 'post',
    'id' => 'random_check_form']) !!}
            @csrf
            @foreach($categories_products as $category_id => $products)
                @foreach($products as $product)
                    {!! Form::hidden("products[{$product->id}][location_id]", $location->id) !!}
                    {!! Form::hidden("products[{$product->id}][category_name]", $product->category_name) !!}
                    {!! Form::hidden("products[{$product->id}][product_name]", $product->product) !!}
                    {!! Form::hidden("products[{$product->id}][sku]", $product->sku) !!}
                    {!! Form::hidden("products[{$product->id}][brand_name]", $product->brand) !!}
                    {!! Form::hidden("products[{$product->id}][current_stock]", $product->current_stock) !!}
                    {!! Form::hidden("products[{$product->id}][variation_id]", $product->variation_id) !!}
                @endforeach
            @endforeach
            {!! Form::submit('Confirm', ['id' => 'confirm-btn', 'class' => 'btn btn-primary', 'style' => 'display: block; width: 160px; height: 50px; margin: 0 auto; font-size: 18px;']) !!}
        {!! Form::close() !!}
        @endcomponent
</section>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    $('#refresh-btn').on('click', function() {
        location.reload();
    });
});
</script>
@endsection
