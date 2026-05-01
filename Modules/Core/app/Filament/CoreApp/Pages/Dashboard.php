<?php

declare(strict_types=1);

namespace Modules\Core\Filament\CoreApp\Pages;

use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

final class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Home;

    protected static string $routePath = 'dashboard';

    public function getColumns(): int|array
    {
        return 4;
    }
}
