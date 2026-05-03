<?php

declare(strict_types=1);

namespace Modules\People\Filament\CoreApp\Resources\Users\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Modules\Core\Support\Actions\SeparatorAction;
use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\People\Filament\CoreApp\Resources\Users\UserResource;
use Modules\People\Services\UserTenantRoleSyncService;
use Spatie\Permission\Models\Role;

final class EditUser extends BaseEditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $tenantId = Filament::getTenant()?->getKey();

        if (blank($tenantId)) {
            return $data;
        }

        $roleIds = $this->record
            ->roles()
            ->where((new Role())->getTable().'.company_id', $tenantId)
            ->pluck((new Role())->getTable().'.id')
            ->all();

        $data['role_name'] = $roleIds;

        return $data;
    }

    protected function afterSave(): void
    {
        $tenantId = Filament::getTenant()?->getKey();

        if (blank($tenantId)) {
            return;
        }

        $roleIds = (array) ($this->data['role_name'] ?? []);

        app(UserTenantRoleSyncService::class)->sync($this->record, (int) $tenantId, $roleIds);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            SeparatorAction::make(),
            DeleteAction::make(),
        ];
    }
}
