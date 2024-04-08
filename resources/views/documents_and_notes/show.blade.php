<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">{!! $document_note->heading !!}</h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    {!! $document_note->description !!}
                </div>
            </div>
            @if ($document_note->media->count() > 0)
                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <h4>@lang('lang_v1.documents')</h4>
                        @foreach ($document_note->media as $media)
                            @php
                                // Extract file extension from the media URL
                                $fileExtension = pathinfo($media->display_url, PATHINFO_EXTENSION);
                                // Define an array of allowed image extensions
                                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
                            @endphp

                            @if (in_array(strtolower($fileExtension), $allowedExtensions))
                                <!-- If media is an image, display the image -->
                                <img style="width: 200px; height: auto; margin-bottom: 10px;" src="{{ $media->display_url }}" alt="{{ $media->display_name }}">
                            @else
                                <!-- If media is not an image, provide a download link -->
                                <a href="{{ $media->display_url }}" download="{{ $media->display_name }}">
                                    <i class="fa fa-download"></i> {{ $media->display_name }}
                                </a><br>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        <div class="modal-footer">
            <span class="pull-left">
                <i class="fas fa-pencil-alt"></i>
                {{ $document_note->createdBy->user_full_name }}
                &nbsp;
                <i class="fa fa-calendar-check-o"></i>
                {{ @format_date($document_note->created_at) }}
            </span>
            <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">
                @lang('messages.close')
            </button>
        </div>
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
