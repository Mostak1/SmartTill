<!-- resources/views/purchase_requisition/order_content.blade.php -->
@foreach ($requisitionDetails as $detail)
    <tr>
        <td>
            <input type="hidden" name="product_id" value="{{ $detail->product_id }}" />
            {{ $detail->product_name }}
        </td>
        <td>{{ $detail->sku }}</td>
        <td>{{ $detail->brand }}</td>
        <td>{{ $detail->category }}</td>
        <td>{{ number_format($detail->current_stock) }}</td>
        <td>{{ number_format($detail->total_units_sold_last_30_days) }}</td>
        <td>
            <input type="number" name="quantity" class="form-control" value="{{ $detail->suggested_order }}">
        </td>
        <td>
            {!! Form::select('supplier_id', $suppliers, $detail->suggested_supplier_id, [
                'class' => 'form-control',
                'placeholder' => 'Select Supplier'
            ]) !!}
        </td>
        <td>
            <input type="hidden" name="variation_id" value="{{ $detail->variation_id }}" />
            <button type="button" class="btn btn-danger btn-remove-row btn-sm">
                <i class="fa fa-times" style="font-size: 12px; cursor: pointer;"></i>
            </button>
        </td>
    </tr>
@endforeach
