<div class="modal-dialog" role="document">
    <div class="modal-content">
        <form action="{{ action([\App\Http\Controllers\RoleController::class, 'store']) }}" method="POST" id="role_form">
            {!! csrf_field() !!}
            
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Role Create</h4>
            </div>

            <div class="modal-body">
                <div class="form-group row">
                    <div class="col-md-12">
                        <label for="role_name">@lang('user.role_name'): *</label>
                        <input type="text" class="form-control" id="role_name" name="role_name" required>
                    </div>

                    <div style="margin-top: 15px" class="col-md-12">
                        {!! Form::label('copy_role', __('Copy From Existing Role') . ':') !!}
                        {!! Form::select('copy_role', $roles, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => 'Select Existing Role']) !!}
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" id="submitForm" class="btn btn-primary">@lang('messages.submit')</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('messages.close')</button>
            </div>
        </form>
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->