<?php

declare(strict_types=1);

namespace Modules\Sri\Support\Concerns;

use Modules\Sri\Filament\Widgets\ElectronicHistoryWidget;

trait InteractsWithElectronicAuditPanel
{
    /**
     * @param  array<int, class-string>  $prepend
     * @param  array<int, class-string>  $append
     * @return array<int, class-string>
     */
    protected function getElectronicAuditWidgets(array $prepend = [], array $append = []): array
    {
        return [...$prepend, ElectronicHistoryWidget::class, ...$append];
    }

    /**
     * @return array{record: mixed}
     */
    protected function getElectronicAuditWidgetData(): array
    {
        return ['record' => $this->getRecord()];
    }
}
