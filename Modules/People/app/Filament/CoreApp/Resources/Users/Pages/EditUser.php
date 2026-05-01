<?php

declare(strict_types=1);

namespace Modules\People\Filament\CoreApp\Resources\Users\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Modules\Core\Support\Actions\SeparatorAction;
use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\People\Filament\CoreApp\Resources\Users\UserResource;
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

        $roleName = $this->record
            ->roles()
            ->where((new Role())->getTable().'.company_id', $tenantId)
            ->value('name');

        $data['role_name'] = $roleName;

        return $data;
    }

    protected function afterSave(): void
    {
        $tenantId = Filament::getTenant()?->getKey();

        if (blank($tenantId)) {
            return;
        }

        $this->record->companies()->syncWithoutDetaching([$tenantId]);

        $roleName = $this->data['role_name'] ?? null;

        if (blank($roleName)) {
            return;
        }

        setPermissionsTeamId((int) $tenantId);
        Role::findOrCreate((string) $roleName, 'web');
        $this->record->syncRoles([(string) $roleName]);
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
