@if($can_crud_task || $is_lead_or_admin)
<button type="button" class="btn btn-sm btn-primary task_btn pull-right m-5" data-href="{{action([\Modules\Project\Http\Controllers\TaskController::class, 'create'], ['project_id' => $project->id])}}">
    @lang('messages.add')&nbsp;
    <i class="fa fa-plus"></i>
</button>
@endif
<div class="btn-group btn-group-toggle pull-right m-5" data-toggle="buttons">
    <label class="btn btn-info btn-sm 
        @if((!empty($project->settings) && !isset($project->settings['task_view'])) || (isset($project->settings['task_view']) &&
                $project->settings['task_view'] == 'list_view'))
            active
        @endif">
        <input type="radio" name="task_view" value="list_view" class="task_view" 
           @if((!empty($project->settings) && !isset($project->settings['task_view'])) || (isset($project->settings['task_view']) &&
                $project->settings['task_view'] == 'list_view'))
                checked
            @endif>
        @lang('project::lang.list_view')
    </label>
    <label class="btn btn-info btn-sm
        @if(isset($project->settings['task_view']) &&
        $project->settings['task_view'] == 'kanban')
            active
        @endif">
        <input type="radio" name="task_view" value="kanban" class="task_view" 
            @if(isset($project->settings['task_view']) &&
            $project->settings['task_view'] == 'kanban')
                checked
            @endif>
        @lang('project::lang.kanban_board')
    </label>
    @if(isset($project->settings['enable_archive']) && $project->settings['enable_archive'] == 'archive')
    <label class="btn btn-info btn-sm">
        <input type="radio" name="task_view" value="archive" class="task_view">
        <i class="fas fa-file-archive"></i>
    </label>
    @endif
</div>
<br><br>
<div class="table-responsive
    @if(isset($project->settings['task_view']) &&
        $project->settings['task_view'] != 'list_view')
        hide
    @endif">
    <table class="table table-bordered table-striped" id="project_task_table">
        <thead>
            <tr>
                <th> @lang('messages.action')</th>
                <th> @lang('messages.created_at')</th>
                <th class="col-md-4"> @lang('project::lang.subject')</th>
                <th> @lang('project::lang.assigned_to')</th>
                <th> @lang('project::lang.priority')</th>
                <th>Estimated Hours</th>
                <th> @lang('business.start_date')</th>
                <th>@lang('project::lang.due_date')</th>
                <th>@lang('sale.status')</th>
                <th>@lang('project::lang.assigned_by')</th>
                <th> @lang('messages.updated_at')</th>
                <th>Label</th>
            </tr>
        </thead>
    </table>
</div>

<div class="custom-kanban-board
    @if(isset($project->settings['task_view']) &&
    $project->settings['task_view'] != 'kanban')
        hide
    @endif">
    <div class="page">
        <div class="main">
            <div class="meta-tasks-wrapper">
                <div id="myKanban" class="meta-tasks"></div>
            </div>
        </div>
    </div>
</div>
<div class="archive-table
  @if (isset($project->settings['task_view'])) hide @endif">
    <table class="table table-bordered table-striped" id="archive_project_task_table">
        <thead>
            <tr>
                <th> @lang('messages.action')</th>
                <th> @lang('messages.created_at')</th>
                <th class="col-md-4"> @lang('project::lang.subject')</th>
                <th> @lang('project::lang.assigned_to')</th>
                <th> @lang('project::lang.priority')</th>
                <th>Estimated Hours</th>
                <th> @lang('business.start_date')</th>
                <th>@lang('project::lang.due_date')</th>
                <th>@lang('sale.status')</th>
                <th>@lang('project::lang.assigned_by')</th>
                <th> @lang('messages.updated_at')</th>
                <th>Label</th>
            </tr>
        </thead>
    </table>
</div>

<div id="c-popup" class="c-popup">
    <span class="c-close">&times;</span>
    <img class="c-popup-content" id="c-popup-img">
    <div id="c-caption"></div>
</div>