<?php

declare(strict_types=1);

namespace Modules\Sri\Support\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Enums\ElectronicStatusEnum;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DownloadXmlAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->tooltip(fn (Action $action) => $action->isIconButton() ? __('Download XML') : null)
            ->icon(Heroicon::ArrowDownTray)
            ->color('gray')
            ->visible(function (?Model $record): bool {
                if (! $record instanceof HasElectronicBilling) {
                    return false;
                }

                $status = $record->getElectronicStatus();

                return in_array($status, [
                    ElectronicStatusEnum::XmlGenerated,
                    ElectronicStatusEnum::Signed,
                    ElectronicStatusEnum::Submitted,
                    ElectronicStatusEnum::Authorized,
                ], strict: true);
            })
            ->action(function (Model $record): StreamedResponse {
                /** @var HasElectronicBilling $record */
                // Prefer RIDE (authorized XML) over signed XML
                $ridePath = $record->metadata['ride_path'] ?? null;
                $xmlPath = $record->metadata['xml_path'] ?? null;
                $path = $ridePath ?? $xmlPath;

                $disk = config('sri.electronic.xml_storage_disk', 'local');
                $filename = ($record->access_key ?? 'document').'.xml';

                return response()->streamDownload(function () use ($path, $disk): void {
                    echo Storage::disk($disk)->get($path) ?? '';
                }, $filename, ['Content-Type' => 'application/xml']);
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'download_xml';
    }
}
