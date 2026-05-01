<?php

declare(strict_types=1);

namespace Modules\Sri\Support\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Models\Company;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Exceptions\XmlGenerationException;
use Modules\Sri\Jobs\ProcessElectronicDocument;
use Modules\Sri\Services\SriDocumentPreValidator;

final class GenerateXmlAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Send to SRI'))
            ->tooltip(fn (Action $action) => $action->isIconButton() ? __('Send to SRI') : null)
            ->icon(Heroicon::PaperAirplane)
            ->color('info')
            ->visible(function (?Model $record): bool {
                if (! $record instanceof HasElectronicBilling || ! method_exists($record, 'canStartElectronicProcessing')) {
                    return false;
                }

                return $record->canStartElectronicProcessing();
            })
            ->requiresConfirmation()
            ->modalHeading(__('Send to SRI'))
            ->modalDescription(__('The document XML will be generated, signed, and sent to the SRI for electronic authorization.'))
            ->action(function (?Model $record): void {
                if (! $record instanceof HasElectronicBilling || ! method_exists($record, 'canStartElectronicProcessing')) {
                    return;
                }

                if (! $record->canStartElectronicProcessing()) {
                    Notification::make()
                        ->title(__('Document cannot be sent to the SRI'))
                        ->body(__('Only issued documents that are not already being processed can be submitted to the SRI.'))
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
                        ->title(__('Document cannot be processed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();

                    return;
                }

                ProcessElectronicDocument::dispatch(
                    modelClass: $record::class,
                    modelId: $record->getKey(),
                )->onQueue('electronic-billing');

                Notification::make()
                    ->title(__('Queued for SRI processing'))
                    ->body(__('The document is being processed. You will be notified when it is authorized.'))
                    ->info()
                    ->send();
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'generate_xml';
    }
}
