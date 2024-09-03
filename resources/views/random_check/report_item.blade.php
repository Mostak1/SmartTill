{{-- resources/views/random_check/report_item.blade.php --}}
<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content" id="printableArea">
        <div class="modal-header">
            <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title text-center">Report Item Details</h4>
        </div>
        <div class="modal-body">
            <p style="text-align: right">Generated at: {{ now()->format('d-m-Y, h:i A') }}</p>
            <p><strong>Location:</strong> {{ $location->name }}</p>
            <p><strong>Report:</strong> {{ $report->report_no }}</p>
            <h3>Missing Items</h3>
            <table class="table table-bordered table-striped print-font">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Product Name</th>
                        <th>SKU</th>
                        <th>Brand Name</th>
                        <th>Missing Qty</th>
                        <th>Subtotal @show_tooltip(__('Subtotal (Sell Price)'))</th>
                        <th>Comment</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($missingItems as $item)
                        <tr>
                            <td>{{ $item->category_name }}</td>
                            <td>{{ $item->product_name }}</td>
                            <td class="break-after-6">{{ $item->sku }}</td>
                            <td>{{ $item->brand_name }}</td>
                            <td>{{ abs($item->quantity) }}</td>
                            <td>৳ {{ number_format(abs($item->quantity) * $item->subtotal, 2) }}</td>
                            <td>{{ $item->comment }}</td>
                            <td>{{ $item->created_at->format('d M Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-right"><strong>Total Missing:</strong></td>
                        <td><b>৳ {{ number_format($totalMissingSellPrice, 2) }}</b></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>

            <h3>Surplus Items</h3>
            <table class="table table-bordered table-striped print-font">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Product Name</th>
                        <th>SKU</th>
                        <th>Brand Name</th>
                        <th>Surplus Qty</th>
                        <th>Subtotal @show_tooltip(__('Subtotal (Sell Price)'))</th>
                        <th>Comment</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($surplusItems as $item)
                        <tr>
                            <td>{{ $item->category_name }}</td>
                            <td>{{ $item->product_name }}</td>
                            <td class="break-after-6">{{ $item->sku }}</td>
                            <td>{{ $item->brand_name }}</td>
                            <td>{{ number_format($item->quantity) }}</td>
                            <td>৳ {{ number_format($item->quantity * $item->subtotal, 2) }}</td>
                            <td>{{ $item->comment }}</td>
                            <td>{{ $item->created_at->format('d M Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-right"><strong>Total Surplus:</strong></td>
                        <td><b>৳ {{ number_format($totalSurplusSellPrice, 2) }}</b></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>

            <h3 class="text-center">Net Result: ৳ {{ number_format(abs($netResult), 2) }} 
                <span class="{{ $resultStatus == 'Loss' ? 'text-danger' : 'text-success' }}">
                    ({{ $resultStatus }})
                </span>
            </h3>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-info no-print btn-sm" onclick="printDiv('printableArea')">Print(A4)</button>
            <button type="button" class="btn btn-default no-print" data-dismiss="modal">Close</button>
        </div>
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>
    function printDiv(divName) {
        var printContents = document.getElementById(divName).innerHTML;
        var originalContents = document.body.innerHTML;

        document.body.innerHTML = printContents;

        window.print();

        document.body.innerHTML = originalContents;
        window.location.reload(); // Reload the page to reset the modal state
    }
</script>
