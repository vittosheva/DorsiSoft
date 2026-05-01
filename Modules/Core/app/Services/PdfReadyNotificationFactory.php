<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Core\Contracts\GeneratesPdf;
use Modules\Core\Models\PdfShareLink;
use Modules\Core\Support\Pdf\PdfDocumentRouteKey;

final class PdfReadyNotificationFactory
{
    public function __construct(
        private readonly PdfShareLinkService $pdfShareLinkService,
    ) {}

    public function make(string $modelClass, int $modelId, Model&GeneratesPdf $document, PdfShareLink $shareLink): Notification
    {
        $shareUrl = $this->pdfShareLinkService->temporarySignedViewUrl($shareLink);
        $expiresAt = $shareLink->expires_at?->timezone(config('app.timezone'))->format('Y-m-d H:i');

        return Notification::make()
            ->title(__('Document :code was generated', ['code' => $document->code]))
            ->body(Str::markdown(__('Expires at: :expiresAt', ['expiresAt' => $expiresAt])))
            ->success()
            ->actions([
                Action::make('open')
                    ->icon(Heroicon::OutlinedEye)
                    ->button()
                    ->color('gray')
                    ->url($shareUrl)
                    ->openUrlInNewTab(),
                Action::make('download')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->button()
                    ->url(route('core.pdf.download', [
                        'model' => PdfDocumentRouteKey::fromClass($modelClass),
                        'id' => $modelId,
                    ]))
                    ->openUrlInNewTab(),
            ]);
    }
}
