<?php

declare(strict_types=1);

namespace Modules\Core\Support\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Modules\Core\Contracts\GeneratesPdf;
use Modules\Core\Services\DocumentPdfGenerator;
use Modules\Core\Support\Pdf\PdfDocumentRouteKey;

final class GeneratePdfAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->tooltip(fn (Action $action) => $action->isIconButton() ? __('Generate pdf') : null)
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->color('gray')
            ->action(function (?Model $record, Component $livewire): void {
                if (! $record || ! $record instanceof GeneratesPdf) {
                    return;
                }

                app(DocumentPdfGenerator::class)->generate(
                    $record,
                    (string) filament()->getTenant()?->ruc,
                );

                Notification::make()
                    ->title(__('PDF generated'))
                    ->success()
                    ->send();

                $pdfViewUrl = route('core.pdf.view', [
                    'model' => PdfDocumentRouteKey::fromClass($record::class),
                    'id' => $record->getKey(),
                ]);

                $livewire->js("window.open('{$pdfViewUrl}', '_blank')");
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'generate_pdf';
    }
}
