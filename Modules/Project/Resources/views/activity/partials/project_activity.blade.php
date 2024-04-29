<table class="table table-hover">
	<caption>
		@lang('project::lang.updated_values')
	</caption>
    <thead>
    	<tr>
    		<th class="col-md-2">
    			@lang('project::lang.field')
    		</th>
    		<th class="col-md-5">
    			@lang('project::lang.old_value')
    		</th>
    		<th class="col-md-5">
    			@lang('project::lang.new_value')
    		</th>
        </tr>
    </thead>
    <tbody>
    	@foreach($activity->properties['attributes'] as $key => $value)
    	<tr>
    		@if($key == 'name' || $key == 'start_date' || $key == 'end_date' || $key == 'status' || $key == 'description')
				<td>
					{{$label [$key]}}
				</td>
				<td>
					@if($key == 'name' || $key == 'description')

						{!! $activity->properties['old'][$key] !!}

					@elseif($key == 'start_date' || $key == 'end_date')

						{{@format_date($activity->properties['old'][$key])}}

					@elseif($key == 'status')

						{{$status_and_priority[$activity->properties['old'][$key]]}}

					@endif
				</td>
				<td>
					@if($key == 'name' || $key == 'description')

						{!! $value !!}

					@elseif($key == 'start_date' || $key == 'end_date')

						{{@format_date($value)}}

					@elseif($key == 'status')
					
						{{$status_and_priority[$value]}}

					@endif
				</td>
			@endif
		</tr>	
		@endforeach
    </tbody>
</table>
{{-- {"attributes":{"id":194,"business_id":1,"project_id":36,"task_id":"#1","subject":"Menu becomes very small for one search result in purchase product list","start_date":null,"due_date":null,"priority":"low","description":null,"created_by":1,"status":"not_started","custom_field_1":null,"custom_field_2":null,"custom_field_3":null,"custom_field_4":null,"created_at":"2024-04-28T10:51:20.000000Z","updated_at":"2024-04-28T10:51:20.000000Z"}} 

{"from":{"task_view":"list_view","enable_timelog":1,"enable_notes_documents":1,"enable_invoice":1,"enable_archive":"archive","members_crud_task":0,"members_crud_note":0,"members_crud_timelog":0,"task_id_prefix":"#","not_started":{"id":1,"name":"New Topic"},"in_progress":{"id":1,"name":"Working Topic"},"on_hold":{"id":1,"name":"On Hold"},"cancelled":{"id":0,"name":"Cancelled"},"completed":{"id":1,"name":"Completed"}},"to":{"task_view":"list_view","enable_timelog":1,"enable_notes_documents":1,"enable_invoice":1,"enable_archive":"archive","members_crud_task":0,"members_crud_note":0,"members_crud_timelog":0,"task_id_prefix":"#","not_started":{"id":1,"name":"New Topic"},"in_progress":{"id":1,"name":"Working Topic"},"on_hold":{"id":1,"name":"On Hold"},"cancelled":{"id":0,"name":"Cancelled"},"completed":{"id":0,"name":"Completed"}}}


--}}