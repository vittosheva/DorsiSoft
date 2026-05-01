<?php

declare(strict_types=1);

namespace Modules\Sri\Filament\Widgets;

use Filament\Schemas\Components\Livewire as LivewireSchema;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;
use Modules\Sri\Livewire\SRIAuditTabs;
use Modules\Sri\Support\Concerns\InteractsWithElectronicAuditData;

final class ElectronicHistoryWidget extends Widget implements HasSchemas
{
    use InteractsWithElectronicAuditData;
    use InteractsWithSchemas;

    protected bool $isCollapsible = true;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'sri::widgets.electronic-history';

    protected ?string $pollingInterval = null;

    protected static bool $isLazy = false;

    public function content(Schema $schema): Schema
    {
        if (! $this->shouldShowWidget()) {
            return $schema->components([]);
        }

        return $schema->components([
            LivewireSchema::make(SRIAuditTabs::class, [
                'record' => $this->record,
            ]),
        ]);
    }

    public function shouldShowWidget(): bool
    {
        return $this->hasElectronicAuditRecord();
    }
}
