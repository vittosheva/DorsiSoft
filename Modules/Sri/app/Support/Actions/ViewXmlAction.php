<?php

declare(strict_types=1);

namespace Modules\Sri\Support\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Support\XmlDisplayFormatter;

final class ViewXmlAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->tooltip(fn (Action $action) => $action->isIconButton() ? __('View XML') : null)
            ->icon(Heroicon::Eye)
            ->color('gray')
            ->visible(function (?Model $record): bool {
                if (! $record instanceof HasElectronicBilling) {
                    return false;
                }

                return filled($record->metadata['ride_path'] ?? null)
                    || filled($record->metadata['xml_path'] ?? null);
            })
            ->modalContent(function (?Model $record): View {
                $xmlPath = $record->metadata['ride_path'] ?? $record->metadata['xml_path'] ?? null;
                $xmlContent = '';

                if ($xmlPath) {
                    $disk = config('sri.electronic.xml_storage_disk', 'local');
                    $xmlContent = Storage::disk($disk)->get($xmlPath) ?? '';
                }

                return view('sri::actions.view-xml-modal', [
                    'xmlContent' => app(XmlDisplayFormatter::class)->format($xmlContent),
                ]);
            })
            ->modalHeading(__('XML'))
            ->modalWidth('4xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('Close'));
    }

    public static function getDefaultName(): ?string
    {
        return 'view_xml';
    }
}
