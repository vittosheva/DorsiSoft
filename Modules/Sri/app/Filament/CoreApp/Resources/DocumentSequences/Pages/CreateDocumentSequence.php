<?php

declare(strict_types=1);

namespace Modules\Sri\Filament\CoreApp\Resources\DocumentSequences\Pages;

use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Sri\Filament\CoreApp\Resources\DocumentSequences\DocumentSequenceResource;

final class CreateDocumentSequence extends BaseCreateRecord
{
    protected static string $resource = DocumentSequenceResource::class;
}
