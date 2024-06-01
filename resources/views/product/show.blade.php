<div class="row">
  <div class="col-md-10 col-md-offset-1 col-xs-12">


    <div class="table-responsive">
      <table class="table table-bordered">
          <thead>
              <tr>
                  <th>Old Price</th>
                  <th>New Price</th>
                  <th>Updated By</th>
                  <th>Updated At</th>
              </tr>
          </thead>
          <tbody>
            @foreach($PriceHistory as $history)
            <tr>
                <td>{{ $history->old_price }}</td>
                <td>{{ $history->new_price }}</td>
                <td>{{ $history->updated_by }}</td>
                <td>{{ $history->updated_at }}</td>
            </tr>
            @endforeach
        </tbody>
      </table>
    </div>

    <div class="table-responsive">
      <table class="table table-condensed bg-gray">
        <tr>
          <th>@lang('business.location')</th>
          <th>@lang('lang_v1.rack')</th>
          <th>@lang('lang_v1.row')</th>
          <th>@lang('lang_v1.position')</th>
        </tr>
        @if(!empty($details[0]))
          @foreach( $details as $detail )
            <tr>
              <td>{{ $detail->name}}</td>
              <td>
                {{ $detail->rack }}
              </td>
              <td>
                {{ $detail->row }}
              </td>
              <td>
                {{ $detail->position }}
              </td>
            </tr>
          @endforeach
        @else
          <tr>
            <td colspan="4" class="text-center">
              -
            </td>
          </tr>
        @endif
        
      </table>
    </div>
  </div>
</div>