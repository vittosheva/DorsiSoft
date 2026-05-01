<?php

declare(strict_types=1);

namespace Modules\Core\Filament\CoreApp\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Schema;

final class Login extends BaseLogin
{
    /* public function getHeading(): string|Htmlable
    {
        return __('System Administration');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('Restricted access — authorized personnel only.');
    }*/

    public function mount(): void
    {
        parent::mount();

        $this->data['remember'] = true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                // $this->getRememberFormComponent(),
            ]);
    }
}
