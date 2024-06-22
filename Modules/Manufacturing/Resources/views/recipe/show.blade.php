<div class="modal-dialog" role="document">
	<div class="modal-content">
		<div class="modal-header">
		    <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		      <h4 class="modal-title" id="modalTitle">{{$recipe->variation->full_name}}</h4>
	    </div>
	    <div class="modal-body">
	    	<div class="row">
      			<div class="col-md-6">
      				{!! $recipe->variation->product->product_description !!}
      			</div>
      			<div class="col-md-6">
      				@foreach($recipe->variation->media as $media)
			        	{!! $media->thumbnail([60, 60], 'img-thumbnail') !!}
			        @endforeach
      			</div>
      		</div>
      		<div class="row">
      			<div class="col-md-12">
      				<table class="table">
						<thead>
							<tr>
								<th>@lang('manufacturing::lang.ingredient')</th>
								<th>@lang('lang_v1.quantity')</th>
								<th>@lang('manufacturing::lang.waste_percent')</th>
								<th>Final Quantity</th>
								<th>@lang('lang_v1.price')</th>
							</tr>
						</thead>
						<tbody>
							@php
								$ingredient_groups = [];
								$ingredient_total_price = 0;
							@endphp
							@foreach($ingredients as $ingredient)
								@php
									$ingredient_price = $ingredient['quantity']*$ingredient['dpp_inc_tax']*$ingredient['multiplier'];
									$ingredient_total_price += $ingredient_price;
								@endphp
								@if(empty($ingredient['mfg_ingredient_group_id']))
									<tr>
										<td>
											{{$ingredient['full_name']}}
										</td>
										<td><span>{{ number_format($ingredient['quantity'], 3) }}</span> {{$ingredient['unit']}}</td>
										<td><span>{{ number_format($ingredient['waste_percent'], 3) }}</span>%</td>
										<td>
											<span>
												{{ number_format(($ingredient['quantity'] * (100 - $ingredient['waste_percent']) / 100), 3) }}
											</span> {{$ingredient['unit']}}
										</td>
										<td><span class="display_currency" data-currency_symbol="true">{{$ingredient_price}}</span></td>
									</tr>
								@else
									@php
										$ingredient_groups[$ingredient['mfg_ingredient_group_id']][] = $ingredient;
									@endphp
								@endif	
							@endforeach
							@foreach($ingredient_groups as $ingredient_group)
								<tr>
									<td colspan="4" class="bg-gray"><strong>{{$ingredient_group[0]['ingredient_group_name'] ?? ''}}</strong> @if(!empty($ingredient_group[0]['ig_description']))
									- {{$ingredient_group[0]['ig_description']}}
								@endif</td>
								</tr>
								
								@foreach($ingredient_group as $ingredient)
									<tr>
										<td>
											{{$ingredient['full_name']}}
										</td>
										<td><span>{{ number_format($ingredient['quantity'], 3)}}</span> {{$ingredient['unit']}}</td>
										<td><span>{{ number_format($ingredient['waste_percent'], 3)}}</span>%</td>
										<td><span class="display_currency" data-currency_symbol="true">{{$ingredient['quantity']*$ingredient['dpp_inc_tax']*$ingredient['multiplier']}}</span></td>
									</tr>
								@endforeach
							@endforeach
						</tbody>
						<tfoot>
							<tr>
								<td colspan="3" class="text-right"><strong>@lang('manufacturing::lang.ingredients_cost')</strong></td>
								<td><span class="display_currency" data-currency_symbol="true">{{$ingredient_total_price}}</span></td>
							</tr>
						</tfoot>
					</table>
      			</div>
      		</div>
      		<div class="row">
      			<div class="col-md-6">
      				<strong>@lang('manufacturing::lang.wastage'):</strong>
      				{{ number_format($recipe->waste_percent, 3) ?? 0}} % <br>
      				<strong>@lang('manufacturing::lang.total_output_quantity'):</strong>
      				@if(!empty($recipe->total_quantity)){{number_format($recipe->total_quantity)}}@else 0 @endif @if(!empty($recipe->sub_unit)) {{$recipe->sub_unit->short_name}} @else {{$recipe->variation->product->unit->short_name}} @endif
      			</div>
      			<div class="col-md-6">
      				<strong>@lang('manufacturing::lang.extra_cost'):</strong>
      				<span ></span>{{@num_format($recipe->extra_cost)}} @if($recipe->production_cost_type == 'percentage') % @elseif ($recipe->production_cost_type == 'per_unit') (@lang('manufacturing::lang.per_unit')) @endif <br>
      				<strong>@lang('sale.total'):</strong>
      				<span class="display_currency" data-currency_symbol="true">{{ number_format($recipe->final_price, 2) }}</span>
      			</div>
      		</div>
      		<div class="row">
      			<div class="col-md-12">
      				<strong>@lang('lang_v1.instructions'):</strong><br>
      				{!! $recipe->instructions !!}
      			</div>
      		</div>
			  <div class="row">
				<div class="col-md-12">
					<h4 style="padding: 7px 0;" class="text-center bg-green"><b>Recipe Price History</b></h4>
					<table class="table table-bordered table-striped">
						<thead>
							<tr>
								<th>Type</th>
								<th>Price</th>
								<th>Updated By</th>
								<th>Updated At</th>
							</tr>
						</thead>
						<tbody>
							@forelse($PriceHistory as $history)
								<tr>
									<td>{{$history->h_type}}</td>
									<td>{{ number_format($history->new_price, 2) }}</td>
									<td>{{ \App\User::find($history->updated_by)->first_name }} {{ \App\User::find($history->updated_by)->last_name }}</td>
									<td>{{ Carbon::parse($history->updated_at)->format('d-m-Y, h:i A') }}</td>
								</tr>
							@empty
								<tr>
									<td colspan="4">No price history available for this Recipe.</td>
								</tr>
							@endforelse
						</tbody>
					</table>
				</div>
			</div>
      	</div>
      	<div class="modal-footer">
      		<button type="button" class="btn btn-primary no-print" aria-label="Print" 
			      onclick="$(this).closest('div.modal-content').printThis();"><i class="fa fa-print"></i> @lang( 'messages.print' )
			</button>
	      	<button type="button" class="btn btn-default no-print" data-dismiss="modal">@lang( 'messages.close' )</button>
	    </div>
	</div>
</div>
