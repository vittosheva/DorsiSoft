<?php

declare(strict_types=1);

namespace Modules\People\Filament\CoreApp\Resources\BusinessPartners\Pages;

use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Modules\Core\Support\Actions\FastCreateAction;
use Modules\Core\Support\Pages\BaseListRecords;
use Modules\People\Filament\CoreApp\Resources\BusinessPartners\BusinessPartnerResource;
use Modules\People\Filament\CoreApp\Resources\BusinessPartners\Schemas\BusinessPartnerFastCreateForm;
use Modules\People\Models\BusinessPartner;
use Modules\People\Support\Actions\CreateFinalConsumerAction;

final class ListBusinessPartners extends BaseListRecords
{
    protected static string $resource = BusinessPartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateFinalConsumerAction::make('create_final_consumer'),
            /* FastCreateAction::make()
                ->schema(fn (Schema $schema) => BusinessPartnerFastCreateForm::configure($schema))
                ->modalSubmitActionLabel(__('Create'))
                ->action(function (array $data): void {
                    $roles = $data['roles'] ?? [];
                    unset($data['roles']);

                    $partner = BusinessPartner::create($data);
                    $partner->roles()->sync($roles);

                    Notification::make()
                        ->title(__(':record f created successfully.', ['record' => static::getResource()::getTitleCaseModelLabel()]))
                        ->success()
                        ->send();
                }), */
            ...parent::getHeaderActions(),
        ];
    }
}
