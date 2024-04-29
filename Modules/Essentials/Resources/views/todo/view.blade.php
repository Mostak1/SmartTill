<div class="modal-dialog" role="document">
    <div class="modal-content">


        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('essentials::lang.todo')</h4>
        </div>

        <div class="modal-body">
            <section class="content">
                <div class="row">
                    <div class="col-md-12">
                        <div class="box box-primary">
                            <div class="box-header">
                                <h4>
                                    <i class="ion ion-clipboard"></i>
                                    <small><code>({{ $todo->task_id }})</code></small> {{ $todo->task }}
                                </h4>
                            </div>
                            <div class="box-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>{{ __('business.start_date') }}: </strong>
                                        {{ @format_date($todo->date) }}<br>
                                        <strong>{{ __('essentials::lang.end_date') }}: </strong>
                                        @if (!empty($todo->end_date))
                                            {{ @format_date($todo->end_date) }}
                                        @endif
                                        <br>
                                        <strong>{{ __('essentials::lang.estimated_hours') }}: </strong>
                                        {{ $todo->estimated_hours }}
                                    </div>
                                    <div class="col-md-4">
                                        <strong>{{ __('essentials::lang.assigned_by') }}: </strong>
                                        {{ $todo->assigned_by?->user_full_name }}<br>
                                        <strong>{{ __('essentials::lang.assigned_to') }}: </strong>
                                        {{ implode(', ', $users) }}
                                    </div>
                                    <div class="col-md-4">
                                        <strong>{{ __('essentials::lang.priority') }}: </strong>
                                        {{ $priorities[$todo->priority] ?? '' }}<br>
                                        <strong>{{ __('sale.status') }}: </strong>
                                        {{ $task_statuses[$todo->status] ?? '' }}
                                    </div>
                                    <div class="clearfix"></div>
                                    <div class="col-md-12">
                                        <br />
                                        <strong>{{ __('lang_v1.description') }}: </strong> {!! $todo->description !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="nav-tabs-custom">
                                <ul class="nav nav-tabs">
                                    <li class="active">
                                        <a href="#comments_tab" data-toggle="tab" aria-expanded="true">
                                            <i class="fa fa-comment"></i>
                                            @lang('essentials::lang.comments') </a>
                                    </li>
                                    <li>
                                        <a href="#documents_tab" data-toggle="tab">
                                            <i class="fa fa-file"></i>
                                            @lang('lang_v1.documents') </a>
                                    </li>
                                    <li>
                                        <a href="#activities_tab" data-toggle="tab">
                                            <i class="fa fa-pen-square"></i>
                                            @lang('lang_v1.activities') </a>
                                    </li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane active" id="comments_tab">
                                        <div class="row">
                                            {!! Form::open([
                                                'url' => action([\Modules\Essentials\Http\Controllers\ToDoController::class, 'addComment']),
                                                'id' => 'task_comment_form',
                                                'method' => 'post',
                                            ]) !!}
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    {!! Form::label('comment', __('essentials::lang.add_comment') . ':') !!}
                                                    {!! Form::textarea('comment', null, ['rows' => 3, 'class' => 'form-control', 'required']) !!}
                                                    {!! Form::hidden('task_id', $todo->id) !!}
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <button type="submit"
                                                    class="btn btn-primary pull-right ladda-button add-comment-btn"
                                                    data-style="expand-right">
                                                    <span class="ladda-label">
                                                        @lang('messages.add')
                                                    </span>
                                                </button>
                                            </div>
                                            {!! Form::close() !!}
                                            <div class="col-md-12">
                                                <hr>
                                                <div class="direct-chat-messages">
                                                    @foreach ($todo->comments as $comment)
                                                        @include('essentials::todo.comment', [
                                                            'comment' => $comment,
                                                        ])
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tab-pane" id="documents_tab">
                                        <div class="row">
                                            {!! Form::open([
                                                'url' => action([\Modules\Essentials\Http\Controllers\ToDoController::class, 'uploadDocument']),
                                                'id' => 'task_upload_doc_form',
                                                'method' => 'post',
                                                'files' => true,
                                            ]) !!}
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    {!! Form::label('documents', __('lang_v1.upload_documents') . ':') !!}
                                                    {!! Form::file('documents[]', ['id' => 'documents', 'multiple', 'required']) !!}
                                                    {!! Form::hidden('task_id', $todo->id) !!}
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    {!! Form::label('description', __('lang_v1.description') . ':') !!}
                                                    {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => 3]) !!}
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <button type="submit"
                                                    class="btn btn-primary pull-right">@lang('essentials::lang.upload')</button>
                                            </div>
                                            {!! Form::close() !!}
                                            <div class="col-md-12">
                                                <hr>
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                            <th>@lang('lang_v1.documents')</th>
                                                            <th>@lang('lang_v1.description')</th>
                                                            <th>@lang('lang_v1.uploaded_by')</th>
                                                            <th>@lang('lang_v1.download')</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($todo->media as $media)
                                                            <tr>
                                                                <td>

                                                                    @php
                                                                        // Extract file extension from the media URL
                                                                        $fileExtension = pathinfo(
                                                                            $media->display_url,
                                                                            PATHINFO_EXTENSION,
                                                                        );
                                                                        // Define an array of allowed image extensions
                                                                        $allowedExtensions = [
                                                                            'jpg',
                                                                            'jpeg',
                                                                            'png',
                                                                            'gif',
                                                                            'bmp',
                                                                        ];
                                                                    @endphp

                                                                    @if (in_array(strtolower($fileExtension), $allowedExtensions))
                                                                        <!-- If media is an image, display the image -->
                                                                        <img style="width: 100px; height: auto; margin-bottom: 10px;"
                                                                            src="{{ $media->display_url }}"
                                                                            alt="{{ $media->display_name }}">
                                                                        {{ $media->display_name }}
                                                                    @else
                                                                        {{ $media->display_name }}
                                                                    @endif

                                                                </td>
                                                                <td>{{ $media->description }}</td>
                                                                <td>{{ $media->uploaded_by_user->user_full_name ?? '' }}
                                                                </td>
                                                                <td><a href="{{ $media->display_url }}" download
                                                                        class="btn btn-success btn-xs">@lang('lang_v1.download')</a>

                                                                    @if (in_array(auth()->user()->id, [$media->uploaded_by, $todo->created_by]))
                                                                        <a href="{{ action([\Modules\Essentials\Http\Controllers\ToDoController::class, 'deleteDocument'], $media->id) }}"
                                                                            class="btn btn-danger btn-xs delete-document"
                                                                            data-media_id="{{ $media->id }}"><i
                                                                                class="fa fa-trash"></i>
                                                                            @lang('messages.delete')</a>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane" id="activities_tab">
                                        <div class="row">
                                            <div class="col-md-12">
                                                @include('activity_log.activities', [
                                                    'activity_type' => 'sell',
                                                    'statuses' => $task_statuses,
                                                ])
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            </section>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary ladda-button" data-style="expand-right">
                <span class="ladda-label">@lang('messages.save')</span>
            </button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>


    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
