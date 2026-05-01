<?php

declare(strict_types=1);

namespace Modules\Core\Support\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;

abstract class BaseRelationManager extends RelationManager
{
    public $resourceRecord = null;
}
