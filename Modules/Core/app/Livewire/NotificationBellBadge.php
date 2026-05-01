<?php

declare(strict_types=1);

namespace Modules\Core\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

final class NotificationBellBadge extends Component
{
    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->refresh();
    }

    #[On('notificationsRead')]
    #[On('notificationClosed')]
    public function refresh(): void
    {
        $user = Auth::user();

        $this->unreadCount = $user
            ? DatabaseNotification::query()
                ->where('notifiable_id', $user->getKey())
                ->whereNull('read_at')
                ->count()
            : 0;
    }

    public function render(): View
    {
        return view('core::livewire.notification-bell-badge');
    }
}
