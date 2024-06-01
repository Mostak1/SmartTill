<div class="row">
    <div class="col-md-7">
        <div class="row">
            <div class="col-md-3">
                <div class="box box-solid box-warning">
                    <div class="box-header with-border">
                        <h4 class="box-title">
                            @lang('project::lang.incompleted_tasks')
                        </h4>
                        <!-- /.box-tools -->
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body text-center">
                        <span class="fs-20">
                            <b>{{ $project->incomplete_task }}</b>
                        </span>
                    </div>
                    <!-- /.box-body -->
                </div>
                <!-- /.box -->
            </div>
            @if (isset($project->settings['enable_notes_documents']) && $project->settings['enable_notes_documents'])
                <div class="col-md-3">
                    <div class="box box-solid box-primary">
                        <div class="box-header with-border">
                            <h4 class="box-title">
                                @lang('project::lang.documents_and_notes')
                            </h4>
                            <!-- /.box-tools -->
                        </div>
                        <!-- /.box-header -->
                        <div class="box-body text-center">
                            <span class="fs-20">
                                <b>{{ $project->note_and_documents_count }}</b>
                            </span>
                        </div>
                        <!-- /.box-body -->
                    </div>
                    <!-- /.box -->
                </div>
            @endif
            @if (isset($project->settings['enable_timelog']) && $project->settings['enable_timelog'])
                <div class="col-md-3">
                    <div class="box box-solid box-info">
                        <div class="box-header with-border">
                            <h4 class="box-title">
                                @lang('project::lang.total_time')
                            </h4>
                            <!-- /.box-tools -->
                        </div>
                        <!-- /.box-header -->
                        <div class="box-body text-center">
                            @php
                                $hours = floor($timelog->total_seconds / 3600);
                                $minutes = floor(($timelog->total_seconds / 60) % 60);
                            @endphp
                            <span>
                                <b>
                                    {{ sprintf('%02d:%02d', $hours, $minutes) }}
                                </b>
                            </span>
                        </div>
                        <!-- /.box-body -->
                    </div>
                    <!-- /.box -->
                </div>
            @endif
            @if (isset($project->settings['enable_invoice']) && $project->settings['enable_invoice'] && $is_lead_or_admin)
                <div class="col-md-3">
                    <div class="box box-solid box-success">
                        <div class="box-header with-border">
                            <h4 class="box-title">
                                @lang('sale.total_paid')
                                <small class="text-white">
                                    @lang('project::lang.invoice')
                                </small>
                            </h4>
                            <!-- /.box-tools -->
                        </div>
                        <!-- /.box-header -->
                        <div class="box-body text-center">
                            <span>
                                <b>
                                    <span class="subtotal display_currency" data-currency_symbol="true">
                                        {{ $invoice->paid }}
                                    </span>
                                </b>
                            </span>
                        </div>
                        <!-- /.box-body -->
                    </div>
                    <!-- /.box -->
                </div>
            @endif
        </div>
        <div class="row">
            @if (isset($project->settings['enable_invoice']) && $project->settings['enable_invoice'] && $is_lead_or_admin)
                <div class="col-md-3">
                    <div class="box box-solid box-danger">
                        <div class="box-header with-border">
                            <h4 class="box-title">
                                @lang('sale.total_remaining')
                                <small class="text-white">
                                    @lang('project::lang.invoice')
                                </small>
                            </h4>
                            <!-- /.box-tools -->
                        </div>
                        <!-- /.box-header -->
                        <div class="box-body text-center">
                            <span>
                                <b>
                                    <span class="subtotal display_currency" data-currency_symbol="true">
                                        {{ $transaction->total - $invoice->paid }}
                                    </span>
                                </b>
                            </span>
                        </div>
                        <!-- /.box-body -->
                    </div>
                    <!-- /.box -->
                </div>
            @endif
        </div>
        @if (!empty($project->description))
            <div class="row">
                <div class="col-md-12">
                    <div class="box box-solid">
                        <div class="box-body">
                            {!! $project->description !!}
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
    <div class="col-md-5">
        <!-- customer -->
        <div class="box box-solid box-default">
            <div class="box-header with-border">
                <h4 class="box-title">
                    <i class="fas fa-check-circle"></i>
                    {{ ucFirst($project->name) }}
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <a title="Edit" data-href="{{action([\Modules\Project\Http\Controllers\ProjectController::class, 'edit'], [$project->id])}}" class="cursor-pointer edit_a_project">
                        <i class="fa fa-edit"></i>
                    </a>
                    <a title="Archive" data-redirect="{{action([\Modules\Project\Http\Controllers\ProjectController::class, 'index']) . '?project_view=list_view'}}" data-href="{{action([\Modules\Project\Http\Controllers\ProjectController::class, 'destroy'], [$project->id])}}" class="cursor-pointer delete_a_project">
                        <i class="fas fa-file-archive"></i>
                    </a>
                    
                </h4>
            </div>
            <div class="box-body">
                @if (isset($project->customer->name))
                    <i class="fa fa-briefcase"></i>
                    {{ $project->customer->name }}
                @endif <br>

                @if (isset($project->customer->mobile))
                    <i class="fa fa-mobile"></i>
                    @lang('contact.mobile'): {{ $project->customer->mobile }}
                @endif <br>

                <i class="fas fa-user-tie"></i>
                <strong>Lead: </strong>{{ \App\User::find($project->lead_id)->first_name }} {{ \App\User::find($project->lead_id)->last_name }} <br>
                <i class="fas fa-flag"></i>
                <strong>Creator: </strong>{{ \App\User::find($project->created_by)->first_name }} {{ \App\User::find($project->created_by)->last_name }}
                @if (isset($project->customer->landmark))
                    {{ $project->customer->landmark }}
                @endif

                @if (isset($project->customer->city))
                    {{ ', ' . $project->customer->city }}
                @endif

                @if (isset($project->customer->state))
                    {{ ', ' . $project->customer->state }}
                @endif
                @if (isset($project->customer->country))
                    {{ ', ' . $project->customer->country }}
                @endif
                <br>

                <i class="fas fa-check-circle"></i>
                @lang('sale.status'):
                @lang('project::lang.' . $project->status)

                @if ($project->categories->count() > 0)
                    <br>
                    <i class="fa fas fa-gem"></i>
                    @lang('category.categories'):
                    <span>
                        @foreach ($project->categories as $categories)
                            @if (!$loop->last)
                                {{ $categories->name . ',' }}
                            @else
                                {{ $categories->name }}
                            @endif
                        @endforeach
                    </span>
                @endif
            </div>
            <!-- /.box-body -->
            <div class="box-footer">
                @includeIf('project::avatar.create', ['max_count' => '10', 'members' => $project->members])
            </div>
            <!-- /.box-footer-->
        </div>
    </div>
</div>

<div class="row"
    style="border: 1px solid #767779; border-radius:10px; padding:5px; margin-bottom:6px; box-shadow: 0 0 4px rgb(65, 60, 60);">
    <div class="" style="font-size:25px; margin:10px; width:fit-content; color:#7CB5EC; font-weight:400;"><i class="fas fa-check-circle"></i>   Work Distribution
    </div>
    <div class="col-md-7">
        <div class="row">

            @foreach ($project_members_card as $project_member)
                <div class="col-md-3">
                    <div class="card"
                        style="border: 1px solid #7CB5EC; border-radius:10px; padding:5px; margin-bottom:6px;">
                        <div class="card-header">
                            <div class=""
                                style="font-size: 14px; overflow: hidden;
							white-space: nowrap;
							text-overflow: ellipsis;
							width: 140px; ">
                                @includeIf('project::avatar.create', [
                                    'member' => $project_member['name'],
                                ])
                                {{ $project_member['name'] }}
                            </div>
                            <div class="row">
                                <div class="col-md-6 text-danger">
                                    <div class="text-center">{{ $project_member['task_count'] }} </div>
                                    <div style="font-size: 10px" class="text-center">Not Done </div>
                                </div>
                                <div class="col-md-6 text-primary">
                                    <div class="text-center">{{ $project_member['completed_tasks'] }} </div>
                                    <div style="font-size: 10px" class="text-center">Done </div>
                                </div>
                            </div>
                            <!-- /.box-tools -->
                        </div>
                        <!-- /.box-header -->
                        <div class="card-body" style="margin-top:8px;">
                            <span class="fs-12">
                                {{-- <b>{{ $project_member['id']}}</b> --}}
                                @if ($project->settings['not_started']['id'] == 1)
                                    {{ $project->settings['not_started']['name'] }}:
                                    {{ $project_member['not_started'] }} <br>
                                @endif
                                @if ($project->settings['in_progress']['id'] == 1)
                                    {{ $project->settings['in_progress']['name'] }}:
                                    {{ $project_member['in_progress_tasks'] }} <br>
                                @endif
                                @if ($project->settings['on_hold']['id'] == 1)
                                    {{ $project->settings['on_hold']['name'] }}:
                                    {{ $project_member['on_hold_tasks'] }} <br>
                                @endif
                            </span>
                        </div>
                        <!-- /.box-body -->
                    </div>
                    <!-- /.box -->
                </div>
            @endforeach
        </div>
    </div>
    <div class="col-md-5">
        {!! $chart->script() !!}
        {!! $chart->container() !!}
    </div>

</div>

<!-- /.box -->
<div class="modal fade" tabindex="-1" role="dialog" id="project_model"></div>

