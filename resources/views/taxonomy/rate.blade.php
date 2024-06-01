<div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title text-center">Category Rate History</h4>
      </div>
  
      <div class="modal-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Updated At</th>
                    <th>Rate</th>
                    <th>Updated By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($PriceHistory as $history)
                    <tr>
                        <td>{{ Carbon::parse($history->updated_at)->format('d-m-Y, h:i A') }}</td>
                        <td>{{ number_format($history->new_price, 2) }}</td>
                        <td>{{ \App\User::find($history->updated_by)->first_name }} {{ \App\User::find($history->updated_by)->last_name }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No rate history available for this Category.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
      </div>
  
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
      </div>

    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->