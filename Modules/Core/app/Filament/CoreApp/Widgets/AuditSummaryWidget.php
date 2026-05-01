<?php

declare(strict_types=1);

namespace Modules\Core\Filament\CoreApp\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Support\Audit\AuditDisplayDataResolver;

final class AuditSummaryWidget extends StatsOverviewWidget
{
    public ?Model $record = null;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected static bool $isLazy = false;

    protected ?string $heading = null;

    public static function canView(): bool
    {
        return false;
    }

    public function mount(): void
    {
        // $this->heading = __('Audit');
    }

    protected function getStats(): array
    {
        if ($this->record) {
            return [];
        }

        $audit = app(AuditDisplayDataResolver::class)->resolve($this->record);

        return [
            Stat::make(__('Created by'), $audit['creator_name']),
            Stat::make(__('Creation date'), $audit['created_at']),
            Stat::make(__('Modified by'), $audit['editor_name']),
            Stat::make(__('Modification date'), $audit['updated_at']),
        ];
    }
}
