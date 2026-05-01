<?php

declare(strict_types=1);

namespace Modules\Core\Support\Actions;

use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Contracts\GeneratesPdf;
use Modules\Core\Jobs\GenerateDocumentPdf;

final class GeneratePdfBulkAction extends BulkAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Generate PDFs'))
            ->icon(Heroicon::DocumentArrowDown)
            ->color('gray')
            ->action(function (Collection $records): void {
                $tenantId = (string) filament()->getTenant()?->ruc;
                $userId = Auth::id();
                $count = 0;

                foreach ($records as $record) {
                    if ($record instanceof GeneratesPdf) {
                        GenerateDocumentPdf::dispatch(
                            modelClass: $record::class,
                            modelId: $record->getKey(),
                            userId: $userId,
                            tenantId: $tenantId,
                            notifyWhenReady: true,
                        );
                        $count++;
                    }
                }

                Notification::make()
                    ->title($count === 1 ? __('PDF generation started') : __('PDF generation started'))
                    ->body(
                        $count === 1
                            ? __('You will be notified when the PDF is ready.')
                            : __('You will be notified when each of the :count PDFs is ready.', ['count' => $count])
                    )
                    ->info()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    public static function getDefaultName(): ?string
    {
        return 'generate_pdf_bulk';
    }
}
