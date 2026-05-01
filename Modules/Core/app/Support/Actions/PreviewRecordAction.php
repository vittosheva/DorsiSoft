<?php

declare(strict_types=1);

namespace Modules\Core\Support\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

final class PreviewRecordAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Preview'))
            ->tooltip(__('Preview'))
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->slideOver()
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('Close'));
    }

    public static function getDefaultName(): ?string
    {
        return 'preview_record';
    }
}
