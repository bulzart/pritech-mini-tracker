{{--
    Status badge. Usage: @include('issues._status_badge', ['status' => $issue->status])
    Underscores are shown as spaces ("in_progress" → "in progress").
--}}
<span class="badge badge--status-{{ $status }}">{{ str_replace('_', ' ', $status) }}</span>
