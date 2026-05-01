<?php

declare(strict_types=1);

namespace Modules\Sri\Support\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\Company;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Enums\ElectronicStatusEnum;
use Modules\Sri\Services\ElectronicDocumentOrchestrator;
use Throwable;

final class PollElectronicAuthorizationAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Check SRI'))
            ->tooltip(fn (Action $action) => $action->isIconButton() ? __('Check authorization status in SRI') : null)
            ->icon(Heroicon::ArrowPathRoundedSquare)
            ->color('warning')
            ->visible(function (?Model $record): bool {
                return $record instanceof HasElectronicBilling
                    && $record->getElectronicStatus() === ElectronicStatusEnum::Submitted
                    && filled($record->getAccessKey());
            })
            ->authorize(fn (?Model $record): bool => $record instanceof Model && Gate::allows('view', $record))
            ->requiresConfirmation()
            ->modalHeading(__('Check status in SRI'))
            ->modalDescription(__('Manually check SRI when the document is still IN PROCESS and the final result is not available yet.'))
            ->action(function (?Model $record): void {
                if (! $record instanceof HasElectronicBilling || ! $record instanceof Model) {
                    return;
                }

                try {
                    /** @var Company|null $company */
                    $company = $record->relationLoaded('company')
                        ? $record->getRelation('company')
                        : Company::withoutGlobalScopes()->find($record->company_id);

                    app(ElectronicDocumentOrchestrator::class)->pollAuthorization($record, $company, Auth::id());

                    $record->refresh();

                    $notification = Notification::make();

                    if ($record->getElectronicStatus() === ElectronicStatusEnum::Authorized) {
                        $notification
                            ->title(__('Document authorized'))
                            ->body(__('SRI has already returned the document authorization.'))
                            ->success()
                            ->send();

                        return;
                    }

                    if ($record->getElectronicStatus() === ElectronicStatusEnum::Rejected) {
                        $notification
                            ->title(__('Document rejected by SRI'))
                            ->body(__('SRI returned a rejection. Review the electronic audit for details.'))
                            ->warning()
                            ->persistent()
                            ->send();

                        return;
                    }

                    $notification
                        ->title(__('SRI still reports it as in process'))
                        ->body(__('The manual check did not return authorization yet. Automatic polling will continue running every 5 minutes.'))
                        ->info()
                        ->send();
                } catch (Throwable $exception) {
                    Notification::make()
                        ->title(__('Could not check status in SRI'))
                        ->body($exception->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();
                }
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'poll_electronic_authorization';
    }
}
