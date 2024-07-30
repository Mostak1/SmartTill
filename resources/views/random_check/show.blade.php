<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
     
        </div>
        <div class="modal-body" id="printableArea">
            <h4 class="modal-title text-center">Random Check Details</h4>
            <br>
                        <div class="">
                            <ul class="list-unstyled" >
                                <li><strong>Check No:</strong> {{ $randomCheck->check_no }}</li>
                                <li><strong>Checked By:</strong> {{ $randomCheck->checkedBy->first_name }}
                                    {{ $randomCheck->checkedBy->last_name }}</li>
                                <li><strong>Checked At:</strong>
                                    {{ \Carbon\Carbon::parse($randomCheck->created_at)->format('d F Y, g:i A') }}</li>
                            </ul>
                            <p style="text-align: right">Generated at: {{ now()->format('d-m-Y, h:i A') }}</p>
                        </div>
            <!-- Random Check Details -->
            <div class="form-group">
                <table class="table table-bordered table-striped random-print-font">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Product Name</th>
                            <th>SKU</th>
                            <th>Brand Name</th>
                            <th>Soft. Count</th>
                            <th>Physical Count</th>
                            <th>Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($randomCheck->randomCheckDetails as $detail)
                            <tr>
                                <td>{{ $detail->product->category->name }}</td>
                                <td>{{ $detail->product->name }}</td>
                                <td>{{ $detail->product->sku }}</td>
                                <td>{{ $detail->brand_name }}</td>
                                <td>{{ $detail->current_stock }}</td>
                                <td>
                                    @php
                                        $physicalCount = number_format($detail->physical_count);
                                        if ($physicalCount > 0) {
                                            $formatted = "+{$physicalCount} (surplus)";
                                        } elseif ($physicalCount < 0) {
                                            $formatted = "{$physicalCount} (missing)";
                                        } else {
                                            $formatted = '0 (match)';
                                        }
                                    @endphp
                                    {{ $formatted }}
                                </td>
                                <td>{{ $detail->comment }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <!-- Overall Comment -->
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('comment', 'Overall Comment:') !!}
                        <p>{!! $randomCheck->comment !!}</p>
                    </div>

                </div>
                <div class="col-md-6 no-print">
                    <strong>{{ __('lang_v1.activities') }}:</strong><br>
                    @includeIf('activity_log.activities')
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-primary no-print" onclick="printDiv('printableArea')">Print</button>
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
