@if (isset($members) && (is_array($members) || is_object($members)))
    @foreach ($members as $member)
        @if ($loop->iteration < $max_count)
            @if (isset($member->media->display_url))
                <img class="user_avatar" src="{{ $member->media->display_url }}" data-toggle="tooltip"
                    title="{{ $member->user_full_name }}">
            @else
                <img class="user_avatar" src="https://ui-avatars.com/api/?name={{ $member->first_name }}"
                    data-toggle="tooltip" title="{{ $member->user_full_name }}">
            @endif
        @elseif($loop->iteration == $max_count)
            <img class="user_avatar" src="https://ui-avatars.com/api/?name=...." data-toggle="tooltip" title="...">
        @endif
    @endforeach
@else
    @if (isset($member->media->display_url))
        <img class="user_avatar" src="{{ $member->media->display_url }}" data-toggle="tooltip"
            title="{{ $member }}">
    @else
        <img class="user_avatar" src="https://ui-avatars.com/api/?name={{ $member }}" data-toggle="tooltip"
            title="{{ $member}}">
    @endif
@endif
