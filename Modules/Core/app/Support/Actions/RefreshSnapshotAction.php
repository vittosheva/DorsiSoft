<?php

declare(strict_types=1);

namespace Modules\Core\Support\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

final class RefreshSnapshotAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Update contact data'))
            ->tooltip(fn (Action $action) => $action->isIconButton() ? __('Update contact data') : null)
            ->icon(Heroicon::ArrowPath)
            ->color(Color::Indigo)
            ->visible(fn (Model $record) => method_exists($record, 'isEditable') && $record->isEditable() && method_exists($record, 'isSnapshotStale') && $record->isSnapshotStale())
            ->requiresConfirmation()
            ->modalHeading(__('Update contact data'))
            ->modalDescription(__('The contact updated their data after this document was created. This will update the snapshot fields on this document. Authorized documents are never modified.'))
            ->modalSubmitActionLabel(__('Update'))
            ->action(function (Model $record): void {
                if (! method_exists($record, 'refreshSnapshot')) {
                    return;
                }

                $record->refreshSnapshot();

                Notification::make()
                    ->title(__('Contact data updated'))
                    ->success()
                    ->send();
            })
            ->after(function () {
                $this->redirect(request()->header('referer') ?? request()->fullUrl());
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'refresh_snapshot';
    }
}
