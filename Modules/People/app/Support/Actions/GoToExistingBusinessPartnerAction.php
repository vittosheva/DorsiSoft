<?php

declare(strict_types=1);

namespace Modules\People\Support\Actions;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Size;
use Modules\People\Filament\CoreApp\Resources\BusinessPartners\BusinessPartnerResource;
use Modules\People\Models\BusinessPartner;

final class GoToExistingBusinessPartnerAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Go to existing record'))
            ->requiresConfirmation()
            ->action(function (Get $get, Action $action): void {
                $id = $get('_duplicate_partner_id');
                if (! $id) {
                    return;
                }

                $partner = BusinessPartner::withTrashed()->find($id);
                if (! $partner) {
                    Notification::make()
                        ->title(__('The record could not be found.'))
                        ->danger()
                        ->send();

                    $action->halt();

                    return;
                }

                if ($partner->trashed()) {
                    $partner->restore();
                }

                $action->redirect(BusinessPartnerResource::getUrl('edit', [
                    'record' => $partner->getKey(),
                    'tenant' => Filament::getTenant(),
                ]));
            })
            ->button()
            ->size(Size::Small);
    }

    public static function getDefaultName(): ?string
    {
        return 'go_to_existing_partner';
    }
}
