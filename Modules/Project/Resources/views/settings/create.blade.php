{!! Form::open([
    'url' => action(
        [\Modules\Project\Http\Controllers\ProjectController::class, 'postSettings'],
        ['project_id' => $project->id],
    ),
    'id' => 'settings_form',
    'method' => 'put',
]) !!}

<div class="row">
    <div class="col-md-4">
        <div class="checkbox">
            <label>
                <input type="checkbox" name="enable_timelog" value="1"
                    @if (isset($project->settings['enable_timelog']) && $project->settings['enable_timelog']) checked @endif> @lang('project::lang.enable_timelog')
            </label>
        </div>
    </div>

    <div class="col-md-4">
        <div class="checkbox">
            <label>
                <input type="checkbox" name="enable_notes_documents" value="1"
                    @if (isset($project->settings['enable_notes_documents']) && $project->settings['enable_notes_documents']) checked @endif> @lang('project::lang.enable_notes_documents')
            </label>
        </div>
    </div>

    <div class="col-md-4">
        <div class="checkbox">
            <label>
                <input type="checkbox" name="enable_invoice" value="1"
                    @if (isset($project->settings['enable_invoice']) && $project->settings['enable_invoice']) checked @endif> @lang('project::lang.enable_invoice')
            </label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="checkbox">
            <label>
                <input type="checkbox" name="enable_archive" value="archive"
                    @if (isset($project->settings['enable_archive']) && $project->settings['enable_archive']) checked @endif> @lang('project::lang.enable_archive')
            </label>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <label>Default Task View:&nbsp;</label>
        <label class="radio-inline">
            <input type="radio" name="task_view" value="list_view" required
                @if (isset($project->settings['task_view']) && $project->settings['task_view'] == 'list_view') checked @endif>
            @lang('project::lang.list_view')
        </label>
        <label class="radio-inline">
            <input type="radio" name="task_view" value="kanban" required
                @if (isset($project->settings['task_view']) && $project->settings['task_view'] == 'kanban') checked @endif>
            @lang('project::lang.kanban_board')
        </label>
    </div>
    <div class="col-md-4">
        @php
            $task_id_prefix = !empty($project->settings['task_id_prefix']) ? $project->settings['task_id_prefix'] : '';
        @endphp
        <div class="form-group form-inline">
            {!! Form::label('task_id_prefix', __('project::lang.task_id_prefix') . ':*') !!}
            {!! Form::text('task_id_prefix', $task_id_prefix, ['class' => 'form-control', 'required']) !!}
        </div>
    </div>
</div>

<br>
<div class="row">
    <div class="col-md-3">
        <label>@lang('user.permissions')</label>
        <div>
            <label for="members_crud_task">@lang('project::lang.add_a_task')</label>
        </div>
        <div>
            <label for="members_crud_timelog">@lang('project::lang.add_time_log')</label>
        </div>
        <div>
            <label for="members_crud_note">@lang('project::lang.add_notes_docs')</label>
        </div>
    </div>
    <div class="col-md-3">
        <label>@lang('project::lang.members')</label>
        <div>
            <input type="checkbox" id="members_crud_task" name="members_crud_task" value="1"
                @if (isset($project->settings['members_crud_task']) && $project->settings['members_crud_task']) checked @endif>
        </div>
        <div>
            <input type="checkbox" id="members_crud_timelog" name="members_crud_timelog" value="1"
                @if (isset($project->settings['members_crud_timelog']) && $project->settings['members_crud_timelog']) checked @endif>
        </div>
        <div>
            <input type="checkbox" id="members_crud_note" name="members_crud_note" value="1"
                @if (isset($project->settings['members_crud_note']) && $project->settings['members_crud_note']) checked @endif>
        </div>
    </div>
    <div class="col-md-6">
        <label>Custom Label Settings</label>
        <div id="entries">
            <div class="row">
                <div class="col-md-6"></div>
                <div class="col-md-2">
                    <button type="button" class="add-entry btn btn-primary btn-sm">Add +</button>
                </div>
            </div>
            @if (isset($project->settings['levels']))
                @foreach ($project->settings['levels'] as $level)
                    <div class="entry">
                        <div class="row">
                            <div class="col-md-4">
                                <input class="form-control" type="text" name="level_name[]" placeholder="Label Name" value="{{ $level['name'] }}">
                            </div>
                            <div hidden class="col-md-2">
                                <input class="form-control color-picker" type="text" name="color[]" value="{{ $level['color'] }}">
                            </div>
                            <div class="col-md-2" style="padding-right: 0; width: 12%;">
                                <input class="form-control bg-picker" type="text" name="bg[]" value="{{ $level['bg'] }}">
                            </div>
                            <div class="col-md-2" style="padding-left: 0;">
                                <span style="font-size: 20px;" class="remove-entry bg-white text-red"><i class="fas fa-minus-circle"></i></span>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
            <div class="entry">
                <div class="row">
                    <div class="col-md-4">
                        <input class="form-control" type="text" name="level_name[]" placeholder="Label Name">
                    </div>
                    <div hidden class="col-md-2">
                        <input class="form-control color-picker" type="text" name="color[]" value="#000000">
                    </div>
                    <div class="col-md-2" style="padding-right: 0; width: 12%;">
                        <input class="form-control bg-picker" type="text" name="bg[]" value="#FFFFFF">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class=""><br>
    <label class="" style="font-size:20px; margin-top:10px;display:block;">
        <i class="fa fa-cogs"></i> Kanban Board - Status Settings
    </label> <br>
    <div class="row">
        <div class="col-md-3">
            <label>Status Name</label>
        </div>
        <div class="col-md-3">
            <label>Enable ?</label>
        </div>
        <div class="col-md-3">
            <label>Rename</label>
        </div>
    </div>
    <div class="row">
        @php
            $not_started = !empty($project->settings['not_started']['name'])
                ? $project->settings['not_started']['name']
                : __('project::lang.not_started');
        @endphp
        <div class="col-md-3">
            {!! Form::label('not_started', __('project::lang.not_started') . ':') !!}
        </div>
        <div class="col-md-3">
            <input type="checkbox" id="not_started_id" name="not_started_id" value="1"
                @if (isset($project->settings['not_started']['id']) && $project->settings['not_started']['id']) checked @endif>
        </div>
        <div class="col-md-3">
            {!! Form::text('not_started', $not_started, [
                'class' => 'form-control',
                'placeholder' => 'Type To Rename',
                'id' => 'not_started',
            ]) !!}
        </div>
    </div>
    <div class="row">
        @php
            $not_started = !empty($project->settings['in_progress']['name'])
                ? $project->settings['in_progress']['name']
                : __('project::lang.in_progress');
        @endphp
        <div class="col-md-3">
            {!! Form::label('in_progress', __('project::lang.in_progress') . ':') !!}
        </div>
        <div class="col-md-3">
            <input type="checkbox" id="in_progress_id" name="in_progress_id" value="1"
                @if (isset($project->settings['in_progress']['id']) && $project->settings['in_progress']['id']) checked @endif>
        </div>
        <div class="col-md-3">
            {!! Form::text('in_progress', $not_started, [
                'class' => 'form-control',
                'placeholder' => 'Type To Rename',
                'id' => 'in_progress',
            ]) !!}
        </div>
    </div>
    <div class="row">
        @php
            $not_started = !empty($project->settings['on_hold']['name'])
                ? $project->settings['on_hold']['name']
                : __('project::lang.on_hold');
        @endphp
        <div class="col-md-3">
            {!! Form::label('on_hold', __('project::lang.on_hold') . ':') !!}
        </div>
        <div class="col-md-3">
            <input type="checkbox" id="on_hold_id" name="on_hold_id" value="1"
                @if (isset($project->settings['on_hold']['id']) && $project->settings['on_hold']['id']) checked @endif>
        </div>
        <div class="col-md-3">
            {!! Form::text('on_hold', $not_started, [
                'class' => 'form-control',
                'id' => 'on_hold',
                'placeholder' => 'Type To Rename',
            ]) !!}
        </div>
    </div>
    <div class="row">
        @php
            $not_started = !empty($project->settings['cancelled']['name'])
                ? $project->settings['cancelled']['name']
                : __('project::lang.cancelled');
        @endphp
        <div class="col-md-3">
            {!! Form::label('cancelled', __('project::lang.cancelled') . ':') !!}
        </div>
        <div class="col-md-3">
            <input type="checkbox" id="cancelled_id" name="cancelled_id" value="1"
                @if (isset($project->settings['cancelled']['id']) && $project->settings['cancelled']['id']) checked @endif>
        </div>
        <div class="col-md-3">
            {!! Form::text('cancelled', $not_started, [
                'class' => 'form-control',
                'id' => 'cancelled',
                'placeholder' => 'Type To Rename',
            ]) !!}
        </div>
    </div>
    <div class="row">
        @php
            $not_started = !empty($project->settings['completed']['name'])
                ? $project->settings['completed']['name']
                : __('project::lang.completed');
        @endphp
        <div class="col-md-3">
            {!! Form::label('completed', __('project::lang.completed') . ':') !!}
        </div>
        <div class="col-md-3">
            <input type="checkbox" id="completed_id" name="completed_id" value="1"
                @if (isset($project->settings['completed']['id']) && $project->settings['completed']['id']) checked @endif>
        </div>
        <div class="col-md-3">
            {!! Form::text('completed', $not_started, ['class' => 'form-control', 'placeholder' => 'Type To Rename']) !!}
        </div>
    </div>
</div>
<div class="row">
    <div style="margin-top: 40px" class="col-md-12 text-center">
        <!-- Add an id attribute to the update button -->
        <button id="update-button" style="padding:10px 20px; font-size: 20px" type="button"
            class="btn btn-primary">
            @lang('messages.update')
        </button>
    </div>
</div>
{!! Form::close() !!}
