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
use Modules\Sri\Enums\ElectronicCorrectionStatusEnum;
use Modules\Sri\Enums\ElectronicStatusEnum;
use Modules\Sri\Exceptions\XmlGenerationException;
use Modules\Sri\Jobs\ProcessElectronicDocument;
use Modules\Sri\Services\SriDocumentPreValidator;

final class RetryElectronicAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Resend to SRI'))
            ->tooltip(fn (Action $action) => $action->isIconButton() ? __('Retry SRI submission') : null)
            ->icon(Heroicon::ArrowPath)
            ->color('warning')
            ->visible(function (?Model $record): bool {
                if (! $record instanceof HasElectronicBilling || ! method_exists($record, 'canRetryElectronicProcessing')) {
                    return false;
                }

                return $record->canRetryElectronicProcessing();
            })
            ->authorize(function (Model $record): bool {
                return Gate::allows('retryElectronic', $record);
            })
            ->requiresConfirmation()
            ->modalHeading(__('Retry SRI submission'))
            ->modalDescription(__('This will re-attempt to sign and send the document to the SRI only for retryable technical failures.'))
            ->action(function (?Model $record): void {
                if (! $record instanceof HasElectronicBilling || ! method_exists($record, 'canRetryElectronicProcessing')) {
                    return;
                }

                if (! $record->canRetryElectronicProcessing()) {
                    Notification::make()
                        ->title(__('Document cannot be re-processed'))
                        ->body(__('Only issued documents with retryable technical failures can be retried. Use the correction flow when the SRI requires fiscal data changes.'))
                        ->danger()
                        ->persistent()
                        ->send();

                    return;
                }

                $record->loadMissing(['company', ...$record->getElectronicEagerLoads()]);

                /** @var Company $company */
                $company = $record->getRelation('company');

                try {
                    app(SriDocumentPreValidator::class)->validate($record, $company);
                } catch (XmlGenerationException $e) {
                    Notification::make()
                        ->title(__('Document cannot be re-processed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();

                    return;
                }

                // Reset status to allow re-processing
                $record->update([
                    'electronic_status' => ElectronicStatusEnum::Pending,
                    'correction_status' => ElectronicCorrectionStatusEnum::None,
                ]);

                ProcessElectronicDocument::dispatch(
                    modelClass: $record::class,
                    modelId: $record->getKey(),
                )->onQueue('electronic-billing');

                Notification::make()
                    ->title(__('Retry queued'))
                    ->body(__('The document will be re-submitted to the SRI.'))
                    ->warning()
                    ->send();
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'retry_electronic';
    }
}
