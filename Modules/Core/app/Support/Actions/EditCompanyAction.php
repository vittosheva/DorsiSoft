<?php

declare(strict_types=1);

namespace Modules\Core\Support\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Modules\Core\Filament\CoreApp\Pages\EditCompany;

final class EditCompanyAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Go to Company Settings'))
            ->icon(Heroicon::Cog)
            ->button()
            ->url(EditCompany::getUrl())
            ->openUrlInNewTab();
    }

    public static function getDefaultName(): ?string
    {
        return 'edit_company';
    }
}
