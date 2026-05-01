<span
    @if ($unreadCount > 0)
        class="fi-bell-badge"
        aria-label="{{ $unreadCount }} {{ __('unread notifications') }}"
    @else
        style="display:none"
    @endif
>{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
