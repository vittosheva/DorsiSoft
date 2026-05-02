<?php

declare(strict_types=1);

namespace Modules\Sri\Support\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\Company;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Enums\ElectronicStatusEnum;
use Modules\Sri\Services\SendElectronicDocumentEmailService;
use Throwable;

final class SendElectronicDocumentEmailAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Send by email'))
            ->tooltip(fn (Action $action) => $action->isIconButton() ? __('Send RIDE and XML') : null)
            ->icon(Heroicon::Envelope)
            ->color('info')
            ->modalHeading(__('Send electronic document by email'))
            ->modalDescription(__('The RIDE PDF and XML download link will be sent automatically to the configured recipient.'))
            ->modalSubmitActionLabel(__('Send'))
            ->requiresConfirmation()
            ->visible(fn (?Model $record): bool => $record instanceof HasElectronicBilling
                && $record->getElectronicStatus() === ElectronicStatusEnum::Authorized)
            ->authorize(fn (?Model $record): bool => $record ? Gate::allows('view', $record) : false)
            ->action(function (?Model $record): void {
                if (! $record instanceof HasElectronicBilling || ! $record instanceof Model) {
                    return;
                }

                /** @var Company|null $company */
                $company = $record->relationLoaded('company')
                    ? $record->getRelation('company')
                    : Company::withoutGlobalScopes()->find($record->company_id);

                if (! $company) {
                    Notification::make()
                        ->title(__('Company not found'))
                        ->danger()
                        ->send();

                    return;
                }

                try {
                    app(SendElectronicDocumentEmailService::class)->dispatch($record, $company);

                    Notification::make()
                        ->title(__('Email queued'))
                        ->body(__('The notification will be delivered to the configured recipient.'))
                        ->success()
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->title(__('Could not queue email'))
                        ->body($e->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();
                }
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'send_electronic_document_email';
    }
}
