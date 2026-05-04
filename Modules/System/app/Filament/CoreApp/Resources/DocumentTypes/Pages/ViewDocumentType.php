<?php

declare(strict_types=1);

namespace Modules\System\Filament\CoreApp\Resources\DocumentTypes\Pages;

use Modules\Core\Support\Pages\BaseViewRecord;
use Modules\System\Filament\CoreApp\Resources\DocumentTypes\DocumentTypeResource;

final class ViewDocumentType extends BaseViewRecord
{
    protected static string $resource = DocumentTypeResource::class;
}
