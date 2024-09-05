<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">Role: {{ $role_name }}</h4>
        </div>

        <div class="modal-body">
            <table class="table table-striped table-bordered" id="role-users-table">
                <thead>
                    <tr>
                        <th>User Name</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th style="width: 100px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->username }}</td>
                            <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @can('user.update')
                                    <a href="{{ action('App\Http\Controllers\ManageUserController@edit', [$user->id]) }}" class="btn btn-xs btn-primary">
                                        <i class="glyphicon glyphicon-edit"></i> @lang('messages.edit')
                                    </a>
                                    &nbsp;
                                @endcan
                                @can('user.view')
                                    <a href="{{ action('App\Http\Controllers\ManageUserController@show', [$user->id]) }}" class="btn btn-xs btn-info">
                                        <i class="fa fa-eye"></i> @lang('messages.view')
                                    </a>
                                    &nbsp;
                                @endcan
                                @can('user.delete')
                                    <button data-href="{{ action('App\Http\Controllers\ManageUserController@destroy', [$user->id]) }}" class="btn btn-xs btn-danger delete_user_button">
                                        <i class="glyphicon glyphicon-trash"></i> @lang('messages.delete')
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No users found for this role.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->