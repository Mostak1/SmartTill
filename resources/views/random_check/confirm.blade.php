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
        'url' => action([\App\Http\Controllers\CheckController::class, 'checkUpdate']),
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
                <th style="width: 10%;">Phy. Count Diff.</th>
                <th style="width: 25%;">Comment</th>
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
                        <div class="input-group input-number">
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
                            <small id="physical_count_text_{{ $id }}" class="form-text"></small>
                        </div>
                    </td>
                    <td>
                        <span class="comment-placeholder" style="cursor: pointer; color: blue;">Click to add comment...</span>
                        {!! Form::textarea("products[{$id}][comment]", null, [
                            'class' => 'form-control comment-textarea hide',
                            'rows' => 2,
                            'placeholder' => 'Comments...'
                        ]) !!}
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
            {!! Form::textarea("comment", null, [
                'class' => 'form-control',
                'rows' => 3,
                'placeholder' => 'Overall comments...'
            ]) !!}
        </div>
    </div>
    
    {!! Form::submit('Save', [
        'class' => 'btn btn-primary',
        'style' => 'display: block; width: 160px; height: 50px; margin: 0 auto; margin-top: 10px; font-size: 18px;',
    ]) !!}
    {!! Form::close() !!}
    @endcomponent
</section>
@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        function updatePhysicalCountText(id, value) {
            const textElement = $(`#physical_count_text_${id}`);
            if (value === 0) {
                textElement.text('0 (match)');
            } else if (value < 0) {
                textElement.text(`${value} (missing)`);
            } else if (value > 0) {
                textElement.text(`+${value} (surplus)`);
            }
        }

        $('.quantity-down-int').on('click', function() {
            const index = $(this).data('index');
            const input = $(`#physical_count_${index}`);
            let value = parseInt(input.val()) || 0; // Ensure value is an integer
            input.val(value - 1);
            updatePhysicalCountText(index, parseInt(input.val()));
        });

        $('.quantity-up-int').on('click', function() {
            const index = $(this).data('index');
            const input = $(`#physical_count_${index}`);
            let value = parseInt(input.val()) || 0; // Ensure value is an integer
            input.val(value + 1);
            updatePhysicalCountText(index, parseInt(input.val()));
        });

        // Initialize text elements
        $('.input_number').each(function() {
            const id = $(this).data('id');
            const value = parseInt($(this).val()) || 0; // Ensure value is an integer
            updatePhysicalCountText(id, value);
        });

        $('.comment-placeholder').on('click', function() {
            $(this).hide();
            $(this).next('.comment-textarea').removeClass('hide').focus();
        });
    });
</script>
@endsection
