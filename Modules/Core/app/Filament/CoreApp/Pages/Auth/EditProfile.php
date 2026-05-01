<?php

declare(strict_types=1);

namespace Modules\Core\Filament\CoreApp\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

final class EditProfile extends BaseEditProfile
{
    public function mount(): void
    {
        $user = Filament::auth()->user();

        if (($user instanceof Model) && (! Filament::getTenant())) {
            $tenant = Filament::getUserDefaultTenant($user);

            if ($tenant) {
                Filament::setTenant($tenant, isQuiet: true);
            }
        }

        parent::mount();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('tenant_company')
                    ->state(fn (): string => (string) (Filament::getTenant()?->getAttribute('legal_name') ?? '—')),
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }
}
