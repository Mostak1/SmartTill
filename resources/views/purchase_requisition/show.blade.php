<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title text-center" id="modalTitle">
                @lang('lang_v1.purchase_requisition_details')
            </h4>
        </div>
        <div class="modal-body">
            <!-- Display Purchase Requisition Details -->
            <div class="row">
                <div class="col-md-3">
                    <strong>Requisition no.:</strong> {{ $purchaseRequisition->requisition_no }} <br>
                    <strong>@lang('business.location'):</strong> {{ $location_name }}
                </div>
                <div class="col-md-6"></div>
                <div class="col-md-3">
                    <strong>Requisition by:</strong> {{ $requisition_by_name }} <br>
                    <strong>Requisition Date:</strong> {{ $requisition_date }}
                </div>
            </div>

            <!-- Purchase Requisition Lines Table -->
            <div class="row mt-5">
                <div class="col-md-12">
                    <table class="table bg-gray">
                        <thead>
                            <tr class="bg-green">
                                <th>Product Name</th>
                                <th>SKU</th>
                                <th>Brand</th>
                                <th>Category</th>
                                <th>Current Stock</th>
                                <th>Units Sold @show_tooltip('Total Units Sold within Last 30 Days')</th>
                                <th>Suggested Order</th>
                                <th>Suggested Supplier</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($purchaseLines as $line)
                                <tr>
                                    <td>
                                        {{ $line->product->name }}
                                        @if($line->product->type == 'single')
                                            ({{ $line->product->sku }})
                                        @else
                                            - {{ $line->variations->product_variation->name }} - {{ $line->variations->name }} ({{ $line->variations->sub_sku }})
                                        @endif
                                    </td>
                                    <td>{{ $line->product->sku }}</td>
                                    <td>{{ $line->product->brand->name ?? '' }}</td>
                                    <td>{{ $line->product->category->name ?? '' }}</td>
                                    <td>{{ number_format($line->current_stock) }}</td>
                                    <td>{{ number_format($line->products_sold) }}</td>
                                    <td>{{ number_format($line->quantity) }} {{ $line->product->unit->short_name }}</td>
                                    <td>{{ $line->last_supplier }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary no-print" onclick="window.print();">@lang('messages.print')</button>
            <button type="button" class="btn btn-default no-print" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>