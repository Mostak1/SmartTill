<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        {!! Form::open([
            'url' => action([\App\Http\Controllers\CheckController::class, 'checkDetailUpdate'], $randomCheck->id),
            'method' => 'post',
        ]) !!}
        {!! csrf_field() !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3>Edit Random Check <small><strong>Location: </strong>{{ $location->name }}</small></h3>
                <div>
                    <a href="{{ route('random_check.printA4', $randomCheck->id) }}" class="btn btn-info btn-sm">Print(A4)</a>
                    <a href="{{ route('random_check.printPOS', $randomCheck->id) }}" style="margin-right: 15px;" class="btn btn-info btn-sm">Print(POS)</a>
                </div>
            </div>
        </div>
        <div class="modal-body" id="printableArea">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Product Name</th>
                            <th>SKU</th>
                            <th>Brand Name</th>
                            <th>Soft. Count</th>
                            <th style="width: 15%;">Phy. Count Diff.</th>
                            <th style="width: 20%;">Comment</th>
                            @if(session('business.enable_lot_number'))
                            <th style="width: 15%;">Lot Number</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($randomCheck->randomCheckDetails as $detail)
                            <tr>
                                <td>{{ $detail->product->category->name }}</td>
                                <td>{{ $detail->product->name }}</td>
                                <td class="break-after-6">{{ $detail->product->sku }}</td>
                                <td>{{ $detail->brand_name }}</td>
                                <td>{{ $detail->current_stock }}</td>
                                <td>
                                    <div class="input-group input-number">
                                        <span class="input-group-btn">
                                            <button type="button" class="btn btn-default btn-flat quantity-down-int" data-index="{{ $detail->id }}">
                                                <i class="fa fa-minus text-danger"></i>
                                            </button>
                                        </span>
                                        {!! Form::number("details[{$detail->id}][physical_count]", number_format($detail->physical_count), [
                                            'class' => 'form-control input_number',
                                            'required' => true,
                                            'id' => "physical_count_{$detail->id}",
                                            'data-id' => $detail->id
                                        ]) !!}
                                        <span class="input-group-btn">
                                            <button type="button" class="btn btn-default btn-flat quantity-up-int" data-index="{{ $detail->id }}">
                                                <i class="fa fa-plus text-success"></i>
                                            </button>
                                        </span>
                                    </div>
                                    <div style="text-align: center;">
                                        <small id="physical_count_text_{{ $detail->id }}" class="form-text "></small>
                                    </div>
                                </td>
                                <td>
                                    {!! Form::text("details[{$detail->id}][comment]", $detail->comment, ['class' => 'form-control ']) !!}
                                </td>
                                @if(session('business.enable_lot_number'))
                                <td>
                                    <!-- Lot Number Dropdown -->
                                    {!! Form::select(
                                        "details[{$detail->id}][lot_number]",
                                        $detail->product->purchase_lines->pluck('lot_number', 'lot_number')->map(function($lot_number) {
                                            return $lot_number ?: '-';
                                        }),
                                        $detail->lot_number,
                                        ['class' => 'form-control lot-number-select', 'placeholder' => 'Select Lot', 'disabled' => 'disabled']
                                    ) !!}
                                </td>
                                @endif                                
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="row">
                <div class="col-md-6"></div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('comment', 'Comment:') !!}
                        {!! Form::textarea('comment', strip_tags($randomCheck->comment), ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Overall comments...']) !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Save</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
        {!! Form::close() !!}
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>
    $(document).ready(function() {
        function updatePhysicalCountText(id, value) {
            var textElement = $('#physical_count_text_' + id);
            var lotNumberSelect = $("select[name='details[" + id + "][lot_number]']");

            if (value === 0) {
                textElement.text('0 (match)');
                lotNumberSelect.prop('disabled', true);
                lotNumberSelect.show();
            } else if (value < 0) {
                textElement.text(value + ' (missing)');
                lotNumberSelect.prop('disabled', false);
                lotNumberSelect.show();
            } else if (value > 0) {
                textElement.text('+' + value + ' (surplus)');
                lotNumberSelect.prop('disabled', false);
                lotNumberSelect.show();
            }
        }

        $('.quantity-down-int').on('click', function() {
            var index = $(this).data('index');
            var input = $('#physical_count_' + index);
            var value = parseInt(input.val()) || 0; // Ensure value is an integer
            input.val(value - 1);
            updatePhysicalCountText(index, value - 1);
        });

        $('.quantity-up-int').on('click', function() {
            var index = $(this).data('index');
            var input = $('#physical_count_' + index);
            var value = parseInt(input.val()) || 0; // Ensure value is an integer
            input.val(value + 1);
            updatePhysicalCountText(index, value + 1);
        });

        // Initialize text elements
        $('.input_number').each(function() {
            var id = $(this).data('id');
            var value = parseInt($(this).val()) || 0; // Ensure value is an integer
            updatePhysicalCountText(id, value);
        });
    });
</script>