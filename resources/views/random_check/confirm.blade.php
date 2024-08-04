@extends('layouts.app')

@section('content')
<section class="content">
    @component('components.widget')
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h3>Confirm Random Check <small><strong>Location: </strong>{{$location->name}}</small></h3>
        <div>
            <a href="{{ route('random_check.printA4', $randomCheckId) }}" class="btn btn-info btn-sm">Print(A4)</a>
            <a href="{{ route('random_check.printPOS', $randomCheckId) }}" class="btn btn-info btn-sm">Print(POS)</a>
        </div>
    </div>
    {!! Form::open([
        'url' => action([\App\Http\Controllers\ProductController::class, 'checkUpdate']),
        'method' => 'post',
    ]) !!}
    @csrf
    <input type="hidden" name="random_check_id" value="{{ $randomCheckId }}">
    <div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Category</th>
                <th>Brand Name</th>
                <th>SKU</th>
                <th>Product Name</th>
                <th>Current Stock</th>
                <th>Phy. Count Diff.</th>
                <th>Comment</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $id => $product)
                <tr>
                    <td>{{ $product['category_name'] }}</td>
                    <td>{{ !empty($product['brand_name']) ? $product['brand_name'] : 'No Brand' }}</td>
                    <td>{{ $product['sku'] }}</td>
                    <td>{{ $product['product_name'] }}</td>
                    <td>{{ number_format($product['current_stock'], 2) }}</td>
                    <td>
                        <div class="input-group input-number ">
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default btn-flat quantity-down-int" data-index="{{ $id }}">
                                    <i class="fa fa-minus text-danger"></i>
                                </button>
                            </span>
                            {!! Form::number("products[{$id}][physical_count]", 0, [
                                'class' => 'form-control input_number',
                                'required' => true,
                                'id' => "physical_count_{$id}",
                                'data-id' => $id
                            ]) !!}
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default btn-flat quantity-up-int" data-index="{{ $id }}">
                                    <i class="fa fa-plus text-success"></i>
                                </button>
                            </span>
                        </div>
                        <div style="text-align: center;">
                            <small id="physical_count_text_{{ $id }}" class="form-text "></small>
                        </div>
                    </td>
                    <td>
                        {!! Form::text("products[{$id}][comment]", null, ['class' => '']) !!}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    <div class="row">
        <div class="col-md-7">
            <ul>
                <li><strong>Checked by: </strong>{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</li>
                <li><strong>Checked at: </strong>{{ now()->format('d F Y, g:i A') }}</li>
            </ul>
        </div>
        <div class="col-md-5">
            {!! Form::textarea("comment", null, ['class' => 'form-control ', 'rows' => 3, 'placeholder' => 'Overall comments...']) !!}
        </div>
    </div>
    
    {!! Form::submit('Save', [
        'class' => 'btn btn-primary ',
        'style' => 'display: block; width: 160px; height: 50px; margin: 0 auto; margin-top:10px; font-size: 18px;',
    ]) !!}
    {!! Form::close() !!}
    @endcomponent
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        function updatePhysicalCountText(id, value) {
            const textElement = document.querySelector(#physical_count_text_${id});
            if (value === 0) {
                textElement.textContent = '0 (match)';
            } else if (value < 0) {
                textElement.textContent = ${value} (missing);
            } else if (value > 0) {
                textElement.textContent = +${value} (surplus);
            }
            else if (value == 0) {
                textElement.textContent = ${value} (match);
            }
        }

        document.querySelectorAll('.quantity-down-int').forEach(button => {
            button.addEventListener('click', function() {
                const index = this.getAttribute('data-index');
                const input = document.querySelector(#physical_count_${index});
                let value = parseInt(input.value);
                input.value = value - 1;
                updatePhysicalCountText(index, input.value);
            });
        });

        document.querySelectorAll('.quantity-up-int').forEach(button => {
            button.addEventListener('click', function() {
                const index = this.getAttribute('data-index');
                const input = document.querySelector(#physical_count_${index});
                let value = parseInt(input.value);
                input.value = value + 1;
                updatePhysicalCountText(index, input.value);
            });
        });

        // Initialize text elements
        document.querySelectorAll('.input_number').forEach(input => {
            const id = input.getAttribute('data-id');
            const value = parseInt(input.value);
            updatePhysicalCountText(id, value);
        });
    });
</script>
@endsection