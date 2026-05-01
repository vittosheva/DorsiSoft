<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

final class Login extends BaseLogin
{
    public function getHeading(): string|Htmlable
    {
        return __('System Administration');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('Restricted access — authorized personnel only.');
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
