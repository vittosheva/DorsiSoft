<?php

declare(strict_types=1);

namespace Modules\Core\Support\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

final class TransitionRecordStatusAction extends Action
{
    private ?Closure $applyTransitionUsing = null;

    private ?Closure $afterTransitionUsing = null;

    private ?Closure $notificationTitleUsing = null;

    private ?Closure $redirectUrlUsing = null;

    private string $notificationStyle = 'success';

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->requiresConfirmation()
            ->action(function (array $data, Model $record): void {
                if ($this->applyTransitionUsing === null) {
                    throw new RuntimeException(__('TransitionRecordStatusAction requires applyTransitionUsing().'));
                }

                $this->evaluate($this->applyTransitionUsing, [
                    'data' => $data,
                    'record' => $record,
                ], [
                    Model::class => $record,
                    $record::class => $record,
                ]);

                if ($this->afterTransitionUsing !== null) {
                    $this->evaluate($this->afterTransitionUsing, [
                        'data' => $data,
                        'record' => $record,
                    ], [
                        Model::class => $record,
                        $record::class => $record,
                    ]);
                }

                if ($this->notificationTitleUsing !== null) {
                    $notification = Notification::make()->title((string) $this->evaluate($this->notificationTitleUsing, [
                        'data' => $data,
                        'record' => $record,
                    ], [
                        Model::class => $record,
                        $record::class => $record,
                    ]));

                    match ($this->notificationStyle) {
                        'warning' => $notification->warning(),
                        'danger' => $notification->danger(),
                        'info' => $notification->info(),
                        default => $notification->success(),
                    };

                    $notification->send();
                }

                if ($this->redirectUrlUsing !== null) {
                    $this->redirect((string) $this->evaluate($this->redirectUrlUsing, [
                        'data' => $data,
                        'record' => $record,
                    ], [
                        Model::class => $record,
                        $record::class => $record,
                    ]));
                }
            });
    }

    public function applyTransitionUsing(Closure $callback): static
    {
        $this->applyTransitionUsing = $callback;

        return $this;
    }

    public function afterTransitionUsing(Closure $callback): static
    {
        $this->afterTransitionUsing = $callback;

        return $this;
    }

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

    public function redirectUrlUsing(Closure $callback): static
    {
        $this->redirectUrlUsing = $callback;

        return $this;
    }
}
