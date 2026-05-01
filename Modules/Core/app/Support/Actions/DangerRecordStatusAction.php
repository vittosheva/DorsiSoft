<?php

declare(strict_types=1);

namespace Modules\Core\Support\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

final class DangerRecordStatusAction extends Action
{
    /**
     * @var (Closure(Model, array<string, mixed>): void)|null
     */
    private ?Closure $applyTransitionUsing = null;

    /**
     * @var (Closure(Model, array<string, mixed>): void)|null
     */
    private ?Closure $afterTransitionUsing = null;

    /**
     * @var (Closure(Model, array<string, mixed>): string)|null
     */
    private ?Closure $notificationTitleUsing = null;

    /**
     * @var (Closure(Model, array<string, mixed>): string)|null
     */
    private ?Closure $redirectUrlUsing = null;

    private string $notificationStyle = 'warning';

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->icon(Heroicon::XCircle)
            ->color('danger')
            ->requiresConfirmation()
            ->action(function (array $data, Model $record): void {
                if ($this->applyTransitionUsing === null) {
                    throw new RuntimeException(__('DangerRecordStatusAction requires applyTransitionUsing().'));
                }

                ($this->applyTransitionUsing)($record, $data);

                if ($this->afterTransitionUsing !== null) {
                    ($this->afterTransitionUsing)($record, $data);
                }

                if ($this->notificationTitleUsing !== null) {
                    $notification = Notification::make()->title(($this->notificationTitleUsing)($record, $data));

                    match ($this->notificationStyle) {
                        'danger' => $notification->danger(),
                        'info' => $notification->info(),
                        'success' => $notification->success(),
                        default => $notification->warning(),
                    };

                    $notification->send();
                }

                if ($this->redirectUrlUsing !== null) {
                    $this->redirect(($this->redirectUrlUsing)($record, $data));
                }
            });
    }

    /**
     * @param  Closure(Model, array<string, mixed>): void  $callback
     */
    public function applyTransitionUsing(Closure $callback): static
    {
        $this->applyTransitionUsing = $callback;

        return $this;
    }

    /**
     * @param  Closure(Model, array<string, mixed>): void  $callback
     */
    public function afterTransitionUsing(Closure $callback): static
    {
        $this->afterTransitionUsing = $callback;

        return $this;
    }

    /**
     * @param  Closure(Model, array<string, mixed>): string  $callback
     */
    public function notificationTitleUsing(Closure $callback): static
    {
        $this->notificationTitleUsing = $callback;

        return $this;
    }

    public function notificationStyle(string $notificationStyle): static
    {
        $this->notificationStyle = $notificationStyle;

        return $this;
    }

    /**
     * @param  Closure(Model, array<string, mixed>): string  $callback
     */
    public function redirectUrlUsing(Closure $callback): static
    {
        $this->redirectUrlUsing = $callback;

        return $this;
    }
}
