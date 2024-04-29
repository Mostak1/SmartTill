<div class="modal-dialog" role="document">
    {!! Form::open([
        'url' => action([\Modules\Project\Http\Controllers\TaskController::class, 'postChangeProject'], $project_task->id),
        'id' => 'change_project',
        'method' => 'put',
    ]) !!}
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">
                @lang('project::lang.change_project')
            </h4>
        </div>
        <div class="modal-body">
            
            @if ($leader)
                <div class="">
                    <div class="form-group">
                        {!! Form::label('project_id', 'Change Project') !!}
                        {!! Form::select('project_id', $projects, $project_task->project_id, [
                            'class' => 'form-control select2',
                            'style' => 'width: 100%;',
                        ]) !!}
                    </div>
                </div>
            @elseif(auth()->user()->hasRole('Admin#' . session('business.id')))
                <div class="">
                    <div class="form-group">
                        {!! Form::label('project_id', 'Change Project') !!}
                        {!! Form::select('project_id', $projects, $project_task->project_id, [
                            'class' => 'form-control select2',
                            'style' => 'width: 100%;',
                        ]) !!}
                    </div>
                </div>
            @else
                <div class="">You Are Not Authorised to change The task to another Project</div>
            @endif

        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary btn-sm">
                @lang('messages.update')
            </button>
            <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">
                @lang('messages.close')
            </button>
        </div>
    </div><!-- /.modal-content -->
    {!! Form::close() !!}
</div><!-- /.modal-dialog -->
